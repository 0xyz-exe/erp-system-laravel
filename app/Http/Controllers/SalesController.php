<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ErpCustomer;
use App\Models\ErpProduct;
use App\Models\SalesOrder;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SalesController extends Controller
{
    public function index(): View
    {
        $orders = SalesOrder::with(['customer', 'items.product', 'creator'])->latest()->paginate(15);
        $customers = ErpCustomer::orderBy('name')->get();
        $products = ErpProduct::where('current_stock', '>', 0)->orderBy('name')->get();
        return view('sales.index', compact('orders', 'customers', 'products'));
    }

    public function storeOrder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'erp_customer_id' => 'required|exists:erp_customers,id',
            'order_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.erp_product_id' => 'required|exists:erp_products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $soNumber = 'SO-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

        DB::transaction(function () use ($validated, $soNumber, $request) {
            $totalCents = 0;
            $so = SalesOrder::create([
                'so_number' => $soNumber,
                'erp_customer_id' => $validated['erp_customer_id'],
                'status' => 'confirmed',
                'order_date' => $validated['order_date'],
                'created_by' => $request->user()->id,
            ]);

            foreach ($validated['items'] as $item) {
                $qty = (int) $item['quantity'];
                $priceCents = (int) round($item['unit_price'] * 100);
                $lineTotalCents = $qty * $priceCents;
                $totalCents += $lineTotalCents;

                $so->items()->create([
                    'erp_product_id' => $item['erp_product_id'],
                    'quantity' => $qty,
                    'unit_price_cents' => $priceCents,
                    'total_cents' => $lineTotalCents,
                ]);
            }

            $so->update(['total_cents' => $totalCents]);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'CREATE_SALES_ORDER',
                'module' => 'Sales',
                'description' => "Created Sales Order {$soNumber} totaling $" . number_format($totalCents / 100, 2),
            ]);
        });

        return redirect()->route('sales.index')->with('success', "Sales Order {$soNumber} created.");
    }

    public function fulfillOrder(Request $request, SalesOrder $order): RedirectResponse
    {
        if ($order->status === 'fulfilled') {
            return back()->with('error', 'Sales order is already fulfilled.');
        }

        // Validate stock availability first
        foreach ($order->items as $item) {
            if ($item->product->current_stock < $item->quantity) {
                return back()->with('error', "Insufficient stock for {$item->product->name}. Required: {$item->quantity}, Available: {$item->product->current_stock}");
            }
        }

        DB::transaction(function () use ($order, $request) {
            foreach ($order->items as $item) {
                $product = $item->product;
                $product->decrement('current_stock', $item->quantity);

                StockMovement::create([
                    'erp_product_id' => $product->id,
                    'type' => 'out',
                    'quantity' => $item->quantity,
                    'reference_type' => 'SalesOrder',
                    'reference_id' => $order->id,
                    'notes' => "Fulfilled against SO {$order->so_number}",
                    'created_by' => $request->user()->id,
                ]);
            }

            $order->update(['status' => 'fulfilled']);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'FULFILL_SALES_ORDER',
                'module' => 'Sales',
                'description' => "Fulfilled SO {$order->so_number}, stock deducted.",
            ]);
        });

        return redirect()->route('sales.index')->with('success', "Sales Order {$order->so_number} fulfilled.");
    }
}
