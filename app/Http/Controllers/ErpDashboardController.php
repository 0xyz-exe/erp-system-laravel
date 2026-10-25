<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\ErpProduct;
use App\Models\FinancialAccount;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use Illuminate\View\View;

class ErpDashboardController extends Controller
{
    public function index(): View
    {
        $lowStockProducts = ErpProduct::whereColumn('current_stock', '<=', 'min_stock_threshold')->get();
        $pendingPOs = PurchaseOrder::whereIn('status', ['draft', 'ordered'])->with('supplier')->latest()->get();
        $openSalesOrders = SalesOrder::whereIn('status', ['draft', 'confirmed'])->with('customer')->latest()->get();
        $totalEmployees = Employee::where('status', 'active')->count();
        $accounts = FinancialAccount::all();
        $recentAudits = AuditLog::with('user')->latest()->take(8)->get();

        $metrics = [
            'total_products' => ErpProduct::count(),
            'low_stock_count' => $lowStockProducts->count(),
            'pending_pos_count' => $pendingPOs->count(),
            'open_sales_count' => $openSalesOrders->count(),
            'total_employees' => $totalEmployees,
            'net_cash_balance_cents' => (int) $accounts->where('type', 'asset')->sum('balance_cents'),
        ];

        return view('dashboard', compact('metrics', 'lowStockProducts', 'pendingPOs', 'openSalesOrders', 'accounts', 'recentAudits'));
    }
}
