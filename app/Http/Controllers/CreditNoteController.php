<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\Client;
use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\AccountingService;
use App\Services\StockService;
use App\Traits\Auditable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CreditNoteController extends Controller
{
    use Auditable;

    protected string $model = 'accounting';

    /**
     * CoA code constants — single source of truth.
     */
    public const COA_ACCOUNTS_RECEIVABLE = '1200';
    public const COA_SALES_RETURN        = '4100';
    public const COA_PPN_KELUARAN        = '2110';

    public function __construct(
        private AccountingService $accountingService,
        private StockService $stockService,
    ) {}

    public function index()
    {
        $creditNotes = CreditNote::with(['invoice', 'client', 'warehouse', 'items.product'])->latest()->get();
        return view('accounting.credit-notes.index', compact('creditNotes'));
    }

    public function create()
    {
        $invoices   = Invoice::whereIn('status', ['sent', 'partial', 'unpaid'])->with('client')->get();
        $clients    = Client::active()->orderBy('name')->get();
        $products   = Product::active()->orderBy('name')->get();
        $warehouses = Warehouse::active()->orderBy('name')->get();

        return view('accounting.credit-notes.create', compact('invoices', 'clients', 'products', 'warehouses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_id'              => 'required|exists:invoices,id',
            'warehouse_id'            => 'nullable|exists:warehouses,id',
            'date'                    => 'required|date',
            'amount'                  => 'required|numeric|min:0.01',
            'tax_amount'              => 'nullable|numeric|min:0',
            'reason'                  => 'required|string|max:1000',
            'notes'                   => 'nullable|string|max:1000',
            'items'                   => 'nullable|array',
            'items.*.product_id'      => 'required_with:items|exists:products,id',
            'items.*.quantity'        => 'required_with:items|numeric|min:0.01',
            'items.*.unit_price'      => 'required_with:items|numeric|min:0',
        ]);

        $invoice = Invoice::findOrFail($validated['invoice_id']);

        // ── F-02: Anti-Overclaim Validation ──────────────────────
        $existingCreditTotal = $invoice->creditNotes()
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');
        $maxAllowed = round((float) $invoice->total_amount - $existingCreditTotal, 2);
        $taxAmount  = $validated['tax_amount'] ?? 0;
        $totalAmount = $validated['amount'] + $taxAmount;

        if ($totalAmount > $maxAllowed) {
            return back()->withInput()->withErrors([
                'amount' => __('messages.note_overclaim_error', [
                    'max' => number_format($maxAllowed, 2),
                ]),
            ]);
        }

        $creditNote = DB::transaction(function () use ($validated, $invoice, $taxAmount, $totalAmount) {
            $creditNote = CreditNote::create([
                'credit_note_number' => CreditNote::generateNumber(),
                'invoice_id'         => $invoice->id,
                'client_id'          => $invoice->client_id,
                'warehouse_id'       => $validated['warehouse_id'] ?? null,
                'date'               => $validated['date'],
                'amount'             => $validated['amount'],
                'tax_amount'         => $taxAmount,
                'total_amount'       => $totalAmount,
                'reason'             => $validated['reason'],
                'notes'              => $validated['notes'] ?? null,
                'status'             => 'draft',
                'created_by'         => auth()->id(),
            ]);

            if (!empty($validated['items'])) {
                foreach ($validated['items'] as $item) {
                    $creditNote->items()->create([
                        'product_id' => $item['product_id'],
                        'quantity'   => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'subtotal'   => round($item['quantity'] * $item['unit_price'], 2),
                    ]);
                }
            }

            return $creditNote;
        });

        $this->logCreate($creditNote);

        return redirect()->route('accounting.credit-notes.index')
            ->with('success', __('messages.credit_note_created'));
    }

    public function approve(CreditNote $creditNote)
    {
        if (!$creditNote->isDraft()) {
            return back()->with('error', __('messages.already_approved'));
        }

        // ── F-03: Resolve CoA accounts by exact code (not LIKE) ──
        $arAccount      = $this->resolveAccountOrFail(self::COA_ACCOUNTS_RECEIVABLE);
        $revenueAccount = $this->resolveAccountOrFail(self::COA_SALES_RETURN);

        DB::transaction(function () use ($creditNote, $arAccount, $revenueAccount) {
            $entries = [
                ['account_id' => $revenueAccount->id, 'debit' => $creditNote->amount, 'credit' => 0],
                ['account_id' => $arAccount->id,      'debit' => 0, 'credit' => $creditNote->amount],
            ];

            if ($creditNote->tax_amount > 0) {
                $taxAccount = $this->resolveAccountOrFail(self::COA_PPN_KELUARAN);
                $entries[] = ['account_id' => $taxAccount->id, 'debit' => $creditNote->tax_amount, 'credit' => 0];
                $entries[1]['credit'] = $creditNote->total_amount;
            }

            // ── F-04: Pass polymorphic sourceable to journal ─────
            $journal = $this->accountingService->createJournalEntry(
                $creditNote->credit_note_number,
                $creditNote->date,
                "Credit Note: {$creditNote->reason}",
                $entries,
                CreditNote::class,
                $creditNote->id,
                'auto'
            );

            $creditNote->update([
                'status'           => 'approved',
                'journal_entry_id' => $journal->id,
            ]);

            // ── F-07: Stock increase for returned goods ──────────
            $this->processStockMovements($creditNote, 'in');
        });

        $this->logAction($creditNote, 'approve', "CN #{$creditNote->credit_note_number} approved");

        return back()->with('success', __('messages.credit_note_approved'));
    }

    /**
     * Process stock movements for credit note items (customer return → stock in).
     */
    private function processStockMovements(CreditNote $creditNote, string $type): void
    {
        if (!$creditNote->warehouse_id || $creditNote->items->isEmpty()) {
            return;
        }

        $creditNote->loadMissing('items.product');

        foreach ($creditNote->items as $item) {
            $this->stockService->processMovement([
                'product_id'     => $item->product_id,
                'warehouse_id'   => $creditNote->warehouse_id,
                'type'           => $type,
                'quantity'       => $item->quantity,
                'unit_cost'      => $item->unit_price,
                'reference_type' => 'credit_note',
                'reference_id'   => $creditNote->id,
                'notes'          => "CN #{$creditNote->credit_note_number} — {$creditNote->reason}",
                'created_by'     => auth()->id(),
            ]);
        }
    }

    /**
     * Resolve a CoA account by exact code or throw.
     */
    private function resolveAccountOrFail(string $code): ChartOfAccount
    {
        $account = $this->accountingService->resolveAccount($code);

        if (!$account) {
            abort(500, __('messages.note_coa_missing', ['code' => $code]));
        }

        return $account;
    }
}
