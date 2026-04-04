<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\BankReconciliation;
use App\Models\BankTransaction;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BankReconciliationService
{
    /**
     * Get book balance for a bank account (from linked COA journal entries).
     */
    public function getBookBalance(BankAccount $bankAccount, ?string $date = null): float
    {
        $date = $date ?? now()->toDateString();

        if (!$bankAccount->coa_id) {
            return $bankAccount->current_balance;
        }

        $debit = DB::table('journal_items')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_items.journal_entry_id')
            ->where('journal_items.account_id', $bankAccount->coa_id)
            ->where('journal_entries.date', '<=', $date)
            ->sum('journal_items.debit');

        $credit = DB::table('journal_items')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_items.journal_entry_id')
            ->where('journal_items.account_id', $bankAccount->coa_id)
            ->where('journal_entries.date', '<=', $date)
            ->sum('journal_items.credit');

        return $debit - $credit; // Asset account: debit-normal
    }

    /**
     * Start a new reconciliation.
     */
    public function createReconciliation(BankAccount $bankAccount, string $statementDate, float $statementBalance): BankReconciliation
    {
        $bookBalance = $this->getBookBalance($bankAccount, $statementDate);

        return BankReconciliation::create([
            'bank_account_id'  => $bankAccount->id,
            'statement_date'   => $statementDate,
            'statement_balance' => $statementBalance,
            'book_balance'     => $bookBalance,
            'difference'       => $statementBalance - $bookBalance,
            'status'           => 'draft',
        ]);
    }

    /**
     * Toggle reconcile status of a transaction.
     */
    public function toggleReconcile(BankTransaction $transaction, BankReconciliation $reconciliation): void
    {
        DB::transaction(function () use ($transaction, $reconciliation) {
            $oldState = $transaction->is_reconciled;

            if ($oldState && $transaction->reconciliation_id === $reconciliation->id) {
                $transaction->update(['is_reconciled' => false, 'reconciliation_id' => null]);
            } else {
                $transaction->update(['is_reconciled' => true, 'reconciliation_id' => $reconciliation->id]);
            }

            $this->recalculateDifference($reconciliation);

            AuditLogService::log(
                'bank_reconciliation',
                $oldState ? 'unmatch' : 'match',
                ($oldState ? 'Unmatched' : 'Matched') . " transaction #{$transaction->id} ({$transaction->description}) on reconciliation #{$reconciliation->id}",
                ['is_reconciled' => $oldState, 'reconciliation_id' => $oldState ? $reconciliation->id : null],
                ['is_reconciled' => !$oldState, 'reconciliation_id' => !$oldState ? $reconciliation->id : null],
                $transaction
            );
        });
    }

    /**
     * Complete a reconciliation.
     */
    public function completeReconciliation(BankReconciliation $reconciliation): BankReconciliation
    {
        return DB::transaction(function () use ($reconciliation) {
            $oldData = $reconciliation->toArray();

            $this->recalculateDifference($reconciliation);

            $reconciliation->update([
                'status'        => 'completed',
                'reconciled_by' => Auth::id(),
                'reconciled_at' => now(),
            ]);

            // Update bank account balance
            $reconciliation->bankAccount->update([
                'current_balance' => $reconciliation->statement_balance,
            ]);

            $reconciliation->refresh();

            AuditLogService::log(
                'bank_reconciliation',
                'complete',
                "Completed bank reconciliation #{$reconciliation->id} for {$reconciliation->bankAccount->name} (statement date: {$reconciliation->statement_date->format('Y-m-d')})",
                $oldData,
                $reconciliation->toArray(),
                $reconciliation
            );

            return $reconciliation;
        });
    }

    /**
     * Recalculate the difference for a reconciliation.
     */
    private function recalculateDifference(BankReconciliation $reconciliation): void
    {
        $reconciledDebit = BankTransaction::where('reconciliation_id', $reconciliation->id)
            ->where('type', 'debit')->sum('amount');
        $reconciledCredit = BankTransaction::where('reconciliation_id', $reconciliation->id)
            ->where('type', 'credit')->sum('amount');

        $adjustedBookBalance = $reconciliation->book_balance + $reconciledDebit - $reconciledCredit;
        $difference = $reconciliation->statement_balance - $adjustedBookBalance;

        $reconciliation->update(['difference' => $difference]);
    }

    /**
     * Get unreconciled transactions for a bank account up to a date.
     */
    public function getUnreconciledTransactions(BankAccount $bankAccount, string $toDate): \Illuminate\Database\Eloquent\Collection
    {
        return BankTransaction::where('bank_account_id', $bankAccount->id)
            ->where('transaction_date', '<=', $toDate)
            ->where('is_reconciled', false)
            ->orderBy('transaction_date')
            ->get();
    }

    /**
     * Record a bank transaction and optionally update balance.
     */
    public function recordTransaction(array $data): BankTransaction
    {
        $transaction = BankTransaction::create($data);

        $bankAccount = BankAccount::find($data['bank_account_id']);
        $adjustment = $data['type'] === 'debit' ? $data['amount'] : -$data['amount'];
        $bankAccount->increment('current_balance', $adjustment);

        return $transaction;
    }
}
