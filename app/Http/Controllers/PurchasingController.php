<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ErpProduct;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PurchasingController extends Controller
{
    public function index(): View
    {
        $orders = PurchaseOrder::with(['supplier', 'items.product', 'creator'])->latest()->paginate(15);
        $suppliers = Supplier::orderBy('name')->get();
        $products = ErpProduct::orderBy('name')->get();
        return view('purchasing.index', compact('orders', 'suppliers', 'products'));
    }

    public function storeOrder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'order_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.erp_product_id' => 'required|exists:erp_products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        $poNumber = 'PO-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

        DB::transaction(function () use ($validated, $poNumber, $request) {
            $totalCents = 0;
            $po = PurchaseOrder::create([
                'po_number' => $poNumber,
                'supplier_id' => $validated['supplier_id'],
                'status' => 'ordered',
                'order_date' => $validated['order_date'],
                'created_by' => $request->user()->id,
            ]);

            foreach ($validated['items'] as $item) {
                $qty = (int) $item['quantity'];
                $costCents = (int) round($item['unit_cost'] * 100);
                $lineTotalCents = $qty * $costCents;
                $totalCents += $lineTotalCents;

                $po->items()->create([
                    'erp_product_id' => $item['erp_product_id'],
                    'quantity' => $qty,
                    'unit_cost_cents' => $costCents,
                    'total_cents' => $lineTotalCents,
                ]);
            }

            $po->update(['total_cents' => $totalCents]);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'CREATE_PURCHASE_ORDER',
                'module' => 'Purchasing',
                'description' => "Created Purchase Order {$poNumber} totaling $" . number_format($totalCents / 100, 2),
            ]);
        });

        return redirect()->route('purchasing.index')->with('success', "Purchase Order {$poNumber} created successfully.");
    }

    public function receiveGoods(Request $request, PurchaseOrder $order): RedirectResponse
    {
        if ($order->status === 'received') {
            return back()->with('error', 'Purchase order is already received.');
        }

        DB::transaction(function () use ($order, $request) {
            foreach ($order->items as $item) {
                $product = $item->product;
                $product->increment('current_stock', $item->quantity);

                StockMovement::create([
                    'erp_product_id' => $product->id,
                    'type' => 'in',
                    'quantity' => $item->quantity,
                    'reference_type' => 'PurchaseOrder',
                    'reference_id' => $order->id,
                    'notes' => "Goods received against PO {$order->po_number}",
                    'created_by' => $request->user()->id,
                ]);
            }

            $order->update([
                'status' => 'received',
                'received_date' => now(),
            ]);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'RECEIVE_GOODS',
                'module' => 'Purchasing',
                'description' => "Received goods for PO {$order->po_number}, inventory updated.",
            ]);
        });

        return redirect()->route('purchasing.index')->with('success', "Goods for PO {$order->po_number} received into inventory.");
    }
}
