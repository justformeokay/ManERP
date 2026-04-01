<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvoiceRequest;
use App\Models\Invoice;
use App\Models\SalesOrder;
use App\Services\FinanceService;
use App\Traits\Auditable;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    use Auditable;

    protected string $model = 'finance';

    public function __construct(private FinanceService $financeService) {}

    public function index(Request $request)
    {
        $invoices = Invoice::query()
            ->with(['client', 'salesOrder'])
            ->search($request->input('search'))
            ->when($request->input('status'), fn($q, $s) => $q->where('status', $s))
            ->latest('invoice_date')
            ->paginate(15)
            ->withQueryString();

        return view('finance.invoices.index', compact('invoices'));
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['client', 'salesOrder', 'items.product', 'payments.creator', 'creator']);

        return view('finance.invoices.show', compact('invoice'));
    }

    public function create(Request $request)
    {
        $salesOrder = null;
        if ($request->has('sales_order')) {
            $salesOrder = SalesOrder::with('client')
                ->whereIn('status', ['confirmed', 'shipped', 'completed'])
                ->findOrFail($request->input('sales_order'));
        }

        $salesOrders = SalesOrder::with('client')
            ->whereIn('status', ['confirmed', 'shipped', 'completed'])
            ->whereDoesntHave('invoices')
            ->orderByDesc('order_date')
            ->get();

        return view('finance.invoices.create', compact('salesOrders', 'salesOrder'));
    }

    public function store(InvoiceRequest $request)
    {
        $salesOrder = SalesOrder::findOrFail($request->sales_order_id);

        if (!in_array($salesOrder->status, ['confirmed', 'shipped', 'completed'])) {
            return back()->with('error', 'Only confirmed, shipped, or completed orders can be invoiced.');
        }

        if ($salesOrder->invoices()->exists()) {
            return back()->with('error', 'This sales order already has an invoice.');
        }

        $invoice = $this->financeService->createInvoiceFromSalesOrder($salesOrder, [
            'due_date' => $request->due_date,
            'notes' => $request->notes,
        ]);

        $this->logCreate($invoice);

        return redirect()->route('finance.invoices.show', $invoice)
            ->with('success', "Invoice {$invoice->invoice_number} created successfully.");
    }

    public function cancel(Invoice $invoice)
    {
        $check = $invoice->requireTransition('cancelled');
        if ($check !== true) {
            return back()->with('error', $check);
        }

        $oldData = $invoice->toArray();
        $this->financeService->cancelInvoice($invoice);
        $this->logAction($invoice, 'cancel', "Invoice {$invoice->invoice_number} cancelled", $oldData);

        return back()->with('success', 'Invoice cancelled successfully.');
    }
}
