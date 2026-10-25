<?php

use App\Http\Controllers\ErpDashboardController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\HrController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchasingController;
use App\Http\Controllers\SalesController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [ErpDashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Inventory
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory/products', [InventoryController::class, 'storeProduct'])->name('inventory.products.store');
    Route::post('/inventory/products/{product}/adjust', [InventoryController::class, 'adjustStock'])->name('inventory.products.adjust');

    // Purchasing
    Route::get('/purchasing', [PurchasingController::class, 'index'])->name('purchasing.index');
    Route::post('/purchasing/orders', [PurchasingController::class, 'storeOrder'])->name('purchasing.orders.store');
    Route::post('/purchasing/orders/{order}/receive', [PurchasingController::class, 'receiveGoods'])->name('purchasing.orders.receive');

    // Sales
    Route::get('/sales', [SalesController::class, 'index'])->name('sales.index');
    Route::post('/sales/orders', [SalesController::class, 'storeOrder'])->name('sales.orders.store');
    Route::post('/sales/orders/{order}/fulfill', [SalesController::class, 'fulfillOrder'])->name('sales.orders.fulfill');

    // Finance
    Route::get('/finance', [FinanceController::class, 'index'])->name('finance.index');
    Route::post('/finance/transactions', [FinanceController::class, 'storeTransaction'])->name('finance.transactions.store');

    // HR
    Route::get('/hr', [HrController::class, 'index'])->name('hr.index');
    Route::post('/hr/employees', [HrController::class, 'storeEmployee'])->name('hr.employees.store');
});

require __DIR__.'/auth.php';
