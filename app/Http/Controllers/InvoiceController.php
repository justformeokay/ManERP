<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvoiceRequest;
use App\Models\Invoice;
use App\Models\SalesOrder;
use App\Services\FinanceService;
use App\Traits\Auditable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        // Summary widgets
        $summary = [
            'total_receivable' => Invoice::whereNotIn('status', ['draft', 'cancelled'])
                ->selectRaw('COALESCE(SUM(total_amount - paid_amount), 0) as total')
                ->value('total'),
            'overdue_count' => Invoice::whereNotIn('status', ['paid', 'cancelled', 'draft'])
                ->where('due_date', '<', now()->toDateString())
                ->count(),
            'overdue_amount' => Invoice::whereNotIn('status', ['paid', 'cancelled', 'draft'])
                ->where('due_date', '<', now()->toDateString())
                ->selectRaw('COALESCE(SUM(total_amount - paid_amount), 0) as total')
                ->value('total'),
            'unpaid_count' => Invoice::whereIn('status', ['unpaid', 'sent', 'partial'])
                ->count(),
        ];

        return view('finance.invoices.index', compact('invoices', 'summary'));
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
            $salesOrder = SalesOrder::with(['client', 'items.product'])
                ->whereIn('status', ['confirmed', 'processing', 'shipped', 'completed'])
                ->findOrFail($request->input('sales_order'));
        }

        // Allow SOs that still have uninvoiced items (partial invoicing)
        $salesOrders = SalesOrder::with('client')
            ->whereIn('status', ['confirmed', 'processing', 'shipped', 'completed'])
            ->whereHas('items', fn($q) => $q->whereRaw('quantity > invoiced_quantity'))
            ->orderByDesc('order_date')
            ->get();

        return view('finance.invoices.create', compact('salesOrders', 'salesOrder'));
    }

    public function store(InvoiceRequest $request)
    {
        $salesOrder = SalesOrder::with('items')->findOrFail($request->sales_order_id);

        if (!in_array($salesOrder->status, ['confirmed', 'processing', 'shipped', 'completed'])) {
            return back()->with('error', __('messages.inv_so_status_error'));
        }

        // Validate at least one item has invoiceable quantity
        $hasInvoiceable = $salesOrder->items->contains(fn($item) => $item->remaining_invoiceable > 0);
        if (!$hasInvoiceable) {
            return back()->with('error', __('messages.inv_fully_invoiced'));
        }

        // Validate submitted quantities don't exceed remaining
        if ($request->has('items')) {
            foreach ($request->input('items', []) as $soItemId => $line) {
                $soItem = $salesOrder->items->find($soItemId);
                if ($soItem && (float) ($line['quantity'] ?? 0) > $soItem->remaining_invoiceable) {
                    return back()->with('error', __('messages.inv_qty_exceeds', [
                        'product' => $soItem->product->name ?? '#' . $soItemId,
                        'max' => number_format($soItem->remaining_invoiceable, 2),
                    ]));
                }
            }
        }

        $invoice = $this->financeService->createInvoiceFromSalesOrder($salesOrder, [
            'due_date' => $request->due_date,
            'invoice_date' => $request->invoice_date ?? now()->toDateString(),
            'notes' => $request->notes,
            'items' => $request->input('items', []),
            'include_tax' => $request->boolean('include_tax', true),
            'tax_rate' => $request->input('tax_rate', 11),
        ]);

        $this->logCreate($invoice);

        return redirect()->route('finance.invoices.show', $invoice)
            ->with('success', __('messages.inv_created', ['number' => $invoice->invoice_number]));
    }

    /**
     * Approve a draft invoice: create journal entry.
     */
    public function approve(Invoice $invoice)
    {
        if ($invoice->status !== 'draft') {
            return back()->with('error', __('messages.inv_only_draft_approve'));
        }

        $this->financeService->approveInvoice($invoice);
        $this->logAction($invoice, 'approve', "Invoice {$invoice->invoice_number} approved");

        return back()->with('success', __('messages.inv_approved', ['number' => $invoice->invoice_number]));
    }

    /**
     * Mark invoice as sent to client.
     */
    public function send(Invoice $invoice)
    {
        $check = $invoice->requireTransition('sent');
        if ($check !== true) {
            return back()->with('error', $check);
        }

        $this->financeService->sendInvoice($invoice);
        $this->logAction($invoice, 'send', "Invoice {$invoice->invoice_number} sent to client");

        return back()->with('success', __('messages.inv_sent', ['number' => $invoice->invoice_number]));
    }

    /**
     * JSON API: Get sales order line items for auto-pull in create form.
     */
    public function salesOrderItems(Request $request)
    {
        $salesOrder = SalesOrder::with('items.product')
            ->findOrFail($request->input('sales_order_id'));

        $items = $salesOrder->items->map(fn($item) => [
            'id' => $item->id,
            'product_name' => $item->product->name ?? '—',
            'product_sku' => $item->product->sku ?? '',
            'quantity' => (float) $item->quantity,
            'invoiced_quantity' => (float) $item->invoiced_quantity,
            'remaining' => $item->remaining_invoiceable,
            'unit_price' => (float) $item->unit_price,
            'discount' => (float) $item->discount,
            'total' => (float) $item->total,
        ]);

        return response()->json([
            'client_name' => $salesOrder->client->name ?? '',
            'client_company' => $salesOrder->client->company ?? '',
            'order_number' => $salesOrder->number,
            'subtotal' => (float) $salesOrder->subtotal,
            'tax_amount' => (float) $salesOrder->tax_amount,
            'total' => (float) $salesOrder->total,
            'items' => $items,
        ]);
    }

    public function cancel(Invoice $invoice)
    {
        if (!$invoice->isCancellable()) {
            return back()->with('error', __('messages.inv_cannot_cancel'));
        }

        $check = $invoice->requireTransition('cancelled');
        if ($check !== true) {
            return back()->with('error', $check);
        }

        $oldData = $invoice->toArray();

        // Restore invoiced_quantity on SO items before cancelling
        foreach ($invoice->items as $invItem) {
            $soItem = $invoice->salesOrder?->items()
                ->where('product_id', $invItem->product_id)
                ->first();
            if ($soItem) {
                $soItem->decrement('invoiced_quantity', (float) $invItem->quantity);
            }
        }

        $this->financeService->cancelInvoice($invoice);
        $this->logAction($invoice, 'cancel', "Invoice {$invoice->invoice_number} cancelled", $oldData);

        return back()->with('success', __('messages.inv_cancelled', ['number' => $invoice->invoice_number]));
    }
}
