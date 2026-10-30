<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\ErpCustomer;
use App\Models\ErpProduct;
use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use App\Models\JobPosition;
use App\Models\ProductCategory;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. User / Admin
        $user = User::updateOrCreate(
            ['email' => 'admin@demo.test'],
            [
                'name' => 'Alexander Wright',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // 2. HR & Departments
        $deptOps = Department::firstOrCreate(['name' => 'Operations & Logistics'], ['description' => 'Warehousing, inventory control, and fulfillment']);
        $deptEng = Department::firstOrCreate(['name' => 'Hardware Engineering'], ['description' => 'Embedded electronics, robotics, and QA design']);
        $deptProc = Department::firstOrCreate(['name' => 'Global Procurement'], ['description' => 'Vendor relations, PO issuance, and supply chain']);
        $deptFin = Department::firstOrCreate(['name' => 'Finance & Accounting'], ['description' => 'General ledger, payroll, and asset accounting']);
        $deptHR = Department::firstOrCreate(['name' => 'Human Resources'], ['description' => 'Personnel administration, recruitment, and benefits']);

        $posLogLead = JobPosition::firstOrCreate(['department_id' => $deptOps->id, 'title' => 'Warehouse Logistics Lead']);
        $posEngLead = JobPosition::firstOrCreate(['department_id' => $deptEng->id, 'title' => 'Principal Hardware Engineer']);
        $posProc = JobPosition::firstOrCreate(['department_id' => $deptProc->id, 'title' => 'Senior Procurement Specialist']);
        $posFin = JobPosition::firstOrCreate(['department_id' => $deptFin->id, 'title' => 'Chief Financial Controller']);
        $posHR = JobPosition::firstOrCreate(['department_id' => $deptHR->id, 'title' => 'Talent Operations Partner']);

        $employees = [
            ['first_name' => 'Marcus', 'last_name' => 'Vance', 'email' => 'marcus.vance@enterprise.demo', 'phone' => '+62 812-5551-0192', 'department_id' => $deptOps->id, 'job_position_id' => $posLogLead->id, 'salary_cents' => 620000, 'hire_date' => '2023-03-15'],
            ['first_name' => 'Elena', 'last_name' => 'Rostova', 'email' => 'elena.rostova@enterprise.demo', 'phone' => '+62 813-5552-3341', 'department_id' => $deptEng->id, 'job_position_id' => $posEngLead->id, 'salary_cents' => 850000, 'hire_date' => '2022-07-01'],
            ['first_name' => 'David', 'last_name' => 'Chen', 'email' => 'david.chen@enterprise.demo', 'phone' => '+62 811-5553-9081', 'department_id' => $deptProc->id, 'job_position_id' => $posProc->id, 'salary_cents' => 580000, 'hire_date' => '2024-01-10'],
            ['first_name' => 'Sarah', 'last_name' => 'Jenkins', 'email' => 'sarah.jenkins@enterprise.demo', 'phone' => '+62 815-5554-7729', 'department_id' => $deptFin->id, 'job_position_id' => $posFin->id, 'salary_cents' => 740000, 'hire_date' => '2023-09-01'],
            ['first_name' => 'Kevin', 'last_name' => 'Alamsyah', 'email' => 'kevin.alamsyah@enterprise.demo', 'phone' => '+62 818-5555-1204', 'department_id' => $deptHR->id, 'job_position_id' => $posHR->id, 'salary_cents' => 490000, 'hire_date' => '2024-05-18'],
        ];

        foreach ($employees as $empData) {
            Employee::firstOrCreate(['email' => $empData['email']], $empData + ['status' => 'active']);
        }

        // 3. Warehouses & Categories
        $whAlpha = Warehouse::firstOrCreate(['name' => 'Central Hub Alpha - Cikarang'], ['location' => 'Kawasan Industri GIIC Cikarang, Blok B3']);
        $whBeta = Warehouse::firstOrCreate(['name' => 'Regional Distribution Hub - Surabaya'], ['location' => 'Kawasan Industri SIER Surabaya, Blok D12']);

        $catAuto = ProductCategory::firstOrCreate(['name' => 'Robotics & Automation']);
        $catSemi = ProductCategory::firstOrCreate(['name' => 'Semiconductors & MCUs']);
        $catPwr = ProductCategory::firstOrCreate(['name' => 'Power Supply Units']);
        $catEnc = ProductCategory::firstOrCreate(['name' => 'Chassis & Enclosures']);

        // 4. Products
        $productsData = [
            ['name' => 'Servo Motor S90 Industrial Actuator', 'sku' => 'SRV-90M', 'category_id' => $catAuto->id, 'unit_of_measure' => 'units', 'unit_price_cents' => 4500, 'cost_price_cents' => 2400, 'min_stock_threshold' => 20, 'current_stock' => 65],
            ['name' => 'Microcontroller MCU-32 Cortex-M4', 'sku' => 'MCU-32X', 'category_id' => $catSemi->id, 'unit_of_measure' => 'pcs', 'unit_price_cents' => 1450, 'cost_price_cents' => 780, 'min_stock_threshold' => 50, 'current_stock' => 18],
            ['name' => 'Industrial DIN-Rail Power Supply 24V 10A', 'sku' => 'PWR-24V10', 'category_id' => $catPwr->id, 'unit_of_measure' => 'units', 'unit_price_cents' => 8900, 'cost_price_cents' => 4800, 'min_stock_threshold' => 15, 'current_stock' => 38],
            ['name' => 'High-Precision Laser Distance Sensor', 'sku' => 'SNS-OPT4K', 'category_id' => $catAuto->id, 'unit_of_measure' => 'pcs', 'unit_price_cents' => 6200, 'cost_price_cents' => 3300, 'min_stock_threshold' => 20, 'current_stock' => 9],
            ['name' => 'Extruded Aluminum Enclosure IP67', 'sku' => 'ENC-AL67', 'category_id' => $catEnc->id, 'unit_of_measure' => 'units', 'unit_price_cents' => 4800, 'cost_price_cents' => 2200, 'min_stock_threshold' => 25, 'current_stock' => 42],
            ['name' => 'Industrial IoT Edge Gateway 4G/LTE', 'sku' => 'GTW-LTE04', 'category_id' => $catSemi->id, 'unit_of_measure' => 'units', 'unit_price_cents' => 17500, 'cost_price_cents' => 9500, 'min_stock_threshold' => 12, 'current_stock' => 31],
            ['name' => 'Brushless DC Motor Driver 48V 20A', 'sku' => 'BLDC-DRV48', 'category_id' => $catAuto->id, 'unit_of_measure' => 'units', 'unit_price_cents' => 5600, 'cost_price_cents' => 3100, 'min_stock_threshold' => 30, 'current_stock' => 12],
            ['name' => 'Solid State Relay 40A AC/DC', 'sku' => 'SSR-40A-AC', 'category_id' => $catPwr->id, 'unit_of_measure' => 'pcs', 'unit_price_cents' => 2100, 'cost_price_cents' => 1050, 'min_stock_threshold' => 40, 'current_stock' => 84],
        ];

        foreach ($productsData as $pData) {
            ErpProduct::firstOrCreate(['sku' => $pData['sku']], $pData);
        }

        $prodMCU = ErpProduct::where('sku', 'MCU-32X')->first();
        $prodSrv = ErpProduct::where('sku', 'SRV-90M')->first();
        $prodPwr = ErpProduct::where('sku', 'PWR-24V10')->first();
        $prodSns = ErpProduct::where('sku', 'SNS-OPT4K')->first();
        $prodGtw = ErpProduct::where('sku', 'GTW-LTE04')->first();

        // 5. Suppliers
        $supApex = Supplier::firstOrCreate(['name' => 'Apex Automation Components'], ['email' => 'sales@apex-automation.sg', 'phone' => '+65 6789 1234', 'address' => '10 Tuas South Ave 2, Singapore']);
        $supSilicon = Supplier::firstOrCreate(['name' => 'Shenzhen MicroSilicon Electronics'], ['email' => 'orders@microsilicon.hk', 'phone' => '+852 2345 6789', 'address' => 'Baoan District, Shenzhen, GD']);
        $supIndo = Supplier::firstOrCreate(['name' => 'PT Mitra Logam Presisi'], ['email' => 'kontak@mitralogam.co.id', 'phone' => '+62 21-8983-0012', 'address' => 'Kawasan Industri Jababeka II, Cikarang']);

        // 6. Customers
        $custNusantara = ErpCustomer::firstOrCreate(['name' => 'PT Automasi Nusantara Prima'], ['email' => 'procurement@automasi-nusantara.id', 'phone' => '+62 21-5290-8800', 'company' => 'Nusantara Robotics Group']);
        $custCyber = ErpCustomer::firstOrCreate(['name' => 'Cybertronics Labs Global'], ['email' => 'purchasing@cybertronics.io', 'phone' => '+62 22-7201-9923', 'company' => 'Cybertronics R&D Hub']);
        $custAero = ErpCustomer::firstOrCreate(['name' => 'AeroDyn Instrumentation Ltd'], ['email' => 'supply@aerodyn.org', 'phone' => '+62 31-8433-2190', 'company' => 'AeroDyn Avionics']);

        // 7. Purchase Orders
        $po1 = PurchaseOrder::firstOrCreate(['po_number' => 'PO-20261026-9812'], [
            'supplier_id' => $supSilicon->id,
            'status' => 'received',
            'order_date' => Carbon::now()->subDays(4)->toDateString(),
            'received_date' => Carbon::now()->subDays(2)->toDateString(),
            'total_cents' => 198000,
            'created_by' => $user->id,
        ]);
        if ($po1->wasRecentlyCreated) {
            $po1->items()->create(['erp_product_id' => $prodMCU->id, 'quantity' => 150, 'unit_cost_cents' => 780, 'total_cents' => 117000]);
            $po1->items()->create(['erp_product_id' => $prodSns->id, 'quantity' => 25, 'unit_cost_cents' => 3240, 'total_cents' => 81000]);
        }

        $po2 = PurchaseOrder::firstOrCreate(['po_number' => 'PO-20261029-4180'], [
            'supplier_id' => $supApex->id,
            'status' => 'ordered',
            'order_date' => Carbon::now()->subDay()->toDateString(),
            'total_cents' => 162000,
            'created_by' => $user->id,
        ]);
        if ($po2->wasRecentlyCreated) {
            $po2->items()->create(['erp_product_id' => $prodSrv->id, 'quantity' => 40, 'unit_cost_cents' => 2400, 'total_cents' => 96000]);
            $po2->items()->create(['erp_product_id' => $prodPwr->id, 'quantity' => 15, 'unit_cost_cents' => 4400, 'total_cents' => 66000]);
        }

        $po3 = PurchaseOrder::firstOrCreate(['po_number' => 'PO-20261030-7714'], [
            'supplier_id' => $supIndo->id,
            'status' => 'draft',
            'order_date' => Carbon::now()->toDateString(),
            'total_cents' => 66000,
            'created_by' => $user->id,
        ]);
        if ($po3->wasRecentlyCreated) {
            $po3->items()->create(['erp_product_id' => $prodSrv->id, 'quantity' => 15, 'unit_cost_cents' => 2400, 'total_cents' => 36000]);
            $po3->items()->create(['erp_product_id' => $prodMCU->id, 'quantity' => 50, 'unit_cost_cents' => 600, 'total_cents' => 30000]);
        }

        // 8. Sales Orders
        $so1 = SalesOrder::firstOrCreate(['so_number' => 'SO-20261025-1042'], [
            'erp_customer_id' => $custNusantara->id,
            'status' => 'fulfilled',
            'order_date' => Carbon::now()->subDays(5)->toDateString(),
            'total_cents' => 143500,
            'created_by' => $user->id,
        ]);
        if ($so1->wasRecentlyCreated) {
            $so1->items()->create(['erp_product_id' => $prodSrv->id, 'quantity' => 15, 'unit_price_cents' => 4500, 'total_cents' => 67500]);
            $so1->items()->create(['erp_product_id' => $prodPwr->id, 'quantity' => 8, 'unit_price_cents' => 8900, 'total_cents' => 71200]);
            $so1->items()->create(['erp_product_id' => $prodMCU->id, 'quantity' => 3, 'unit_price_cents' => 1600, 'total_cents' => 4800]);
        }

        $so2 = SalesOrder::firstOrCreate(['so_number' => 'SO-20261028-3381'], [
            'erp_customer_id' => $custCyber->id,
            'status' => 'confirmed',
            'order_date' => Carbon::now()->subDays(2)->toDateString(),
            'total_cents' => 175000,
            'created_by' => $user->id,
        ]);
        if ($so2->wasRecentlyCreated) {
            $so2->items()->create(['erp_product_id' => $prodGtw->id, 'quantity' => 10, 'unit_price_cents' => 17500, 'total_cents' => 175000]);
        }

        $so3 = SalesOrder::firstOrCreate(['so_number' => 'SO-20261030-9012'], [
            'erp_customer_id' => $custAero->id,
            'status' => 'draft',
            'order_date' => Carbon::now()->toDateString(),
            'total_cents' => 124000,
            'created_by' => $user->id,
        ]);
        if ($so3->wasRecentlyCreated) {
            $so3->items()->create(['erp_product_id' => $prodSns->id, 'quantity' => 20, 'unit_price_cents' => 6200, 'total_cents' => 124000]);
        }

        // 9. Financial Accounts & Transactions
        $accCash = FinancialAccount::firstOrCreate(['code' => '1010'], ['name' => 'Main Operating Cash Account', 'type' => 'asset', 'balance_cents' => 8425000]);
        $accAR = FinancialAccount::firstOrCreate(['code' => '1020'], ['name' => 'Accounts Receivable', 'type' => 'asset', 'balance_cents' => 2990000]);
        $accRev = FinancialAccount::firstOrCreate(['code' => '4010'], ['name' => 'Commercial Product Sales Revenue', 'type' => 'revenue', 'balance_cents' => 14850000]);
        $accCOGS = FinancialAccount::firstOrCreate(['code' => '5010'], ['name' => 'Cost of Goods Sold (COGS)', 'type' => 'expense', 'balance_cents' => 7420000]);
        $accOpEx = FinancialAccount::firstOrCreate(['code' => '5020'], ['name' => 'General Operations & Facilities', 'type' => 'expense', 'balance_cents' => 3180000]);

        $transactions = [
            ['financial_account_id' => $accCash->id, 'type' => 'income', 'amount_cents' => 143500, 'reference' => 'SO-20261025-1042', 'description' => 'Payment received from PT Automasi Nusantara Prima', 'transaction_date' => Carbon::now()->subDays(4)->toDateString()],
            ['financial_account_id' => $accCash->id, 'type' => 'expense', 'amount_cents' => 198000, 'reference' => 'PO-20261026-9812', 'description' => 'Direct settlement for Shenzhen MicroSilicon PO', 'transaction_date' => Carbon::now()->subDays(2)->toDateString()],
            ['financial_account_id' => $accCash->id, 'type' => 'income', 'amount_cents' => 87500, 'reference' => 'DP-CYBER-09', 'description' => 'Down payment for Cybertronics IoT Gateways batch', 'transaction_date' => Carbon::now()->subDay()->toDateString()],
            ['financial_account_id' => $accOpEx->id, 'type' => 'expense', 'amount_cents' => 125000, 'reference' => 'AWS-CLOUD-OCT', 'description' => 'Cloud infrastructure & IoT MQTT broker monthly fee', 'transaction_date' => Carbon::now()->subDays(3)->toDateString()],
            ['financial_account_id' => $accCash->id, 'type' => 'expense', 'amount_cents' => 45000, 'reference' => 'EXP-FAC-22', 'description' => 'Precision calibration service for optical inspection lab', 'transaction_date' => Carbon::now()->toDateString()],
        ];

        foreach ($transactions as $tData) {
            FinancialTransaction::firstOrCreate(['reference' => $tData['reference']], $tData + ['created_by' => $user->id]);
        }

        // 10. Stock Movements
        StockMovement::firstOrCreate(['notes' => 'Received goods against PO PO-20261026-9812'], [
            'erp_product_id' => $prodMCU->id,
            'warehouse_id' => $whAlpha->id,
            'type' => 'in',
            'quantity' => 150,
            'reference_type' => 'PurchaseOrder',
            'reference_id' => $po1->id,
            'created_by' => $user->id,
        ]);

        StockMovement::firstOrCreate(['notes' => 'Fulfillment for Sales Order SO-20261025-1042'], [
            'erp_product_id' => $prodSrv->id,
            'warehouse_id' => $whAlpha->id,
            'type' => 'out',
            'quantity' => 15,
            'reference_type' => 'SalesOrder',
            'reference_id' => $so1->id,
            'created_by' => $user->id,
        ]);

        // 11. Audit Logs
        $logs = [
            ['action' => 'RECEIVE_GOODS', 'module' => 'Purchasing', 'description' => 'Received 150 MCU-32X & 25 SNS-OPT4K for PO PO-20261026-9812', 'created_at' => Carbon::now()->subDays(2)],
            ['action' => 'FULFILL_ORDER', 'module' => 'Sales', 'description' => 'Completed dispatch for SO SO-20261025-1042 (Nusantara Robotics)', 'created_at' => Carbon::now()->subDays(3)],
            ['action' => 'CREATE_PURCHASE_ORDER', 'module' => 'Purchasing', 'description' => 'Created PO PO-20261029-4180 totaling $1,620.00 for Apex Automation', 'created_at' => Carbon::now()->subDay()],
            ['action' => 'CONFIRM_SALES_ORDER', 'module' => 'Sales', 'description' => 'Confirmed sales order SO-20261028-3381 for Cybertronics Labs', 'created_at' => Carbon::now()->subDay()],
            ['action' => 'FINANCE_TRANSACTION', 'module' => 'Finance', 'description' => 'Recorded income $1,435.00 on account Main Operating Cash Account', 'created_at' => Carbon::now()->subDays(4)],
            ['action' => 'ADJUST_STOCK', 'module' => 'Inventory', 'description' => 'Adjusted stock for SRV-90M: in 10 units (QA batch acceptance)', 'created_at' => Carbon::now()->subHours(6)],
        ];

        foreach ($logs as $logData) {
            AuditLog::firstOrCreate(['description' => $logData['description']], $logData + ['user_id' => $user->id]);
        }
    }
}
