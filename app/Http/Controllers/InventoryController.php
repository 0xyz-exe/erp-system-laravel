<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ErpProduct;
use App\Models\ProductCategory;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(): View
    {
        $products = ErpProduct::with('category')->latest()->paginate(15);
        $categories = ProductCategory::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();
        return view('inventory.index', compact('products', 'categories', 'warehouses'));
    }

    public function storeProduct(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:50|unique:erp_products,sku',
            'category_id' => 'nullable|exists:product_categories,id',
            'unit_price' => 'required|numeric|min:0',
            'cost_price' => 'required|numeric|min:0',
            'min_stock_threshold' => 'required|integer|min:0',
            'initial_stock' => 'nullable|integer|min:0',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $product = ErpProduct::create([
                'name' => $validated['name'],
                'sku' => strtoupper($validated['sku']),
                'category_id' => $validated['category_id'] ?? null,
                'unit_price_cents' => (int) round($validated['unit_price'] * 100),
                'cost_price_cents' => (int) round($validated['cost_price'] * 100),
                'min_stock_threshold' => $validated['min_stock_threshold'],
                'current_stock' => $validated['initial_stock'] ?? 0,
            ]);

            if (($validated['initial_stock'] ?? 0) > 0) {
                StockMovement::create([
                    'erp_product_id' => $product->id,
                    'type' => 'in',
                    'quantity' => $validated['initial_stock'],
                    'notes' => 'Initial stock on creation',
                    'created_by' => $request->user()->id,
                ]);
            }

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'CREATE_PRODUCT',
                'module' => 'Inventory',
                'description' => "Created product {$product->name} (SKU: {$product->sku})",
            ]);
        });

        return redirect()->route('inventory.index')->with('success', 'Product registered successfully.');
    }

    public function adjustStock(Request $request, ErpProduct $product): RedirectResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:in,out,adjustment',
            'quantity' => 'required|integer|min:1',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'notes' => 'required|string|max:255',
        ]);

        DB::transaction(function () use ($product, $validated, $request) {
            $qty = $validated['quantity'];
            if ($validated['type'] === 'out' || ($validated['type'] === 'adjustment' && $qty < 0)) {
                if ($product->current_stock < $qty) {
                    abort(422, 'Cannot reduce stock below zero.');
                }
                $product->decrement('current_stock', $qty);
            } else {
                $product->increment('current_stock', $qty);
            }

            StockMovement::create([
                'erp_product_id' => $product->id,
                'warehouse_id' => $validated['warehouse_id'] ?? null,
                'type' => $validated['type'],
                'quantity' => $qty,
                'notes' => $validated['notes'],
                'created_by' => $request->user()->id,
            ]);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'ADJUST_STOCK',
                'module' => 'Inventory',
                'description' => "Adjusted stock for {$product->sku}: {$validated['type']} {$qty} units",
            ]);
        });

        return redirect()->route('inventory.index')->with('success', 'Stock movement recorded successfully.');
    }
}
