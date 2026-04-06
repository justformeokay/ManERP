<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\SupplierBill;
use App\Models\SupplierPayment;
use App\Services\AccountsPayableService;
use App\Traits\Auditable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountsPayableController extends Controller
{
    use Auditable;

    protected string $model = 'supplier_bill';

    public function __construct(
        protected AccountsPayableService $apService
    ) {}

    // ════════════════════════════════════════════════════════════════
    // BILL MANAGEMENT
    // ════════════════════════════════════════════════════════════════

    /**
     * List all supplier bills.
     */
    public function index(Request $request): View
    {
        $bills = SupplierBill::with(['supplier', 'purchaseOrder'])
            ->search($request->search)
            ->status($request->status)
            ->when($request->supplier_id, fn($q, $v) => $q->where('supplier_id', $v))
            ->when($request->overdue, fn($q) => $q->overdue())
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $suppliers = Supplier::orderBy('name')->get();
        $summary = $this->apService->getSummary();

        return view('ap.bills.index', compact('bills', 'suppliers', 'summary'));
    }

    /**
     * Show bill creation form.
     */
    public function create(Request $request): View
    {
        $suppliers = Supplier::where('status', 'active')->orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $purchaseOrders = PurchaseOrder::with('supplier')
            ->whereIn('status', ['approved', 'completed'])
            ->doesntHave('supplierBill') // PO not yet billed
            ->latest()
            ->get();

        $paymentTerms = (int) Setting::get('default_payment_terms', 30);

        $bill = new SupplierBill([
            'bill_date' => now()->format('Y-m-d'),
            'due_date'  => now()->addDays($paymentTerms)->format('Y-m-d'),
        ]);

        // If creating from PO
        $selectedPO = null;
        if ($request->has('po_id')) {
            $selectedPO = PurchaseOrder::with('items.product', 'supplier')->find($request->po_id);
        }

        return view('ap.bills.form', compact('bill', 'suppliers', 'products', 'purchaseOrders', 'selectedPO'));
    }

    /**
     * Store a new bill.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_id'       => 'required|exists:suppliers,id',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'bill_date'         => 'required|date',
            'due_date'          => 'required|date|after_or_equal:bill_date',
            'tax_amount'        => 'nullable|numeric|min:0',
            'notes'             => 'nullable|string|max:2000',
            'items'             => 'required|array|min:1',
            'items.*.product_id'  => 'nullable|exists:products,id',
            'items.*.description' => 'required|string|max:500',
            'items.*.quantity'    => 'required|numeric|min:0.0001',
            'items.*.price'       => 'required|numeric|min:0',
        ]);

        $bill = $this->apService->createBill($validated);
        $this->logCreate($bill, 'supplier_bill');

        return redirect()
            ->route('ap.bills.show', $bill)
            ->with('success', "Bill {$bill->bill_number} created successfully.");
    }

    /**
     * Show bill details.
     */
    public function show(SupplierBill $bill): View
    {
        $bill->load(['supplier', 'purchaseOrder', 'items.product', 'payments', 'journalEntry.items.account', 'creator']);

        return view('ap.bills.show', compact('bill'));
    }

    /**
     * Show bill edit form.
     */
    public function edit(SupplierBill $bill): View
    {
        if (!$bill->canEdit()) {
            return redirect()
                ->route('ap.bills.show', $bill)
                ->with('error', 'Only draft bills can be edited.');
        }

        $bill->load('items.product');
        $suppliers = Supplier::where('status', 'active')->orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $purchaseOrders = PurchaseOrder::with('supplier')
            ->whereIn('status', ['approved', 'completed'])
            ->where(function ($q) use ($bill) {
                $q->doesntHave('supplierBill')
                  ->orWhere('id', $bill->purchase_order_id);
            })
            ->latest()
            ->get();

        return view('ap.bills.form', compact('bill', 'suppliers', 'products', 'purchaseOrders'));
    }

    /**
     * Update a draft bill.
     */
    public function update(Request $request, SupplierBill $bill): RedirectResponse
    {
        if (!$bill->canEdit()) {
            return back()->with('error', 'Only draft bills can be edited.');
        }

        $validated = $request->validate([
            'supplier_id'       => 'required|exists:suppliers,id',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'bill_date'         => 'required|date',
            'due_date'          => 'required|date|after_or_equal:bill_date',
            'tax_amount'        => 'nullable|numeric|min:0',
            'notes'             => 'nullable|string|max:2000',
            'items'             => 'required|array|min:1',
            'items.*.product_id'  => 'nullable|exists:products,id',
            'items.*.description' => 'required|string|max:500',
            'items.*.quantity'    => 'required|numeric|min:0.0001',
            'items.*.price'       => 'required|numeric|min:0',
        ]);

        $oldData = $bill->toArray();
        $bill = $this->apService->updateBill($bill, $validated);
        $this->logUpdate($bill, $oldData, 'supplier_bill');

        return redirect()
            ->route('ap.bills.show', $bill)
            ->with('success', 'Bill updated successfully.');
    }

    /**
     * Post a bill (create journal entry).
     */
    public function post(SupplierBill $bill): RedirectResponse
    {
        try {
            $oldData = $bill->toArray();
            $this->apService->postBill($bill);
            $this->logUpdate($bill->fresh(), $oldData, 'supplier_bill');

            return redirect()
                ->route('ap.bills.show', $bill)
                ->with('success', "Bill {$bill->bill_number} has been posted.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel a bill.
     */
    public function cancel(SupplierBill $bill): RedirectResponse
    {
        try {
            $oldData = $bill->toArray();
            $this->apService->cancelBill($bill);
            $this->logUpdate($bill->fresh(), $oldData, 'supplier_bill');

            return redirect()
                ->route('ap.bills.show', $bill)
                ->with('success', "Bill {$bill->bill_number} has been cancelled.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Delete a draft bill.
     */
    public function destroy(SupplierBill $bill): RedirectResponse
    {
        if ($bill->status !== 'draft') {
            return back()->with('error', 'Only draft bills can be deleted.');
        }

        $this->logDelete($bill, 'supplier_bill');
        $bill->items()->delete();
        $bill->delete();

        return redirect()
            ->route('ap.bills.index')
            ->with('success', 'Bill deleted successfully.');
    }

    // ════════════════════════════════════════════════════════════════
    // PAYMENT MANAGEMENT
    // ════════════════════════════════════════════════════════════════

    /**
     * List all payments.
     */
    public function payments(Request $request): View
    {
        $payments = SupplierPayment::with(['supplier', 'supplierBill'])
            ->search($request->search)
            ->when($request->supplier_id, fn($q, $v) => $q->where('supplier_id', $v))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $suppliers = Supplier::orderBy('name')->get();

        return view('ap.payments.index', compact('payments', 'suppliers'));
    }

    /**
     * Show payment form for a specific bill.
     */
    public function paymentCreate(SupplierBill $bill): View
    {
        if (!$bill->canPay()) {
            return redirect()
                ->route('ap.bills.show', $bill)
                ->with('error', 'Cannot make payment on this bill.');
        }

        $bill->load('supplier');
        $paymentMethods = SupplierPayment::paymentMethodOptions();

        return view('ap.payments.form', compact('bill', 'paymentMethods'));
    }

    /**
     * Record a payment.
     */
    public function paymentStore(Request $request, SupplierBill $bill): RedirectResponse
    {
        $validated = $request->validate([
            'payment_date'     => 'required|date',
            'amount'           => "required|numeric|min:0.01|max:{$bill->outstanding}",
            'payment_method'   => 'required|in:cash,bank_transfer,check,other',
            'reference_number' => 'nullable|string|max:100',
            'notes'            => 'nullable|string|max:2000',
        ]);

        try {
            $validated['supplier_bill_id'] = $bill->id;
            $payment = $this->apService->recordPayment($validated);
            $this->logCreate($payment, 'supplier_payment');

            return redirect()
                ->route('ap.bills.show', $bill)
                ->with('success', "Payment {$payment->payment_number} recorded successfully.");
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    // ════════════════════════════════════════════════════════════════
    // AGING REPORT
    // ════════════════════════════════════════════════════════════════

    /**
     * AP Aging Report.
     */
    public function agingReport(Request $request): View
    {
        $report    = $this->apService->getAgingReport($request->supplier_id);
        $suppliers = Supplier::orderBy('name')->get();

        $overdueBills = SupplierBill::with('supplier')
            ->whereIn('status', ['posted', 'partial'])
            ->when($request->supplier_id, fn ($q) => $q->where('supplier_id', $request->supplier_id))
            ->orderBy('due_date')
            ->get();

        return view('ap.reports.aging', [
            'agingReport'  => $report['data'],
            'totals'       => $report['totals'],
            'overdueBills' => $overdueBills,
            'suppliers'    => $suppliers,
        ]);
    }
}
