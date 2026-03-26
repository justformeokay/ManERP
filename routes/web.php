<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\BomController;
use App\Http\Controllers\ManufacturingOrderController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::resource('clients', ClientController::class)->except(['show']);
Route::resource('projects', ProjectController::class);

// Inventory / Warehouse
Route::prefix('inventory')->name('inventory.')->group(function () {
    Route::resource('products', ProductController::class)->except(['show']);
    Route::get('stocks', [StockController::class, 'index'])->name('stocks.index');
    Route::resource('movements', StockMovementController::class)->only(['index', 'create', 'store']);
});

// Manufacturing
Route::prefix('manufacturing')->name('manufacturing.')->group(function () {
    Route::resource('boms', BomController::class);
    Route::resource('orders', ManufacturingOrderController::class);
    Route::post('orders/{order}/produce', [ManufacturingOrderController::class, 'produce'])->name('orders.produce');
});

// Sales
Route::prefix('sales')->name('sales.')->group(function () {
    Route::get('/', [SalesOrderController::class, 'index'])->name('index');
    Route::get('/create', [SalesOrderController::class, 'create'])->name('create');
    Route::post('/', [SalesOrderController::class, 'store'])->name('store');
    Route::get('/{order}', [SalesOrderController::class, 'show'])->name('show');
    Route::get('/{order}/edit', [SalesOrderController::class, 'edit'])->name('edit');
    Route::put('/{order}', [SalesOrderController::class, 'update'])->name('update');
    Route::post('/{order}/confirm', [SalesOrderController::class, 'confirm'])->name('confirm');
    Route::delete('/{order}', [SalesOrderController::class, 'destroy'])->name('destroy');
});

// Purchasing
Route::prefix('purchasing')->name('purchasing.')->group(function () {
    Route::get('/', [PurchaseOrderController::class, 'index'])->name('index');
    Route::get('/create', [PurchaseOrderController::class, 'create'])->name('create');
    Route::post('/', [PurchaseOrderController::class, 'store'])->name('store');
    Route::get('/{order}', [PurchaseOrderController::class, 'show'])->name('show');
    Route::get('/{order}/edit', [PurchaseOrderController::class, 'edit'])->name('edit');
    Route::put('/{order}', [PurchaseOrderController::class, 'update'])->name('update');
    Route::post('/{order}/confirm', [PurchaseOrderController::class, 'confirm'])->name('confirm');
    Route::post('/{order}/receive', [PurchaseOrderController::class, 'receive'])->name('receive');
    Route::delete('/{order}', [PurchaseOrderController::class, 'destroy'])->name('destroy');
});

// Reports
Route::prefix('reports')->name('reports.')->group(function () {
    Route::get('/', [ReportController::class, 'index'])->name('index');
    Route::get('/sales', [ReportController::class, 'salesReport'])->name('sales');
    Route::get('/purchasing', [ReportController::class, 'purchasingReport'])->name('purchasing');
    Route::get('/inventory', [ReportController::class, 'inventoryReport'])->name('inventory');
    Route::get('/manufacturing', [ReportController::class, 'manufacturingReport'])->name('manufacturing');
    Route::get('/export', [ReportController::class, 'export'])->name('export');
});

// Settings
Route::prefix('settings')->name('settings.')->group(function () {
    Route::get('/', [SettingsController::class, 'index'])->name('index');
    Route::put('/', [SettingsController::class, 'update'])->name('update');
    Route::resource('users', UserController::class)->except(['show']);
});

// Placeholder logout route (replace with auth package later)
Route::post('/logout', function () {
    return redirect('/');
})->name('logout');
