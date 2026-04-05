<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\BankReconciliation;
use App\Models\BankTransaction;
use App\Models\ChartOfAccount;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BankReconciliationService
{
    private AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }
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
     * Complete a reconciliation. Rejects if difference is not zero.
     */
    public function completeReconciliation(BankReconciliation $reconciliation): BankReconciliation
    {
        return DB::transaction(function () use ($reconciliation) {
            $oldData = $reconciliation->toArray();

            $this->recalculateDifference($reconciliation);
            $reconciliation->refresh();

            // Block completion if difference is not zero
            if (bccomp((string) abs((float) $reconciliation->difference), '0.01', 2) >= 0) {
                throw new InvalidArgumentException(
                    __('messages.bank_reconciliation_not_balanced', [
                        'difference' => number_format((float) $reconciliation->difference, 2),
                    ])
                );
            }

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
     * Record a bank transaction with atomic GL journal creation.
     * Dr/Cr the bank's linked COA + Cr/Dr the contra account.
     */
    public function recordTransaction(array $data): BankTransaction
    {
        return DB::transaction(function () use ($data) {
            $bankAccount = BankAccount::findOrFail($data['bank_account_id']);

            $journalEntryId = null;

            // Create GL journal if bank account has a linked COA
            if ($bankAccount->coa_id) {
                $contraAccountCode = $data['contra_account_code'] ?? null;
                $contraAccount = $contraAccountCode
                    ? ChartOfAccount::where('code', $contraAccountCode)->first()
                    : null;

                if ($contraAccount) {
                    $amount = (float) $data['amount'];
                    $isDebit = ($data['type'] === 'debit');

                    $entries = [
                        [
                            'account_id' => $bankAccount->coa_id,
                            'debit'      => $isDebit ? $amount : 0,
                            'credit'     => $isDebit ? 0 : $amount,
                        ],
                        [
                            'account_id' => $contraAccount->id,
                            'debit'      => $isDebit ? 0 : $amount,
                            'credit'     => $isDebit ? $amount : 0,
                        ],
                    ];

                    $journal = $this->accountingService->createJournalEntry(
                        $data['reference'] ?? 'BANK-TRX-' . now()->format('YmdHis'),
                        $data['transaction_date'],
                        $data['description'] ?? 'Bank Transaction',
                        $entries
                    );
                    $journal->update(['is_posted' => true]);
                    $journalEntryId = $journal->id;
                }
            }

            $transaction = BankTransaction::create(array_merge($data, [
                'journal_entry_id' => $journalEntryId,
            ]));

            $adjustment = $data['type'] === 'debit' ? $data['amount'] : -$data['amount'];
            $bankAccount->increment('current_balance', $adjustment);

            return $transaction;
        });
    }

    /**
     * Verify that the bank account balance matches GL balance.
     * Returns the difference (should be 0 if in sync).
     */
    public function verifyBankGLSync(BankAccount $bankAccount, ?string $date = null): array
    {
        $date = $date ?? now()->toDateString();
        $bankBalance = (float) $bankAccount->current_balance;
        $glBalance = $this->getBookBalance($bankAccount, $date);

        $difference = bcsub((string) $bankBalance, (string) $glBalance, 2);

        return [
            'bank_balance' => $bankBalance,
            'gl_balance'   => $glBalance,
            'difference'   => (float) $difference,
            'is_synced'    => bccomp($difference, '0', 2) === 0,
            'as_of'        => $date,
        ];
    }
}
