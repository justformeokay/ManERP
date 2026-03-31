<?php

use App\Http\Controllers\AccountingReportController;
use App\Http\Controllers\AccountsPayableController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ChartOfAccountController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\BomController;
use App\Http\Controllers\ManufacturingOrderController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\JournalEntryController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\PDFController;
use App\Http\Controllers\ApprovalController;
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
| Language Switcher (accessible to all)
|--------------------------------------------------------------------------
*/
Route::get('/lang/{locale}', [LanguageController::class, 'switch'])->name('language.switch');

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

    // Notifications
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('readAll');
    });

    // CRM - Clients
    Route::prefix('clients')->name('clients.')->group(function () {
        Route::get('/', [ClientController::class, 'index'])->name('index')->middleware('permission:clients.view');
        Route::get('/create', [ClientController::class, 'create'])->name('create')->middleware('permission:clients.create');
        Route::post('/', [ClientController::class, 'store'])->name('store')->middleware('permission:clients.create');
        Route::get('/{client}/edit', [ClientController::class, 'edit'])->name('edit')->middleware('permission:clients.edit');
        Route::put('/{client}', [ClientController::class, 'update'])->name('update')->middleware('permission:clients.edit');
        Route::delete('/{client}', [ClientController::class, 'destroy'])->name('destroy')->middleware('permission:clients.delete');
    });

    // Master Data - Warehouses
    Route::prefix('warehouses')->name('warehouses.')->group(function () {
        Route::get('/', [WarehouseController::class, 'index'])->name('index')->middleware('permission:warehouses.view');
        Route::get('/create', [WarehouseController::class, 'create'])->name('create')->middleware('permission:warehouses.create');
        Route::post('/', [WarehouseController::class, 'store'])->name('store')->middleware('permission:warehouses.create');
        Route::get('/{warehouse}/edit', [WarehouseController::class, 'edit'])->name('edit')->middleware('permission:warehouses.edit');
        Route::put('/{warehouse}', [WarehouseController::class, 'update'])->name('update')->middleware('permission:warehouses.edit');
        Route::delete('/{warehouse}', [WarehouseController::class, 'destroy'])->name('destroy')->middleware('permission:warehouses.delete');
    });

    // Master Data - Suppliers
    Route::prefix('suppliers')->name('suppliers.')->group(function () {
        Route::get('/', [SupplierController::class, 'index'])->name('index')->middleware('permission:suppliers.view');
        Route::get('/create', [SupplierController::class, 'create'])->name('create')->middleware('permission:suppliers.create');
        Route::post('/', [SupplierController::class, 'store'])->name('store')->middleware('permission:suppliers.create');
        Route::get('/{supplier}/edit', [SupplierController::class, 'edit'])->name('edit')->middleware('permission:suppliers.edit');
        Route::put('/{supplier}', [SupplierController::class, 'update'])->name('update')->middleware('permission:suppliers.edit');
        Route::delete('/{supplier}', [SupplierController::class, 'destroy'])->name('destroy')->middleware('permission:suppliers.delete');
    });
    
    // Projects
    Route::prefix('projects')->name('projects.')->group(function () {
        Route::get('/', [ProjectController::class, 'index'])->name('index')->middleware('permission:projects.view');
        Route::get('/create', [ProjectController::class, 'create'])->name('create')->middleware('permission:projects.create');
        Route::post('/', [ProjectController::class, 'store'])->name('store')->middleware('permission:projects.create');
        Route::get('/{project}', [ProjectController::class, 'show'])->name('show')->middleware('permission:projects.view');
        Route::get('/{project}/edit', [ProjectController::class, 'edit'])->name('edit')->middleware('permission:projects.edit');
        Route::put('/{project}', [ProjectController::class, 'update'])->name('update')->middleware('permission:projects.edit');
        Route::delete('/{project}', [ProjectController::class, 'destroy'])->name('destroy')->middleware('permission:projects.delete');
    });

    // Inventory / Warehouse
    Route::prefix('inventory')->name('inventory.')->group(function () {
        // Categories
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index')->middleware('permission:inventory.view');
        Route::get('/categories/create', [CategoryController::class, 'create'])->name('categories.create')->middleware('permission:inventory.create');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store')->middleware('permission:inventory.create');
        Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit')->middleware('permission:inventory.edit');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update')->middleware('permission:inventory.edit');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy')->middleware('permission:inventory.delete');
        
        // Products
        Route::get('/products', [ProductController::class, 'index'])->name('products.index')->middleware('permission:inventory.view');
        Route::get('/products/create', [ProductController::class, 'create'])->name('products.create')->middleware('permission:inventory.create');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store')->middleware('permission:inventory.create');
        Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit')->middleware('permission:inventory.edit');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update')->middleware('permission:inventory.edit');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy')->middleware('permission:inventory.delete');
        
        // Stock
        Route::get('stocks', [StockController::class, 'index'])->name('stocks.index')->middleware('permission:inventory.view');
        
        // Stock Movements
        Route::get('/movements', [StockMovementController::class, 'index'])->name('movements.index')->middleware('permission:inventory.view');
        Route::get('/movements/create', [StockMovementController::class, 'create'])->name('movements.create')->middleware('permission:inventory.create');
        Route::post('/movements', [StockMovementController::class, 'store'])->name('movements.store')->middleware('permission:inventory.create');
        
        // Stock Transfers
        Route::get('/transfers', [StockTransferController::class, 'index'])->name('transfers.index')->middleware('permission:inventory.view');
        Route::get('/transfers/create', [StockTransferController::class, 'create'])->name('transfers.create')->middleware('permission:inventory.create');
        Route::post('/transfers', [StockTransferController::class, 'store'])->name('transfers.store')->middleware('permission:inventory.create');
        Route::delete('/transfers/{transfer}', [StockTransferController::class, 'destroy'])->name('transfers.destroy')->middleware('permission:inventory.delete');
        Route::post('transfers/{transfer}/execute', [StockTransferController::class, 'execute'])->name('transfers.execute')->middleware('permission:inventory.edit');
        Route::post('transfers/{transfer}/cancel', [StockTransferController::class, 'cancel'])->name('transfers.cancel')->middleware('permission:inventory.edit');
    });

    // Manufacturing
    Route::prefix('manufacturing')->name('manufacturing.')->group(function () {
        // BOMs
        Route::get('/boms', [BomController::class, 'index'])->name('boms.index')->middleware('permission:manufacturing.view');
        Route::get('/boms/create', [BomController::class, 'create'])->name('boms.create')->middleware('permission:manufacturing.create');
        Route::post('/boms', [BomController::class, 'store'])->name('boms.store')->middleware('permission:manufacturing.create');
        Route::get('/boms/{bom}', [BomController::class, 'show'])->name('boms.show')->middleware('permission:manufacturing.view');
        Route::get('/boms/{bom}/edit', [BomController::class, 'edit'])->name('boms.edit')->middleware('permission:manufacturing.edit');
        Route::put('/boms/{bom}', [BomController::class, 'update'])->name('boms.update')->middleware('permission:manufacturing.edit');
        Route::delete('/boms/{bom}', [BomController::class, 'destroy'])->name('boms.destroy')->middleware('permission:manufacturing.delete');
        
        // Manufacturing Orders
        Route::get('/orders', [ManufacturingOrderController::class, 'index'])->name('orders.index')->middleware('permission:manufacturing.view');
        Route::get('/orders/create', [ManufacturingOrderController::class, 'create'])->name('orders.create')->middleware('permission:manufacturing.create');
        Route::post('/orders', [ManufacturingOrderController::class, 'store'])->name('orders.store')->middleware('permission:manufacturing.create');
        Route::get('/orders/{order}', [ManufacturingOrderController::class, 'show'])->name('orders.show')->middleware('permission:manufacturing.view');
        Route::get('/orders/{order}/edit', [ManufacturingOrderController::class, 'edit'])->name('orders.edit')->middleware('permission:manufacturing.edit');
        Route::put('/orders/{order}', [ManufacturingOrderController::class, 'update'])->name('orders.update')->middleware('permission:manufacturing.edit');
        Route::delete('/orders/{order}', [ManufacturingOrderController::class, 'destroy'])->name('orders.destroy')->middleware('permission:manufacturing.delete');
        Route::post('orders/{order}/confirm', [ManufacturingOrderController::class, 'confirm'])->name('orders.confirm')->middleware('permission:manufacturing.edit');
        Route::post('orders/{order}/produce', [ManufacturingOrderController::class, 'produce'])->name('orders.produce')->middleware('permission:manufacturing.edit');
    });

    // Sales
    Route::prefix('sales')->name('sales.')->group(function () {
        Route::get('/', [SalesOrderController::class, 'index'])->name('index')->middleware('permission:sales.view');
        Route::get('/create', [SalesOrderController::class, 'create'])->name('create')->middleware('permission:sales.create');
        Route::post('/', [SalesOrderController::class, 'store'])->name('store')->middleware('permission:sales.create');
        Route::get('/{salesOrder}', [SalesOrderController::class, 'show'])->name('show')->middleware('permission:sales.view');
        Route::get('/{salesOrder}/edit', [SalesOrderController::class, 'edit'])->name('edit')->middleware('permission:sales.edit');
        Route::put('/{salesOrder}', [SalesOrderController::class, 'update'])->name('update')->middleware('permission:sales.edit');
        Route::delete('/{salesOrder}', [SalesOrderController::class, 'destroy'])->name('destroy')->middleware('permission:sales.delete');
        Route::post('/{salesOrder}/confirm', [SalesOrderController::class, 'confirm'])->name('confirm')->middleware('permission:sales.edit');
        Route::post('/{salesOrder}/deliver', [SalesOrderController::class, 'deliver'])->name('deliver')->middleware('permission:sales.edit');
        Route::post('/{salesOrder}/invoice', [SalesOrderController::class, 'invoice'])->name('invoice')->middleware('permission:sales.edit');
        Route::post('/{salesOrder}/cancel', [SalesOrderController::class, 'cancel'])->name('cancel')->middleware('permission:sales.delete');
    });

    // Purchasing
    Route::prefix('purchasing')->name('purchasing.')->group(function () {
        Route::get('/', [PurchaseOrderController::class, 'index'])->name('index')->middleware('permission:purchasing.view');
        Route::get('/create', [PurchaseOrderController::class, 'create'])->name('create')->middleware('permission:purchasing.create');
        Route::post('/', [PurchaseOrderController::class, 'store'])->name('store')->middleware('permission:purchasing.create');
        Route::get('/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('show')->middleware('permission:purchasing.view');
        Route::get('/{purchaseOrder}/edit', [PurchaseOrderController::class, 'edit'])->name('edit')->middleware('permission:purchasing.edit');
        Route::put('/{purchaseOrder}', [PurchaseOrderController::class, 'update'])->name('update')->middleware('permission:purchasing.edit');
        Route::delete('/{purchaseOrder}', [PurchaseOrderController::class, 'destroy'])->name('destroy')->middleware('permission:purchasing.delete');
        Route::post('/{purchaseOrder}/confirm', [PurchaseOrderController::class, 'confirm'])->name('confirm')->middleware('permission:purchasing.edit');
        Route::post('/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])->name('receive')->middleware('permission:purchasing.edit');
        Route::post('/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->name('cancel')->middleware('permission:purchasing.delete');
    });

    // Finance - Invoices & Payments
    Route::prefix('finance')->name('finance.')->group(function () {
        Route::prefix('invoices')->name('invoices.')->group(function () {
            Route::get('/', [InvoiceController::class, 'index'])->name('index')->middleware('permission:finance.view');
            Route::get('/create', [InvoiceController::class, 'create'])->name('create')->middleware('permission:finance.create');
            Route::post('/', [InvoiceController::class, 'store'])->name('store')->middleware('permission:finance.create');
            Route::get('/{invoice}', [InvoiceController::class, 'show'])->name('show')->middleware('permission:finance.view');
            Route::post('/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('cancel')->middleware('permission:finance.delete');
        });

        Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store')->middleware('permission:finance.create');
    });

    // Accounts Payable (AP)
    Route::prefix('ap')->name('ap.')->middleware('permission:finance.view')->group(function () {
        // Bills
        Route::prefix('bills')->name('bills.')->group(function () {
            Route::get('/', [AccountsPayableController::class, 'index'])->name('index');
            Route::get('/create', [AccountsPayableController::class, 'create'])->name('create')->middleware('permission:finance.create');
            Route::post('/', [AccountsPayableController::class, 'store'])->name('store')->middleware('permission:finance.create');
            Route::get('/{bill}', [AccountsPayableController::class, 'show'])->name('show');
            Route::get('/{bill}/edit', [AccountsPayableController::class, 'edit'])->name('edit')->middleware('permission:finance.edit');
            Route::put('/{bill}', [AccountsPayableController::class, 'update'])->name('update')->middleware('permission:finance.edit');
            Route::delete('/{bill}', [AccountsPayableController::class, 'destroy'])->name('destroy')->middleware('permission:finance.delete');
            Route::post('/{bill}/post', [AccountsPayableController::class, 'post'])->name('post')->middleware('permission:finance.edit');
            Route::post('/{bill}/cancel', [AccountsPayableController::class, 'cancel'])->name('cancel')->middleware('permission:finance.delete');
            Route::get('/{bill}/pay', [AccountsPayableController::class, 'paymentCreate'])->name('pay')->middleware('permission:finance.create');
            Route::post('/{bill}/pay', [AccountsPayableController::class, 'paymentStore'])->name('pay.store')->middleware('permission:finance.create');
        });
        
        // Payments
        Route::get('/payments', [AccountsPayableController::class, 'payments'])->name('payments.index');
        
        // Reports
        Route::get('/aging', [AccountsPayableController::class, 'agingReport'])->name('aging');
    });

    // Accounting - Chart of Accounts, Journal Entries, Ledger, Trial Balance
    Route::prefix('accounting')->name('accounting.')->group(function () {
        Route::prefix('coa')->name('coa.')->group(function () {
            Route::get('/', [ChartOfAccountController::class, 'index'])->name('index')->middleware('permission:accounting.view');
            Route::get('/create', [ChartOfAccountController::class, 'create'])->name('create')->middleware('permission:accounting.create');
            Route::post('/', [ChartOfAccountController::class, 'store'])->name('store')->middleware('permission:accounting.create');
            Route::get('/{account}/edit', [ChartOfAccountController::class, 'edit'])->name('edit')->middleware('permission:accounting.edit');
            Route::put('/{account}', [ChartOfAccountController::class, 'update'])->name('update')->middleware('permission:accounting.edit');
            Route::delete('/{account}', [ChartOfAccountController::class, 'destroy'])->name('destroy')->middleware('permission:accounting.delete');
        });

        Route::prefix('journals')->name('journals.')->group(function () {
            Route::get('/', [JournalEntryController::class, 'index'])->name('index')->middleware('permission:accounting.view');
            Route::get('/create', [JournalEntryController::class, 'create'])->name('create')->middleware('permission:accounting.create');
            Route::post('/', [JournalEntryController::class, 'store'])->name('store')->middleware('permission:accounting.create');
            Route::get('/{journal}', [JournalEntryController::class, 'show'])->name('show')->middleware('permission:accounting.view');
        });

        Route::get('/ledger', [AccountingReportController::class, 'ledger'])->name('ledger')->middleware('permission:accounting.view');
        Route::get('/trial-balance', [AccountingReportController::class, 'trialBalance'])->name('trial-balance')->middleware('permission:accounting.view');
        Route::get('/balance-sheet', [FinancialReportController::class, 'balanceSheet'])->name('balance-sheet')->middleware('permission:accounting.view');
        Route::get('/profit-loss', [FinancialReportController::class, 'profitLoss'])->name('profit-loss')->middleware('permission:accounting.view');
    });

    // Reports
    Route::prefix('reports')->name('reports.')->middleware('permission:reports.view')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/sales', [ReportController::class, 'salesReport'])->name('sales');
        Route::get('/purchasing', [ReportController::class, 'purchasingReport'])->name('purchasing');
        Route::get('/inventory', [ReportController::class, 'inventoryReport'])->name('inventory');
        Route::get('/manufacturing', [ReportController::class, 'manufacturingReport'])->name('manufacturing');
        Route::get('/finance', [ReportController::class, 'financeReport'])->name('finance');
        Route::get('/export', [ReportController::class, 'export'])->name('export');
    });

    // PDF Generation
    Route::prefix('pdf')->name('pdf.')->group(function () {
        Route::get('/invoice/{id}', [PDFController::class, 'invoice'])->name('invoice')->middleware('permission:finance.view');
        Route::get('/po/{id}', [PDFController::class, 'purchaseOrder'])->name('po')->middleware('permission:inventory.view');
        Route::get('/bill/{id}', [PDFController::class, 'supplierBill'])->name('bill')->middleware('permission:finance.view');
    });

    // Approval Workflow
    Route::prefix('approvals')->name('approvals.')->group(function () {
        Route::get('/', [ApprovalController::class, 'index'])->name('index');
        Route::get('/{approval}', [ApprovalController::class, 'show'])->name('show');
        Route::post('/{approval}/approve', [ApprovalController::class, 'approve'])->name('approve');
        Route::post('/{approval}/reject', [ApprovalController::class, 'reject'])->name('reject');
        Route::post('/{approval}/cancel', [ApprovalController::class, 'cancel'])->name('cancel');
        Route::post('/{approval}/resubmit', [ApprovalController::class, 'resubmit'])->name('resubmit');
        
        // Admin: Flow Configuration
        Route::get('/admin/flows', [ApprovalController::class, 'flows'])->name('flows')->middleware('admin');
        Route::get('/admin/flows/{flow}/edit', [ApprovalController::class, 'editFlow'])->name('flows.edit')->middleware('admin');
        Route::put('/admin/flows/{flow}', [ApprovalController::class, 'updateFlow'])->name('flows.update')->middleware('admin');
        
        // Admin: Role Management
        Route::get('/admin/roles', [ApprovalController::class, 'roles'])->name('roles')->middleware('admin');
        Route::get('/admin/roles/{role}/edit', [ApprovalController::class, 'editRole'])->name('roles.edit')->middleware('admin');
        Route::put('/admin/roles/{role}', [ApprovalController::class, 'updateRole'])->name('roles.update')->middleware('admin');
    });

    /*
    |--------------------------------------------------------------------------
    | Admin Only Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware(['admin'])->group(function () {
        
        // Audit Logs
        Route::prefix('audit-logs')->name('audit-logs.')->group(function () {
            Route::get('/', [AuditLogController::class, 'index'])->name('index');
            Route::get('/{activityLog}', [AuditLogController::class, 'show'])->name('show');
        });

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
