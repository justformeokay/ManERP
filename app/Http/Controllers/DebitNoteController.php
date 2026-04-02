<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\DebitNote;
use App\Models\Supplier;
use App\Models\SupplierBill;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DebitNoteController extends Controller
{
    public function __construct(private AccountingService $accountingService) {}

    public function index()
    {
        $debitNotes = DebitNote::with(['supplierBill', 'supplier'])->latest()->get();
        return view('accounting.debit-notes.index', compact('debitNotes'));
    }

    public function create()
    {
        $bills     = SupplierBill::where('status', 'posted')->get();
        $suppliers = Supplier::orderBy('name')->get();
        return view('accounting.debit-notes.create', compact('bills', 'suppliers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_bill_id' => 'required|exists:supplier_bills,id',
            'date'             => 'required|date',
            'amount'           => 'required|numeric|min:0.01',
            'tax_amount'       => 'nullable|numeric|min:0',
            'reason'           => 'required|string|max:1000',
            'notes'            => 'nullable|string|max:1000',
        ]);

        $bill = SupplierBill::findOrFail($validated['supplier_bill_id']);
        $taxAmount = $validated['tax_amount'] ?? 0;

        $debitNote = DebitNote::create([
            'debit_note_number'  => DebitNote::generateNumber(),
            'supplier_bill_id'   => $bill->id,
            'supplier_id'        => $bill->supplier_id,
            'date'               => $validated['date'],
            'amount'             => $validated['amount'],
            'tax_amount'         => $taxAmount,
            'total_amount'       => $validated['amount'] + $taxAmount,
            'reason'             => $validated['reason'],
            'notes'              => $validated['notes'] ?? null,
            'status'             => 'draft',
            'created_by'         => auth()->id(),
        ]);

        return redirect()->route('accounting.debit-notes.index')
            ->with('success', __('messages.debit_note_created'));
    }

    public function approve(DebitNote $debitNote)
    {
        if (!$debitNote->isDraft()) {
            return back()->with('error', __('messages.already_approved'));
        }

        DB::transaction(function () use ($debitNote) {
            // Create journal: Dr AP, Cr Expense/Inventory
            $apAccount      = ChartOfAccount::where('code', 'like', '20%')->first();
            $expenseAccount = ChartOfAccount::where('code', 'like', '50%')->first();

            if ($apAccount && $expenseAccount) {
                $entries = [
                    ['account_id' => $apAccount->id, 'debit' => $debitNote->amount, 'credit' => 0],
                    ['account_id' => $expenseAccount->id, 'debit' => 0, 'credit' => $debitNote->amount],
                ];

                if ($debitNote->tax_amount > 0) {
                    $taxAccount = ChartOfAccount::where('code', 'like', '21%')->first();
                    if ($taxAccount) {
                        $entries[0]['debit'] = $debitNote->total_amount;
                        $entries[] = ['account_id' => $taxAccount->id, 'debit' => 0, 'credit' => $debitNote->tax_amount];
                    }
                }

                $journal = $this->accountingService->createJournalEntry(
                    $debitNote->debit_note_number,
                    $debitNote->date,
                    "Debit Note: {$debitNote->reason}",
                    $entries
                );

                $debitNote->update([
                    'status'           => 'approved',
                    'journal_entry_id' => $journal->id,
                ]);
            }
        });

        return back()->with('success', __('messages.debit_note_approved'));
    }
}
