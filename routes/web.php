<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\BomController;
use App\Http\Controllers\ManufacturingOrderController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes (All ERP modules)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile Management
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // CRM
    Route::resource('clients', ClientController::class)->except(['show']);

    // Master Data
    Route::resource('warehouses', WarehouseController::class)->except(['show']);
    Route::resource('suppliers', SupplierController::class)->except(['show']);
    
    // Projects
    Route::resource('projects', ProjectController::class);

    // Inventory / Warehouse
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::resource('categories', CategoryController::class)->except(['show']);
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
        Route::get('/{salesOrder}', [SalesOrderController::class, 'show'])->name('show');
        Route::get('/{salesOrder}/edit', [SalesOrderController::class, 'edit'])->name('edit');
        Route::put('/{salesOrder}', [SalesOrderController::class, 'update'])->name('update');
        Route::delete('/{salesOrder}', [SalesOrderController::class, 'destroy'])->name('destroy');
        Route::post('/{salesOrder}/confirm', [SalesOrderController::class, 'confirm'])->name('confirm');
        Route::post('/{salesOrder}/deliver', [SalesOrderController::class, 'deliver'])->name('deliver');
        Route::post('/{salesOrder}/invoice', [SalesOrderController::class, 'invoice'])->name('invoice');
        Route::post('/{salesOrder}/cancel', [SalesOrderController::class, 'cancel'])->name('cancel');
    });

    // Purchasing
    Route::prefix('purchasing')->name('purchasing.')->group(function () {
        Route::get('/', [PurchaseOrderController::class, 'index'])->name('index');
        Route::get('/create', [PurchaseOrderController::class, 'create'])->name('create');
        Route::post('/', [PurchaseOrderController::class, 'store'])->name('store');
        Route::get('/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('show');
        Route::get('/{purchaseOrder}/edit', [PurchaseOrderController::class, 'edit'])->name('edit');
        Route::put('/{purchaseOrder}', [PurchaseOrderController::class, 'update'])->name('update');
        Route::delete('/{purchaseOrder}', [PurchaseOrderController::class, 'destroy'])->name('destroy');
        Route::post('/{purchaseOrder}/confirm', [PurchaseOrderController::class, 'confirm'])->name('confirm');
        Route::post('/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])->name('receive');
        Route::post('/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->name('cancel');
    });

    // Reports (accessible by all authenticated users)
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
        Route::get('/purchasing', [ReportController::class, 'purchasing'])->name('purchasing');
        Route::get('/inventory', [ReportController::class, 'inventory'])->name('inventory');
        Route::get('/manufacturing', [ReportController::class, 'manufacturing'])->name('manufacturing');
    });

    /*
    |--------------------------------------------------------------------------
    | Admin Only Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware(['admin'])->group(function () {
        
        // Settings
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [SettingsController::class, 'index'])->name('index');
            Route::post('/', [SettingsController::class, 'update'])->name('update');
            
            // User Management
            Route::resource('users', UserController::class)->except(['show']);
        });
    });
});

require __DIR__.'/auth.php';
