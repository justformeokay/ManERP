<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\Client;
use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CreditNoteController extends Controller
{
    public function __construct(private AccountingService $accountingService) {}

    public function index()
    {
        $creditNotes = CreditNote::with(['invoice', 'client'])->latest()->get();
        return view('accounting.credit-notes.index', compact('creditNotes'));
    }

    public function create()
    {
        $invoices = Invoice::where('status', 'sent')->orWhere('status', 'partial')->get();
        $clients  = Client::active()->orderBy('name')->get();
        return view('accounting.credit-notes.create', compact('invoices', 'clients'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'date'       => 'required|date',
            'amount'     => 'required|numeric|min:0.01',
            'tax_amount' => 'nullable|numeric|min:0',
            'reason'     => 'required|string|max:1000',
            'notes'      => 'nullable|string|max:1000',
        ]);

        $invoice = Invoice::findOrFail($validated['invoice_id']);
        $taxAmount = $validated['tax_amount'] ?? 0;

        $creditNote = CreditNote::create([
            'credit_note_number' => CreditNote::generateNumber(),
            'invoice_id'         => $invoice->id,
            'client_id'          => $invoice->client_id,
            'date'               => $validated['date'],
            'amount'             => $validated['amount'],
            'tax_amount'         => $taxAmount,
            'total_amount'       => $validated['amount'] + $taxAmount,
            'reason'             => $validated['reason'],
            'notes'              => $validated['notes'] ?? null,
            'status'             => 'draft',
            'created_by'         => auth()->id(),
        ]);

        return redirect()->route('accounting.credit-notes.index')
            ->with('success', __('messages.credit_note_created'));
    }

    public function approve(CreditNote $creditNote)
    {
        if (!$creditNote->isDraft()) {
            return back()->with('error', __('messages.already_approved'));
        }

        DB::transaction(function () use ($creditNote) {
            // Create reversing journal: Dr Revenue, Cr AR
            $arAccount = ChartOfAccount::where('code', 'like', '12%')->first();
            $revenueAccount = ChartOfAccount::where('code', 'like', '4%')->first();

            if ($arAccount && $revenueAccount) {
                $entries = [
                    ['account_id' => $revenueAccount->id, 'debit' => $creditNote->amount, 'credit' => 0],
                    ['account_id' => $arAccount->id, 'debit' => 0, 'credit' => $creditNote->amount],
                ];

                if ($creditNote->tax_amount > 0) {
                    $taxAccount = ChartOfAccount::where('code', 'like', '21%')->first();
                    if ($taxAccount) {
                        $entries[] = ['account_id' => $taxAccount->id, 'debit' => $creditNote->tax_amount, 'credit' => 0];
                        $entries[1]['credit'] = $creditNote->total_amount;
                    }
                }

                $journal = $this->accountingService->createJournalEntry(
                    $creditNote->credit_note_number,
                    $creditNote->date,
                    "Credit Note: {$creditNote->reason}",
                    $entries
                );

                $creditNote->update([
                    'status'           => 'approved',
                    'journal_entry_id' => $journal->id,
                ]);
            }
        });

        return back()->with('success', __('messages.credit_note_approved'));
    }
}
