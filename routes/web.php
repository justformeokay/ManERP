<?php

use App\Http\Controllers\AccountingReportController;
use App\Http\Controllers\AccountsPayableController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\EssController;
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
use App\Http\Controllers\StockValuationController;
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
use App\Http\Controllers\QcParameterController;
use App\Http\Controllers\QcInspectionController;
use App\Http\Controllers\CostingController;
use App\Http\Controllers\PurchaseRequestController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\FiscalPeriodController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\BankReconciliationController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\FixedAssetController;
use App\Http\Controllers\CreditNoteController;
use App\Http\Controllers\DebitNoteController;
use App\Http\Controllers\FinancialRatioController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\SalaryStructureController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\SystemMaintenanceController;
use App\Http\Controllers\UserGuideController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\AboutController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\LicenseController;
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

    // License Expired (outside admin group so all users can see it)
    Route::get('/license/expired', [LicenseController::class, 'expired'])->name('license.expired');
    Route::get('/dashboard/api', [DashboardController::class, 'apiData'])->name('dashboard.api');

    // Profile Management
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Employee Self-Service (ESS) — Profile Sub-routes
    Route::prefix('profile/ess')->name('profile.ess.')->group(function () {
        Route::post('/document/upload', [EssController::class, 'uploadDocument'])->name('document.upload');
        Route::get('/document/{document}/view', [EssController::class, 'viewDocument'])->name('document.view');
        Route::delete('/document/{document}', [EssController::class, 'deleteDocument'])->name('document.delete');
        Route::post('/data-change', [EssController::class, 'requestDataChange'])->name('data-change');
    });

    // ESS — HR Approval Routes (admin/HR only)
    Route::prefix('ess-admin')->name('ess-admin.')->middleware('permission:hr.edit')->group(function () {
        Route::post('/change/{change}/approve', [EssController::class, 'approveChange'])->name('change.approve');
        Route::post('/change/{change}/reject', [EssController::class, 'rejectChange'])->name('change.reject');
        Route::post('/document/{document}/verify', [EssController::class, 'verifyDocument'])->name('document.verify');
    });

    // Impersonation (Role Simulation — Phase 7)
    Route::post('/impersonate/{user}/start', [ImpersonationController::class, 'start'])->name('impersonate.start')->middleware('permission:admin.impersonate');
    Route::post('/impersonate/stop', [ImpersonationController::class, 'stop'])->name('impersonate.stop');

    // Two-Factor Authentication Management
    Route::get('/two-factor/setup', [TwoFactorController::class, 'setup'])->name('two-factor.setup');
    Route::post('/two-factor/enable', [TwoFactorController::class, 'enable'])->name('two-factor.enable');
    Route::delete('/two-factor/disable', [TwoFactorController::class, 'disable'])->name('two-factor.disable');

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
        Route::get('/{client}', [ClientController::class, 'show'])->name('show')->middleware('permission:clients.view');
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
        Route::get('stock', [StockController::class, 'hub'])->name('stock.index')->middleware('permission:inventory.view');
        
        // Stock Movements
        Route::get('/movements', [StockMovementController::class, 'index'])->name('movements.index')->middleware('permission:inventory.view');
        Route::get('/movements/create', [StockMovementController::class, 'create'])->name('movements.create')->middleware('permission:inventory.create');
        Route::post('/movements', [StockMovementController::class, 'store'])->name('movements.store')->middleware(['permission:inventory.create', 'fiscal-lock']);
        
        // Stock Transfers
        Route::get('/transfers', [StockTransferController::class, 'index'])->name('transfers.index')->middleware('permission:inventory.view');
        Route::get('/transfers/create', [StockTransferController::class, 'create'])->name('transfers.create')->middleware('permission:inventory.create');
        Route::post('/transfers', [StockTransferController::class, 'store'])->name('transfers.store')->middleware('permission:inventory.create');
        Route::delete('/transfers/{transfer}', [StockTransferController::class, 'destroy'])->name('transfers.destroy')->middleware('permission:inventory.delete');
        Route::post('transfers/{transfer}/execute', [StockTransferController::class, 'execute'])->name('transfers.execute')->middleware(['permission:inventory.edit', 'fiscal-lock']);
        Route::post('transfers/{transfer}/cancel', [StockTransferController::class, 'cancel'])->name('transfers.cancel')->middleware(['permission:inventory.edit', 'fiscal-lock']);

        // Stock Valuation (WAC Report)
        Route::get('/valuation', [StockValuationController::class, 'index'])->name('valuation.index')->middleware('permission:inventory.view');
        Route::get('/valuation/{product}', [StockValuationController::class, 'show'])->name('valuation.show')->middleware('permission:inventory.view');
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
        Route::post('/boms/{bom}/new-version', [BomController::class, 'newVersion'])->name('boms.new-version')->middleware('permission:manufacturing.create');
        // Manufacturing Orders
        Route::get('/orders', [ManufacturingOrderController::class, 'index'])->name('orders.index')->middleware('permission:manufacturing.view');
        Route::get('/orders/create', [ManufacturingOrderController::class, 'create'])->name('orders.create')->middleware('permission:manufacturing.create');
        Route::post('/orders', [ManufacturingOrderController::class, 'store'])->name('orders.store')->middleware('permission:manufacturing.create');
        Route::get('/orders/{order}', [ManufacturingOrderController::class, 'show'])->name('orders.show')->middleware('permission:manufacturing.view');
        Route::get('/orders/{order}/edit', [ManufacturingOrderController::class, 'edit'])->name('orders.edit')->middleware('permission:manufacturing.edit');
        Route::put('/orders/{order}', [ManufacturingOrderController::class, 'update'])->name('orders.update')->middleware('permission:manufacturing.edit');
        Route::delete('/orders/{order}', [ManufacturingOrderController::class, 'destroy'])->name('orders.destroy')->middleware('permission:manufacturing.delete');
        Route::post('orders/{order}/confirm', [ManufacturingOrderController::class, 'confirm'])->name('orders.confirm')->middleware('permission:manufacturing.edit');
        Route::post('orders/{order}/produce', [ManufacturingOrderController::class, 'produce'])->name('orders.produce')->middleware(['permission:manufacturing.edit', 'fiscal-lock']);

        // Costing / HPP
        Route::get('/costing', [CostingController::class, 'index'])->name('costing.index')->middleware('permission:manufacturing.view');
        Route::get('/costing/simulate', [CostingController::class, 'simulateForm'])->name('costing.simulate-form')->middleware('permission:manufacturing.view');
        Route::post('/costing/simulate', [CostingController::class, 'simulate'])->name('costing.simulate')->middleware('permission:manufacturing.view');
        Route::get('/costing/{order}', [CostingController::class, 'show'])->name('costing.show')->middleware('permission:manufacturing.view');
        Route::post('/costing/{order}/recalculate', [CostingController::class, 'recalculate'])->name('costing.recalculate')->middleware('permission:manufacturing.edit');
        Route::post('/costing/standard-cost/{product}', [CostingController::class, 'updateStandardCost'])->name('costing.update-standard-cost')->middleware('permission:manufacturing.edit');
    });

    // Quality Control
    Route::prefix('qc')->name('qc.')->group(function () {
        // QC Parameters
        Route::prefix('parameters')->name('parameters.')->group(function () {
            Route::get('/', [QcParameterController::class, 'index'])->name('index')->middleware('permission:manufacturing.view');
            Route::get('/create', [QcParameterController::class, 'create'])->name('create')->middleware('permission:manufacturing.create');
            Route::post('/', [QcParameterController::class, 'store'])->name('store')->middleware('permission:manufacturing.create');
            Route::get('/{parameter}/edit', [QcParameterController::class, 'edit'])->name('edit')->middleware('permission:manufacturing.edit');
            Route::put('/{parameter}', [QcParameterController::class, 'update'])->name('update')->middleware('permission:manufacturing.edit');
            Route::delete('/{parameter}', [QcParameterController::class, 'destroy'])->name('destroy')->middleware('permission:manufacturing.delete');
        });

        // QC Inspections
        Route::prefix('inspections')->name('inspections.')->group(function () {
            Route::get('/', [QcInspectionController::class, 'index'])->name('index')->middleware('permission:manufacturing.view');
            Route::get('/create', [QcInspectionController::class, 'create'])->name('create')->middleware('permission:manufacturing.create');
            Route::post('/', [QcInspectionController::class, 'store'])->name('store')->middleware('permission:manufacturing.create');
            Route::get('/{inspection}', [QcInspectionController::class, 'show'])->name('show')->middleware('permission:manufacturing.view');
            Route::get('/{inspection}/edit', [QcInspectionController::class, 'edit'])->name('edit')->middleware('permission:manufacturing.edit');
            Route::put('/{inspection}', [QcInspectionController::class, 'update'])->name('update')->middleware('permission:manufacturing.edit');
            Route::post('/{inspection}/record-results', [QcInspectionController::class, 'recordResults'])->name('record-results')->middleware('permission:manufacturing.edit');
            Route::delete('/{inspection}', [QcInspectionController::class, 'destroy'])->name('destroy')->middleware('permission:manufacturing.delete');
        });
    });

    // Purchase Requests
    Route::prefix('purchase-requests')->name('purchase-requests.')->group(function () {
        Route::get('/', [PurchaseRequestController::class, 'index'])->name('index')->middleware('permission:purchasing.view');
        Route::get('/create', [PurchaseRequestController::class, 'create'])->name('create')->middleware('permission:purchasing.create');
        Route::post('/', [PurchaseRequestController::class, 'store'])->name('store')->middleware('permission:purchasing.create');
        Route::get('/{purchaseRequest}', [PurchaseRequestController::class, 'show'])->name('show')->middleware('permission:purchasing.view');
        Route::get('/{purchaseRequest}/edit', [PurchaseRequestController::class, 'edit'])->name('edit')->middleware('permission:purchasing.edit');
        Route::put('/{purchaseRequest}', [PurchaseRequestController::class, 'update'])->name('update')->middleware('permission:purchasing.edit');
        Route::delete('/{purchaseRequest}', [PurchaseRequestController::class, 'destroy'])->name('destroy')->middleware('permission:purchasing.delete');
        Route::post('/{purchaseRequest}/submit', [PurchaseRequestController::class, 'submit'])->name('submit')->middleware('permission:purchasing.edit');
        Route::post('/{purchaseRequest}/approve', [PurchaseRequestController::class, 'approve'])->name('approve')->middleware('permission:purchasing.edit');
        Route::post('/{purchaseRequest}/reject', [PurchaseRequestController::class, 'reject'])->name('reject')->middleware('permission:purchasing.edit');
        Route::get('/{purchaseRequest}/convert', [PurchaseRequestController::class, 'convertToPo'])->name('convert')->middleware('permission:purchasing.create');
        Route::post('/{purchaseRequest}/convert', [PurchaseRequestController::class, 'storeConversion'])->name('store-conversion')->middleware('permission:purchasing.create');
    });

    // Sales
    Route::prefix('sales')->name('sales.')->group(function () {
        Route::get('/', [SalesOrderController::class, 'index'])->name('index')->middleware('permission:sales.view');
        Route::get('/create', [SalesOrderController::class, 'create'])->name('create')->middleware('permission:sales.create');
        Route::post('/', [SalesOrderController::class, 'store'])->name('store')->middleware('permission:sales.create');
        Route::get('/{order}', [SalesOrderController::class, 'show'])->name('show')->middleware('permission:sales.view');
        Route::get('/{order}/edit', [SalesOrderController::class, 'edit'])->name('edit')->middleware('permission:sales.edit');
        Route::put('/{order}', [SalesOrderController::class, 'update'])->name('update')->middleware('permission:sales.edit');
        Route::delete('/{order}', [SalesOrderController::class, 'destroy'])->name('destroy')->middleware('permission:sales.delete');
        Route::post('/{order}/confirm', [SalesOrderController::class, 'confirm'])->name('confirm')->middleware(['permission:sales.edit', 'fiscal-lock']);
        Route::post('/{order}/deliver', [SalesOrderController::class, 'deliver'])->name('deliver')->middleware(['permission:sales.edit', 'fiscal-lock']);
        Route::post('/{order}/invoice', [SalesOrderController::class, 'invoice'])->name('invoice')->middleware('permission:sales.edit');
        Route::post('/{order}/cancel', [SalesOrderController::class, 'cancel'])->name('cancel')->middleware(['permission:sales.delete', 'fiscal-lock']);
    });

    // Purchasing
    Route::prefix('purchasing')->name('purchasing.')->group(function () {
        Route::get('/', [PurchaseOrderController::class, 'index'])->name('index')->middleware('permission:purchasing.view');
        Route::get('/create', [PurchaseOrderController::class, 'create'])->name('create')->middleware('permission:purchasing.create');
        Route::post('/', [PurchaseOrderController::class, 'store'])->name('store')->middleware('permission:purchasing.create');
        Route::get('/{order}', [PurchaseOrderController::class, 'show'])->name('show')->middleware('permission:purchasing.view');
        Route::get('/{order}/edit', [PurchaseOrderController::class, 'edit'])->name('edit')->middleware('permission:purchasing.edit');
        Route::put('/{order}', [PurchaseOrderController::class, 'update'])->name('update')->middleware('permission:purchasing.edit');
        Route::delete('/{order}', [PurchaseOrderController::class, 'destroy'])->name('destroy')->middleware('permission:purchasing.delete');
        Route::post('/{order}/confirm', [PurchaseOrderController::class, 'confirm'])->name('confirm')->middleware('permission:purchasing.edit');
        Route::post('/{order}/receive', [PurchaseOrderController::class, 'receive'])->name('receive')->middleware(['permission:purchasing.edit', 'fiscal-lock']);
        Route::post('/{order}/cancel', [PurchaseOrderController::class, 'cancel'])->name('cancel')->middleware(['permission:purchasing.delete', 'fiscal-lock']);
    });

    // Finance - Invoices & Payments
    Route::prefix('finance')->name('finance.')->group(function () {
        Route::prefix('invoices')->name('invoices.')->group(function () {
            Route::get('/', [InvoiceController::class, 'index'])->name('index')->middleware('permission:finance.view');
            Route::get('/create', [InvoiceController::class, 'create'])->name('create')->middleware('permission:finance.create');
            Route::post('/', [InvoiceController::class, 'store'])->name('store')->middleware(['permission:finance.create', 'fiscal-lock:invoice_date']);
            Route::get('/{invoice}', [InvoiceController::class, 'show'])->name('show')->middleware('permission:finance.view');
            Route::post('/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('cancel')->middleware(['permission:finance.delete', 'fiscal-lock']);
        });

        Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store')->middleware(['permission:finance.create', 'fiscal-lock:payment_date']);
    });

    // Accounts Payable (AP)
    Route::prefix('ap')->name('ap.')->middleware('permission:finance.view')->group(function () {
        // Bills
        Route::prefix('bills')->name('bills.')->group(function () {
            Route::get('/', [AccountsPayableController::class, 'index'])->name('index');
            Route::get('/create', [AccountsPayableController::class, 'create'])->name('create')->middleware('permission:finance.create');
            Route::post('/', [AccountsPayableController::class, 'store'])->name('store')->middleware(['permission:finance.create', 'fiscal-lock:bill_date']);
            Route::get('/{bill}', [AccountsPayableController::class, 'show'])->name('show');
            Route::get('/{bill}/edit', [AccountsPayableController::class, 'edit'])->name('edit')->middleware('permission:finance.edit');
            Route::put('/{bill}', [AccountsPayableController::class, 'update'])->name('update')->middleware(['permission:finance.edit', 'fiscal-lock:bill_date']);
            Route::delete('/{bill}', [AccountsPayableController::class, 'destroy'])->name('destroy')->middleware('permission:finance.delete');
            Route::post('/{bill}/post', [AccountsPayableController::class, 'post'])->name('post')->middleware(['permission:finance.edit', 'fiscal-lock']);
            Route::post('/{bill}/cancel', [AccountsPayableController::class, 'cancel'])->name('cancel')->middleware(['permission:finance.delete', 'fiscal-lock']);
            Route::get('/{bill}/pay', [AccountsPayableController::class, 'paymentCreate'])->name('pay')->middleware('permission:finance.create');
            Route::post('/{bill}/pay', [AccountsPayableController::class, 'paymentStore'])->name('pay.store')->middleware(['permission:finance.create', 'fiscal-lock:payment_date']);
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
            Route::post('/', [JournalEntryController::class, 'store'])->name('store')->middleware(['permission:accounting.create', 'fiscal-lock:date']);
            Route::get('/adjusting', [JournalEntryController::class, 'createAdjusting'])->name('adjusting')->middleware('permission:accounting.create');
            Route::post('/adjusting', [JournalEntryController::class, 'storeAdjusting'])->name('store-adjusting')->middleware(['permission:accounting.create', 'fiscal-lock:date']);
            Route::get('/templates', [JournalEntryController::class, 'templates'])->name('templates')->middleware('permission:accounting.view');
            Route::get('/templates/create', [JournalEntryController::class, 'createTemplate'])->name('templates.create')->middleware('permission:accounting.create');
            Route::post('/templates', [JournalEntryController::class, 'storeTemplate'])->name('templates.store')->middleware('permission:accounting.create');
            Route::delete('/templates/{template}', [JournalEntryController::class, 'destroyTemplate'])->name('templates.destroy')->middleware('permission:accounting.delete');
            Route::get('/{journal}', [JournalEntryController::class, 'show'])->name('show')->middleware('permission:accounting.view');
            Route::post('/{journal}/reverse', [JournalEntryController::class, 'reverse'])->name('reverse')->middleware(['permission:accounting.create', 'fiscal-lock:date']);
        });

        // Fiscal Periods (Closing)
        Route::prefix('fiscal-periods')->name('fiscal-periods.')->group(function () {
            Route::get('/', [FiscalPeriodController::class, 'index'])->name('index')->middleware('permission:accounting.view');
            Route::get('/create', [FiscalPeriodController::class, 'create'])->name('create')->middleware('permission:accounting.create');
            Route::post('/', [FiscalPeriodController::class, 'store'])->name('store')->middleware('permission:accounting.create');
            Route::post('/generate-year', [FiscalPeriodController::class, 'generateYear'])->name('generate-year')->middleware('permission:accounting.create');
            Route::post('/{period}/close', [FiscalPeriodController::class, 'close'])->name('close')->middleware('permission:accounting.close_period');
            Route::post('/{period}/reopen', [FiscalPeriodController::class, 'reopen'])->name('reopen')->middleware('permission:accounting.close_period');
        });

        // Tax Management
        Route::prefix('tax')->name('tax.')->middleware('permission:accounting.view')->group(function () {
            Route::get('/spt-masa-ppn', [TaxController::class, 'sptMasaPPN'])->name('spt-masa-ppn');
            Route::get('/annual', [TaxController::class, 'annualSummary'])->name('annual');
            Route::get('/calculator', [TaxController::class, 'calculator'])->name('calculator');
        });

        Route::get('/reports', [FinancialReportController::class, 'index'])->name('reports.index')->middleware('permission:accounting.view');
        Route::get('/ledger', [AccountingReportController::class, 'ledger'])->name('ledger')->middleware('permission:accounting.view');
        Route::get('/trial-balance', [AccountingReportController::class, 'trialBalance'])->name('trial-balance')->middleware('permission:accounting.view');
        Route::get('/balance-sheet', [FinancialReportController::class, 'balanceSheet'])->name('balance-sheet')->middleware('permission:accounting.view');
        Route::get('/profit-loss', [FinancialReportController::class, 'profitLoss'])->name('profit-loss')->middleware('permission:accounting.view');
        Route::get('/cash-flow', [FinancialReportController::class, 'cashFlow'])->name('cash-flow')->middleware('permission:accounting.view');
        Route::get('/cash-flow/pdf', [FinancialReportController::class, 'cashFlowPdf'])->name('cash-flow.pdf')->middleware('permission:accounting.view');
        Route::get('/cash-flow/excel', [FinancialReportController::class, 'cashFlowExcel'])->name('cash-flow.excel')->middleware('permission:accounting.view');
        Route::get('/ar-aging', [FinancialReportController::class, 'arAging'])->name('ar-aging')->middleware('permission:accounting.view');
        Route::get('/financial-ratios', [FinancialRatioController::class, 'index'])->name('financial-ratios')->middleware('permission:accounting.view');

        // Bank Accounts & Reconciliation
        Route::prefix('bank')->name('bank.')->middleware('permission:accounting.view')->group(function () {
            Route::get('/', [BankAccountController::class, 'index'])->name('index');
            Route::get('/create', [BankAccountController::class, 'create'])->name('create')->middleware('permission:accounting.create');
            Route::post('/', [BankAccountController::class, 'store'])->name('store')->middleware('permission:accounting.create');

            Route::prefix('reconciliation')->name('reconciliation.')->group(function () {
                Route::get('/', [BankReconciliationController::class, 'index'])->name('index');
                Route::post('/', [BankReconciliationController::class, 'create'])->name('create')->middleware('permission:accounting.create');
                Route::get('/{reconciliation}/edit', [BankReconciliationController::class, 'edit'])->name('edit');
                Route::post('/{reconciliation}/toggle/{transaction}', [BankReconciliationController::class, 'toggleTransaction'])->name('toggle')->middleware('permission:accounting.edit');
                Route::post('/{reconciliation}/complete', [BankReconciliationController::class, 'complete'])->name('complete')->middleware('permission:accounting.edit');
            });

            Route::get('/{bankAccount}', [BankAccountController::class, 'show'])->name('show');
            Route::get('/{bankAccount}/transactions', [BankAccountController::class, 'transactions'])->name('transactions');
            Route::post('/{bankAccount}/transactions', [BankAccountController::class, 'storeTransaction'])->name('transactions.store')->middleware(['permission:accounting.create', 'fiscal-lock:transaction_date']);
        });

        // Budgets
        Route::prefix('budgets')->name('budgets.')->middleware('permission:accounting.view')->group(function () {
            Route::get('/', [BudgetController::class, 'index'])->name('index');
            Route::get('/create', [BudgetController::class, 'create'])->name('create')->middleware('permission:accounting.create');
            Route::post('/', [BudgetController::class, 'store'])->name('store')->middleware('permission:accounting.create');
            Route::get('/{budget}', [BudgetController::class, 'show'])->name('show');
            Route::post('/{budget}/approve', [BudgetController::class, 'approve'])->name('approve')->middleware('permission:accounting.edit');
            Route::delete('/{budget}', [BudgetController::class, 'destroy'])->name('destroy')->middleware('permission:accounting.delete');
        });

        // Fixed Assets
        Route::prefix('assets')->name('assets.')->middleware('permission:accounting.view')->group(function () {
            Route::get('/', [FixedAssetController::class, 'index'])->name('index');
            Route::get('/create', [FixedAssetController::class, 'create'])->name('create')->middleware('permission:accounting.create');
            Route::post('/', [FixedAssetController::class, 'store'])->name('store')->middleware('permission:accounting.create');
            Route::get('/{asset}', [FixedAssetController::class, 'show'])->name('show');
            Route::post('/run-depreciation', [FixedAssetController::class, 'runDepreciation'])->name('run-depreciation')->middleware('permission:accounting.edit');
            Route::post('/{asset}/dispose', [FixedAssetController::class, 'dispose'])->name('dispose')->middleware('permission:accounting.edit');
        });

        // Credit Notes
        Route::prefix('credit-notes')->name('credit-notes.')->middleware('permission:accounting.view')->group(function () {
            Route::get('/', [CreditNoteController::class, 'index'])->name('index');
            Route::get('/create', [CreditNoteController::class, 'create'])->name('create')->middleware('permission:accounting.create');
            Route::post('/', [CreditNoteController::class, 'store'])->name('store')->middleware('permission:accounting.create');
            Route::post('/{creditNote}/approve', [CreditNoteController::class, 'approve'])->name('approve')->middleware('permission:accounting.edit');
        });

        // Debit Notes
        Route::prefix('debit-notes')->name('debit-notes.')->middleware('permission:accounting.view')->group(function () {
            Route::get('/', [DebitNoteController::class, 'index'])->name('index');
            Route::get('/create', [DebitNoteController::class, 'create'])->name('create')->middleware('permission:accounting.create');
            Route::post('/', [DebitNoteController::class, 'store'])->name('store')->middleware('permission:accounting.create');
            Route::post('/{debitNote}/approve', [DebitNoteController::class, 'approve'])->name('approve')->middleware('permission:accounting.edit');
        });
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

    // ─── HR & PAYROLL ───
    Route::prefix('hr')->name('hr.')->group(function () {
        // Employees
        Route::prefix('employees')->name('employees.')->middleware('permission:hr.view')->group(function () {
            Route::get('/', [EmployeeController::class, 'index'])->name('index');
            Route::get('/create', [EmployeeController::class, 'create'])->name('create')->middleware('permission:hr.create');
            Route::post('/', [EmployeeController::class, 'store'])->name('store')->middleware('permission:hr.create');
            Route::get('/{employee}', [EmployeeController::class, 'show'])->name('show');
            Route::get('/{employee}/edit', [EmployeeController::class, 'edit'])->name('edit')->middleware('permission:hr.edit');
            Route::put('/{employee}', [EmployeeController::class, 'update'])->name('update')->middleware('permission:hr.edit');
            Route::delete('/{employee}', [EmployeeController::class, 'destroy'])->name('destroy')->middleware('permission:hr.delete');
        });

        // Salary Structures
        Route::post('/salary-structures', [SalaryStructureController::class, 'store'])
            ->name('salary-structures.store')->middleware('permission:hr.create');

        // Payroll
        Route::prefix('payroll')->name('payroll.')->middleware('permission:hr.view')->group(function () {
            Route::get('/', [PayrollController::class, 'index'])->name('index');
            Route::get('/dashboard', [PayrollController::class, 'dashboard'])->name('dashboard');
            Route::get('/create', [PayrollController::class, 'create'])->name('create')->middleware('permission:hr.create');
            Route::post('/', [PayrollController::class, 'store'])->name('store')->middleware('permission:hr.create');
            Route::get('/payslip/{payslip}', [PayrollController::class, 'payslip'])->name('payslip');
            Route::get('/{period}', [PayrollController::class, 'show'])->name('show');
            Route::post('/{period}/approve', [PayrollController::class, 'approve'])->name('approve')->middleware('permission:hr.approve_payroll');
            Route::post('/{period}/post', [PayrollController::class, 'postToAccounting'])->name('post')->middleware('permission:hr.post_payroll');
        });
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
        Route::get('/admin/flows', [ApprovalController::class, 'flows'])->name('flows')->middleware('permission:admin.manage_settings');
        Route::get('/admin/flows/{flow}/edit', [ApprovalController::class, 'editFlow'])->name('flows.edit')->middleware('permission:admin.manage_settings');
        Route::put('/admin/flows/{flow}', [ApprovalController::class, 'updateFlow'])->name('flows.update')->middleware('permission:admin.manage_settings');
        
        // Admin: Role Management
        Route::get('/admin/roles', [ApprovalController::class, 'roles'])->name('roles')->middleware('permission:admin.manage_settings');
        Route::get('/admin/roles/{role}/edit', [ApprovalController::class, 'editRole'])->name('roles.edit')->middleware('permission:admin.manage_settings');
        Route::put('/admin/roles/{role}', [ApprovalController::class, 'updateRole'])->name('roles.update')->middleware('permission:admin.manage_settings');
    });

    /*
    |--------------------------------------------------------------------------
    | Administration Routes (Permission-gated, Phase 7)
    |--------------------------------------------------------------------------
    */
    
        // Audit Logs
        Route::prefix('audit-logs')->name('audit-logs.')->middleware('permission:admin.view_audit_logs')->group(function () {
            Route::get('/', [AuditLogController::class, 'index'])->name('index');
            Route::get('/verify-integrity', [AuditLogController::class, 'verifyIntegrity'])->name('verify-integrity');
            Route::get('/{activityLog}', [AuditLogController::class, 'show'])->name('show');
        });

        // Settings
        Route::prefix('settings')->name('settings.')->middleware('permission:admin.manage_settings')->group(function () {
            Route::get('/', [SettingsController::class, 'index'])->name('index');
            Route::post('/', [SettingsController::class, 'update'])->name('update');
            Route::post('/company', [SettingsController::class, 'updateCompany'])->name('update.company');
            Route::post('/financial', [SettingsController::class, 'updateFinancial'])->name('update.financial');
            Route::post('/payroll', [SettingsController::class, 'updatePayroll'])->name('update.payroll');
            Route::post('/security', [SettingsController::class, 'updateSecurity'])->name('update.security');
            Route::post('/localization', [SettingsController::class, 'updateLocalization'])->name('update.localization');
            
            // User Management
            Route::resource('users', UserController::class)->except(['show'])->middleware('permission:admin.manage_users');
        });

        // System Maintenance
        Route::prefix('maintenance')->name('maintenance.')->middleware('permission:admin.maintenance')->group(function () {
            Route::get('/', [SystemMaintenanceController::class, 'index'])->name('index');
            Route::get('/download', [SystemMaintenanceController::class, 'downloadBackup'])->name('download');
            Route::post('/backup', [SystemMaintenanceController::class, 'runBackup'])->name('backup');
            Route::post('/archive', [SystemMaintenanceController::class, 'runArchive'])->name('archive');
        });

        // User Guide
        Route::prefix('user-guide')->name('user-guide.')->group(function () {
            Route::get('/', [UserGuideController::class, 'index'])->name('index');
            Route::get('/{chapter}', [UserGuideController::class, 'show'])->name('show');
        });

        // Support Tickets
        Route::prefix('support')->name('support.')->group(function () {
            Route::get('/', [SupportTicketController::class, 'index'])->name('index');
            Route::get('/create', [SupportTicketController::class, 'create'])->name('create');
            Route::post('/', [SupportTicketController::class, 'store'])->name('store');
            Route::get('/{ticket}', [SupportTicketController::class, 'show'])->name('show');
            Route::post('/{ticket}/reply', [SupportTicketController::class, 'reply'])->name('reply');
            Route::post('/{ticket}/status', [SupportTicketController::class, 'updateStatus'])->name('updateStatus');
        });

        // About
        Route::get('/about', [AboutController::class, 'index'])->name('about');

        // License Management
        Route::prefix('license')->name('license.')->middleware('permission:admin.manage_license')->group(function () {
            Route::get('/', [LicenseController::class, 'index'])->name('index');
            Route::get('/activate', [LicenseController::class, 'activate'])->name('activate');
            Route::post('/activate', [LicenseController::class, 'processActivation'])->name('processActivation');
        });
});

require __DIR__.'/auth.php';
