<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\ErpCustomer;
use App\Models\ErpProduct;
use App\Models\FinancialAccount;
use App\Models\JobPosition;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErpSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_erp_dashboard_renders_metrics(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Enterprise Resource Planning');
    }

    public function test_inventory_product_creation_and_stock_adjustment(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/inventory/products', [
            'name' => 'Industrial Sensor X',
            'sku' => 'SENS-001',
            'cost_price' => 25.00,
            'unit_price' => 45.00,
            'min_stock_threshold' => 10,
            'initial_stock' => 5,
        ]);

        $response->assertRedirect('/inventory');
        $product = ErpProduct::where('sku', 'SENS-001')->firstOrFail();
        $this->assertEquals(5, $product->current_stock);
        $this->assertTrue($product->isLowStock());

        // Adjust stock (+10)
        $adjustResponse = $this->actingAs($user)->post("/inventory/products/{$product->id}/adjust", [
            'type' => 'in',
            'quantity' => 10,
            'notes' => 'Received emergency replenishment',
        ]);

        $adjustResponse->assertRedirect('/inventory');
        $product->refresh();
        $this->assertEquals(15, $product->current_stock);
        $this->assertFalse($product->isLowStock());

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'ADJUST_STOCK',
            'module' => 'Inventory',
        ]);
    }

    public function test_purchasing_workflow_receives_goods_and_updates_stock(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::create([
            'name' => 'Global Components Ltd',
            'email' => 'sales@globalcomp.com',
        ]);

        $product = ErpProduct::create([
            'name' => 'Microcontroller MCU-32',
            'sku' => 'MCU-32',
            'unit_price_cents' => 1200,
            'cost_price_cents' => 800,
            'min_stock_threshold' => 20,
            'current_stock' => 5,
        ]);

        $response = $this->actingAs($user)->post('/purchasing/orders', [
            'supplier_id' => $supplier->id,
            'order_date' => now()->toDateString(),
            'items' => [
                [
                    'erp_product_id' => $product->id,
                    'quantity' => 50,
                    'unit_cost' => 8.00,
                ],
            ],
        ]);

        $response->assertRedirect('/purchasing');
        $po = PurchaseOrder::where('supplier_id', $supplier->id)->firstOrFail();
        $this->assertEquals('ordered', $po->status);
        $this->assertEquals(40000, $po->total_cents); // 50 * $8.00 = $400.00

        // Receive goods
        $receiveResponse = $this->actingAs($user)->post("/purchasing/orders/{$po->id}/receive");
        $receiveResponse->assertRedirect('/purchasing');

        $po->refresh();
        $product->refresh();
        $this->assertEquals('received', $po->status);
        $this->assertEquals(55, $product->current_stock); // 5 + 50
    }

    public function test_sales_workflow_fulfills_and_decrements_stock(): void
    {
        $user = User::factory()->create();
        $customer = ErpCustomer::create([
            'name' => 'Apex Robotics',
            'email' => 'procurement@apexrobotics.com',
        ]);

        $product = ErpProduct::create([
            'name' => 'Servo Motor S90',
            'sku' => 'SERVO-90',
            'unit_price_cents' => 2500,
            'cost_price_cents' => 1500,
            'min_stock_threshold' => 5,
            'current_stock' => 10,
        ]);

        $response = $this->actingAs($user)->post('/sales/orders', [
            'erp_customer_id' => $customer->id,
            'order_date' => now()->toDateString(),
            'items' => [
                [
                    'erp_product_id' => $product->id,
                    'quantity' => 4,
                    'unit_price' => 25.00,
                ],
            ],
        ]);

        $response->assertRedirect('/sales');
        $so = SalesOrder::where('erp_customer_id', $customer->id)->firstOrFail();
        $this->assertEquals(10000, $so->total_cents);

        // Fulfill order
        $fulfillResponse = $this->actingAs($user)->post("/sales/orders/{$so->id}/fulfill");
        $fulfillResponse->assertRedirect('/sales');

        $so->refresh();
        $product->refresh();
        $this->assertEquals('fulfilled', $so->status);
        $this->assertEquals(6, $product->current_stock); // 10 - 4 = 6
    }

    public function test_finance_income_and_expense_transactions(): void
    {
        $user = User::factory()->create();
        $account = FinancialAccount::create([
            'name' => 'Primary Vault',
            'code' => '1001',
            'type' => 'asset',
            'balance_cents' => 100000, // $1,000.00
        ]);

        // Income $500.00
        $this->actingAs($user)->post('/finance/transactions', [
            'financial_account_id' => $account->id,
            'type' => 'income',
            'amount' => 500.00,
            'description' => 'Consulting fee deposit',
            'transaction_date' => now()->toDateString(),
        ]);

        $account->refresh();
        $this->assertEquals(150000, $account->balance_cents);

        // Expense $200.00
        $this->actingAs($user)->post('/finance/transactions', [
            'financial_account_id' => $account->id,
            'type' => 'expense',
            'amount' => 200.00,
            'description' => 'Office internet bill',
            'transaction_date' => now()->toDateString(),
        ]);

        $account->refresh();
        $this->assertEquals(130000, $account->balance_cents); // $1,300.00
    }

    public function test_hr_employee_registration(): void
    {
        $user = User::factory()->create();
        $dept = Department::create(['name' => 'Finance Dept']);
        $pos = JobPosition::create(['department_id' => $dept->id, 'title' => 'Chief Accountant']);

        $response = $this->actingAs($user)->post('/hr/employees', [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane.doe@enterprise.com',
            'phone' => '+15551234567',
            'department_id' => $dept->id,
            'job_position_id' => $pos->id,
            'hire_date' => now()->toDateString(),
            'salary' => 7500.00,
        ]);

        $response->assertRedirect('/hr');
        $this->assertDatabaseHas('employees', [
            'email' => 'jane.doe@enterprise.com',
            'salary_cents' => 750000,
        ]);
    }
}
