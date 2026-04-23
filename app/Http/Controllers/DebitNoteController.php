<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\DebitNote;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierBill;
use App\Models\Warehouse;
use App\Services\AccountingService;
use App\Services\StockService;
use App\Traits\Auditable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DebitNoteController extends Controller
{
    use Auditable;

    protected string $model = 'accounting';

    /**
     * CoA code constants — single source of truth.
     */
    public const COA_ACCOUNTS_PAYABLE = '2000';
    public const COA_EXPENSE          = '5000';
    public const COA_PPN_MASUKAN      = '1140';

    public function __construct(
        private AccountingService $accountingService,
        private StockService $stockService,
    ) {}

    public function index()
    {
        $debitNotes = DebitNote::with(['supplierBill', 'supplier', 'warehouse', 'items.product'])->latest()->get();
        return view('accounting.debit-notes.index', compact('debitNotes'));
    }

    public function create()
    {
        $bills      = SupplierBill::where('status', 'posted')->with('supplier')->get();
        $suppliers  = Supplier::orderBy('name')->get();
        $products   = Product::active()->orderBy('name')->get();
        $warehouses = Warehouse::active()->orderBy('name')->get();

        return view('accounting.debit-notes.create', compact('bills', 'suppliers', 'products', 'warehouses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_bill_id'        => 'required|exists:supplier_bills,id',
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

        $bill = SupplierBill::findOrFail($validated['supplier_bill_id']);

        // ── F-02: Anti-Overclaim Validation ──────────────────────
        $existingDebitTotal = $bill->debitNotes()
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');
        $maxAllowed = round((float) $bill->total - $existingDebitTotal, 2);
        $taxAmount  = $validated['tax_amount'] ?? 0;
        $totalAmount = $validated['amount'] + $taxAmount;

        if ($totalAmount > $maxAllowed) {
            return back()->withInput()->withErrors([
                'amount' => __('messages.note_overclaim_error', [
                    'max' => number_format($maxAllowed, 2),
                ]),
            ]);
        }

        $debitNote = DB::transaction(function () use ($validated, $bill, $taxAmount, $totalAmount) {
            $debitNote = DebitNote::create([
                'debit_note_number'  => DebitNote::generateNumber(),
                'supplier_bill_id'   => $bill->id,
                'supplier_id'        => $bill->supplier_id,
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
                    $debitNote->items()->create([
                        'product_id' => $item['product_id'],
                        'quantity'   => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'subtotal'   => round($item['quantity'] * $item['unit_price'], 2),
                    ]);
                }
            }

            return $debitNote;
        });

        $this->logCreate($debitNote);

        return redirect()->route('accounting.debit-notes.index')
            ->with('success', __('messages.debit_note_created'));
    }

    public function approve(DebitNote $debitNote)
    {
        if (!$debitNote->isDraft()) {
            return back()->with('error', __('messages.already_approved'));
        }

        // ── F-03: Resolve CoA accounts by exact code (not LIKE) ──
        $apAccount      = $this->resolveAccountOrFail(self::COA_ACCOUNTS_PAYABLE);
        $expenseAccount = $this->resolveAccountOrFail(self::COA_EXPENSE);

        DB::transaction(function () use ($debitNote, $apAccount, $expenseAccount) {
            $entries = [
                ['account_id' => $apAccount->id,      'debit' => $debitNote->amount, 'credit' => 0],
                ['account_id' => $expenseAccount->id,  'debit' => 0, 'credit' => $debitNote->amount],
            ];

            if ($debitNote->tax_amount > 0) {
                $taxAccount = $this->resolveAccountOrFail(self::COA_PPN_MASUKAN);
                $entries[0]['debit'] = $debitNote->total_amount;
                $entries[] = ['account_id' => $taxAccount->id, 'debit' => 0, 'credit' => $debitNote->tax_amount];
            }

            // ── F-04: Pass polymorphic sourceable to journal ─────
            $journal = $this->accountingService->createJournalEntry(
                $debitNote->debit_note_number,
                $debitNote->date,
                "Debit Note: {$debitNote->reason}",
                $entries,
                DebitNote::class,
                $debitNote->id,
                'auto'
            );

            $debitNote->update([
                'status'           => 'approved',
                'journal_entry_id' => $journal->id,
            ]);

            // ── F-07: Stock decrease for goods returned to supplier ──
            $this->processStockMovements($debitNote, 'out');
        });

        $this->logAction($debitNote, 'approve', "DN #{$debitNote->debit_note_number} approved");

        return back()->with('success', __('messages.debit_note_approved'));
    }

    /**
     * Process stock movements for debit note items (supplier return → stock out).
     */
    private function processStockMovements(DebitNote $debitNote, string $type): void
    {
        if (!$debitNote->warehouse_id || $debitNote->items->isEmpty()) {
            return;
        }

        $debitNote->loadMissing('items.product');

        foreach ($debitNote->items as $item) {
            $this->stockService->processMovement([
                'product_id'     => $item->product_id,
                'warehouse_id'   => $debitNote->warehouse_id,
                'type'           => $type,
                'quantity'       => $item->quantity,
                'unit_cost'      => $item->unit_price,
                'reference_type' => 'debit_note',
                'reference_id'   => $debitNote->id,
                'notes'          => "DN #{$debitNote->debit_note_number} — {$debitNote->reason}",
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
