<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BankReconciliation;
use App\Models\BankTransaction;
use App\Services\BankReconciliationService;
use Illuminate\Http\Request;

class BankReconciliationController extends Controller
{
    public function __construct(private BankReconciliationService $reconciliationService) {}

    public function index()
    {
        $reconciliations = BankReconciliation::with('bankAccount')
            ->latest('statement_date')
            ->paginate(20);
        $bankAccounts = BankAccount::active()->get();
        return view('accounting.bank.reconciliation-index', compact('reconciliations', 'bankAccounts'));
    }

    public function create(Request $request)
    {
        $request->validate([
            'bank_account_id'  => 'required|exists:bank_accounts,id',
            'statement_date'   => 'required|date',
            'statement_balance' => 'required|numeric',
        ]);

        $bankAccount = BankAccount::findOrFail($request->bank_account_id);
        $reconciliation = $this->reconciliationService->createReconciliation(
            $bankAccount,
            $request->statement_date,
            $request->statement_balance
        );

        return redirect()->route('accounting.bank.reconciliation.edit', $reconciliation);
    }

    public function edit(BankReconciliation $reconciliation)
    {
        $reconciliation->load('bankAccount');
        $transactions = $this->reconciliationService->getUnreconciledTransactions(
            $reconciliation->bankAccount,
            $reconciliation->statement_date
        );

        $reconciledTransactions = BankTransaction::where('reconciliation_id', $reconciliation->id)->get();

        return view('accounting.bank.reconciliation-edit', compact('reconciliation', 'transactions', 'reconciledTransactions'));
    }

    public function toggleTransaction(BankReconciliation $reconciliation, BankTransaction $transaction)
    {
        $this->reconciliationService->toggleReconcile($transaction, $reconciliation);

        // Recalculate difference
        $reconciledSum = BankTransaction::where('reconciliation_id', $reconciliation->id)
            ->selectRaw("SUM(CASE WHEN type='debit' THEN amount ELSE -amount END) as net")
            ->value('net') ?? 0;

        $reconciliation->update([
            'difference' => $reconciliation->statement_balance - ($reconciliation->book_balance + $reconciledSum),
        ]);

        return back();
    }

    public function complete(BankReconciliation $reconciliation)
    {
        $this->reconciliationService->completeReconciliation($reconciliation);

        return redirect()->route('accounting.bank.reconciliation.index')
            ->with('success', __('messages.reconciliation_completed'));
    }
}
