<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AccountingService
{
    /**
     * Default account codes used for auto-mapping.
     */
    public const ACCOUNTS_RECEIVABLE = '1200';
    public const REVENUE             = '4000';
    public const CASH_BANK           = '1100';

    /**
     * Create a double-entry journal entry.
     *
     * @param string $reference  e.g. INV-2026-00001
     * @param string $date       YYYY-MM-DD
     * @param string $description
     * @param array  $entries    [['account_id'=>int, 'debit'=>float, 'credit'=>float], ...]
     */
    public function createJournalEntry(string $reference, string $date, string $description, array $entries): JournalEntry
    {
        if (empty($entries)) {
            throw new InvalidArgumentException('Journal entries cannot be empty.');
        }

        $totalDebit  = round(array_sum(array_column($entries, 'debit')), 2);
        $totalCredit = round(array_sum(array_column($entries, 'credit')), 2);

        if (abs($totalDebit - $totalCredit) > 0.01) {
            throw new InvalidArgumentException(
                "Journal entry is not balanced: Debit ({$totalDebit}) ≠ Credit ({$totalCredit})"
            );
        }

        return DB::transaction(function () use ($reference, $date, $description, $entries) {
            $journal = JournalEntry::create([
                'reference'   => $reference,
                'date'        => $date,
                'description' => $description,
            ]);

            foreach ($entries as $line) {
                $journal->items()->create([
                    'account_id' => $line['account_id'],
                    'debit'      => $line['debit'] ?? 0,
                    'credit'     => $line['credit'] ?? 0,
                ]);
            }

            return $journal;
        });
    }

    /**
     * Get ledger (all journal items) for an account with running balance.
     */
    public function getLedger(int $accountId, ?string $from = null, ?string $to = null): array
    {
        $account = ChartOfAccount::findOrFail($accountId);

        $query = DB::table('journal_items')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_items.journal_entry_id')
            ->where('journal_items.account_id', $accountId)
            ->where('journal_entries.is_posted', true)
            ->select(
                'journal_entries.date',
                'journal_entries.reference',
                'journal_entries.description',
                'journal_items.debit',
                'journal_items.credit'
            )
            ->orderBy('journal_entries.date')
            ->orderBy('journal_entries.id');

        if ($from) {
            $query->where('journal_entries.date', '>=', $from);
        }
        if ($to) {
            $query->where('journal_entries.date', '<=', $to);
        }

        $rows = $query->get();

        // Compute running balance.
        // For asset/expense accounts:  balance = debit - credit
        // For liability/equity/revenue: balance = credit - debit
        $isDebitNormal = in_array($account->type, ['asset', 'expense']);
        $balance = 0;

        $ledger = $rows->map(function ($row) use ($isDebitNormal, &$balance) {
            $debit  = (float) $row->debit;
            $credit = (float) $row->credit;
            $balance += $isDebitNormal ? ($debit - $credit) : ($credit - $debit);

            return (object) [
                'date'        => $row->date,
                'reference'   => $row->reference,
                'description' => $row->description,
                'debit'       => $debit,
                'credit'      => $credit,
                'balance'     => round($balance, 2),
            ];
        });

        return [
            'account' => $account,
            'entries' => $ledger,
            'closing_balance' => round($balance, 2),
        ];
    }

    /**
     * Get trial balance: total debits and credits per account.
     */
    public function getTrialBalance(?string $from = null, ?string $to = null): array
    {
        $query = DB::table('journal_items')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_items.journal_entry_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_items.account_id')
            ->where('journal_entries.is_posted', true)
            ->select(
                'chart_of_accounts.id',
                'chart_of_accounts.code',
                'chart_of_accounts.name',
                'chart_of_accounts.type',
                DB::raw('SUM(journal_items.debit) as total_debit'),
                DB::raw('SUM(journal_items.credit) as total_credit')
            )
            ->groupBy('chart_of_accounts.id', 'chart_of_accounts.code', 'chart_of_accounts.name', 'chart_of_accounts.type')
            ->orderBy('chart_of_accounts.code');

        if ($from) {
            $query->where('journal_entries.date', '>=', $from);
        }
        if ($to) {
            $query->where('journal_entries.date', '<=', $to);
        }

        $rows = $query->get();

        $grandDebit  = round((float) $rows->sum('total_debit'), 2);
        $grandCredit = round((float) $rows->sum('total_credit'), 2);

        return [
            'accounts'     => $rows,
            'grand_debit'  => $grandDebit,
            'grand_credit' => $grandCredit,
            'is_balanced'  => abs($grandDebit - $grandCredit) < 0.01,
        ];
    }

    /**
     * Resolve a COA record by its code, or return null.
     */
    public function resolveAccount(string $code): ?ChartOfAccount
    {
        return ChartOfAccount::where('code', $code)->first();
    }
}
