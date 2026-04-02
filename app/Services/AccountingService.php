<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\FiscalPeriod;
use App\Models\Invoice;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\Auth;
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
     * Balance Sheet as of a given date.
     */
    public function getBalanceSheet(?string $date = null): array
    {
        $date ??= now()->toDateString();

        $rows = DB::table('journal_items')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_items.journal_entry_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_items.account_id')
            ->where('journal_entries.is_posted', true)
            ->where('journal_entries.date', '<=', $date)
            ->whereIn('chart_of_accounts.type', ['asset', 'liability', 'equity', 'revenue', 'expense'])
            ->select(
                'chart_of_accounts.id',
                'chart_of_accounts.code',
                'chart_of_accounts.name',
                'chart_of_accounts.type',
                DB::raw('SUM(journal_items.debit) as total_debit'),
                DB::raw('SUM(journal_items.credit) as total_credit')
            )
            ->groupBy('chart_of_accounts.id', 'chart_of_accounts.code', 'chart_of_accounts.name', 'chart_of_accounts.type')
            ->orderBy('chart_of_accounts.code')
            ->get();

        $assets = collect();
        $liabilities = collect();
        $equity = collect();
        $retainedEarnings = 0;

        foreach ($rows as $row) {
            $debit  = (float) $row->total_debit;
            $credit = (float) $row->total_credit;

            switch ($row->type) {
                case 'asset':
                    $row->balance = round($debit - $credit, 2);
                    $assets->push($row);
                    break;
                case 'liability':
                    $row->balance = round($credit - $debit, 2);
                    $liabilities->push($row);
                    break;
                case 'equity':
                    $row->balance = round($credit - $debit, 2);
                    $equity->push($row);
                    break;
                case 'revenue':
                    $retainedEarnings += ($credit - $debit);
                    break;
                case 'expense':
                    $retainedEarnings -= ($debit - $credit);
                    break;
            }
        }

        $retainedEarnings = round($retainedEarnings, 2);

        $totalAssets = round($assets->sum('balance'), 2);
        $totalLiabilities = round($liabilities->sum('balance'), 2);
        $totalEquity = round($equity->sum('balance') + $retainedEarnings, 2);

        return [
            'date'                      => $date,
            'assets'                    => $assets,
            'liabilities'               => $liabilities,
            'equity'                    => $equity,
            'retained_earnings'         => $retainedEarnings,
            'total_assets'              => $totalAssets,
            'total_liabilities'         => $totalLiabilities,
            'total_equity'              => $totalEquity,
            'total_liabilities_equity'  => round($totalLiabilities + $totalEquity, 2),
            'is_balanced'               => abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.01,
        ];
    }

    /**
     * Profit & Loss (Income Statement) for a date range.
     */
    public function getProfitLoss(?string $startDate = null, ?string $endDate = null): array
    {
        $endDate   ??= now()->toDateString();
        $startDate ??= now()->startOfYear()->toDateString();

        $rows = DB::table('journal_items')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_items.journal_entry_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_items.account_id')
            ->where('journal_entries.is_posted', true)
            ->where('journal_entries.date', '>=', $startDate)
            ->where('journal_entries.date', '<=', $endDate)
            ->whereIn('chart_of_accounts.type', ['revenue', 'expense'])
            ->select(
                'chart_of_accounts.id',
                'chart_of_accounts.code',
                'chart_of_accounts.name',
                'chart_of_accounts.type',
                DB::raw('SUM(journal_items.debit) as total_debit'),
                DB::raw('SUM(journal_items.credit) as total_credit')
            )
            ->groupBy('chart_of_accounts.id', 'chart_of_accounts.code', 'chart_of_accounts.name', 'chart_of_accounts.type')
            ->orderBy('chart_of_accounts.code')
            ->get();

        $revenue  = collect();
        $expenses = collect();

        foreach ($rows as $row) {
            $debit  = (float) $row->total_debit;
            $credit = (float) $row->total_credit;

            if ($row->type === 'revenue') {
                $row->balance = round($credit - $debit, 2);
                $revenue->push($row);
            } else {
                $row->balance = round($debit - $credit, 2);
                $expenses->push($row);
            }
        }

        $totalRevenue = round($revenue->sum('balance'), 2);
        $totalExpense = round($expenses->sum('balance'), 2);

        return [
            'start_date'    => $startDate,
            'end_date'      => $endDate,
            'revenue'       => $revenue,
            'expenses'      => $expenses,
            'total_revenue' => $totalRevenue,
            'total_expense' => $totalExpense,
            'net_profit'    => round($totalRevenue - $totalExpense, 2),
        ];
    }

    /**
     * Resolve a COA record by its code, or return null.
     */
    public function resolveAccount(string $code): ?ChartOfAccount
    {
        return ChartOfAccount::where('code', $code)->first();
    }

    // ════════════════════════════════════════════════════════════════
    // C: ACCOUNTS RECEIVABLE AGING
    // ════════════════════════════════════════════════════════════════

    /**
     * Get AR aging report grouped by client.
     */
    public function getARAgingReport(?int $clientId = null): array
    {
        $query = Invoice::with('client')
            ->whereIn('status', ['unpaid', 'partial'])
            ->orderBy('due_date');

        if ($clientId) {
            $query->where('client_id', $clientId);
        }

        $invoices = $query->get();

        $grouped = [];
        $totals  = ['total' => 0, 'current' => 0, '1-30' => 0, '31-60' => 0, '61-90' => 0, '90+' => 0];

        foreach ($invoices as $inv) {
            $clientName = $inv->client->name ?? 'Unknown';
            $outstanding = $inv->remaining_balance;
            $daysOverdue = max(0, (int) now()->startOfDay()->diffInDays($inv->due_date, false) * -1);

            if (!isset($grouped[$inv->client_id])) {
                $grouped[$inv->client_id] = [
                    'client_id'   => $inv->client_id,
                    'client_name' => $clientName,
                    'invoice_count' => 0,
                    'current' => 0, '1-30' => 0, '31-60' => 0, '61-90' => 0, '90+' => 0, 'total' => 0,
                ];
            }

            $bucket = 'current';
            if ($daysOverdue > 90) $bucket = '90+';
            elseif ($daysOverdue > 60) $bucket = '61-90';
            elseif ($daysOverdue > 30) $bucket = '31-60';
            elseif ($daysOverdue > 0) $bucket = '1-30';

            $grouped[$inv->client_id][$bucket] += $outstanding;
            $grouped[$inv->client_id]['total'] += $outstanding;
            $grouped[$inv->client_id]['invoice_count']++;

            $totals[$bucket] += $outstanding;
            $totals['total'] += $outstanding;
        }

        // Round all values
        foreach ($grouped as &$row) {
            foreach (['current', '1-30', '31-60', '61-90', '90+', 'total'] as $k) {
                $row[$k] = round($row[$k], 2);
            }
        }
        foreach ($totals as &$v) {
            $v = round($v, 2);
        }

        return [
            'report'  => array_values($grouped),
            'totals'  => $totals,
        ];
    }

    // ════════════════════════════════════════════════════════════════
    // D: FISCAL PERIOD / CLOSING
    // ════════════════════════════════════════════════════════════════

    /**
     * Check if a journal date falls in a closed period.
     */
    public function isDateInClosedPeriod(string $date): bool
    {
        return FiscalPeriod::closed()
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->exists();
    }

    /**
     * Close a fiscal period — creates a closing journal entry that
     * transfers revenue and expense balances to retained earnings.
     */
    public function closePeriod(FiscalPeriod $period, ?string $notes = null): FiscalPeriod
    {
        if ($period->isClosed()) {
            throw new InvalidArgumentException('This period is already closed.');
        }

        return DB::transaction(function () use ($period, $notes) {
            // Get P&L for the period
            $pl = $this->getProfitLoss(
                $period->start_date->toDateString(),
                $period->end_date->toDateString()
            );

            $closingEntries = [];

            // Close revenue accounts (Debit revenue, Credit retained earnings)
            foreach ($pl['revenue'] as $rev) {
                if ($rev->balance > 0) {
                    $closingEntries[] = [
                        'account_id' => $rev->id,
                        'debit'      => $rev->balance,
                        'credit'     => 0,
                    ];
                }
            }

            // Close expense accounts (Credit expense, Debit retained earnings)
            foreach ($pl['expenses'] as $exp) {
                if ($exp->balance > 0) {
                    $closingEntries[] = [
                        'account_id' => $exp->id,
                        'debit'      => 0,
                        'credit'     => $exp->balance,
                    ];
                }
            }

            // Retained earnings account — net profit goes here
            $retainedEarnings = ChartOfAccount::where('code', '3200')->first();

            $closingJournal = null;
            if (!empty($closingEntries) && $retainedEarnings) {
                $netProfit = $pl['net_profit'];

                if ($netProfit >= 0) {
                    $closingEntries[] = [
                        'account_id' => $retainedEarnings->id,
                        'debit'      => 0,
                        'credit'     => $netProfit,
                    ];
                } else {
                    $closingEntries[] = [
                        'account_id' => $retainedEarnings->id,
                        'debit'      => abs($netProfit),
                        'credit'     => 0,
                    ];
                }

                $closingJournal = $this->createJournalEntry(
                    'CLOSE-' . $period->end_date->format('Ym'),
                    $period->end_date->toDateString(),
                    "Closing entry for period: {$period->name}",
                    $closingEntries
                );

                $closingJournal->update([
                    'entry_type' => 'closing',
                    'is_posted'  => true,
                ]);
            }

            $period->update([
                'status'             => 'closed',
                'closed_by'          => Auth::id(),
                'closed_at'          => now(),
                'closing_notes'      => $notes,
                'closing_journal_id' => $closingJournal?->id,
            ]);

            return $period->fresh();
        });
    }

    /**
     * Reopen a closed period (admin only).
     */
    public function reopenPeriod(FiscalPeriod $period): FiscalPeriod
    {
        if ($period->isOpen()) {
            throw new InvalidArgumentException('This period is already open.');
        }

        $period->update([
            'status'    => 'open',
            'closed_by' => null,
            'closed_at' => null,
        ]);

        return $period->fresh();
    }

    // ════════════════════════════════════════════════════════════════
    // E: REVERSING / ADJUSTING JOURNAL ENTRIES
    // ════════════════════════════════════════════════════════════════

    /**
     * Create a reversing entry for an existing journal.
     */
    public function createReversingEntry(JournalEntry $original, ?string $date = null): JournalEntry
    {
        $date ??= now()->toDateString();

        $reversingItems = $original->items->map(fn($item) => [
            'account_id' => $item->account_id,
            'debit'      => (float) $item->credit,
            'credit'     => (float) $item->debit,
        ])->toArray();

        $journal = $this->createJournalEntry(
            'REV-' . $original->reference,
            $date,
            "Reversing: {$original->description}",
            $reversingItems
        );

        $journal->update([
            'entry_type'        => 'reversing',
            'reversed_entry_id' => $original->id,
            'is_posted'         => true,
        ]);

        return $journal;
    }

    /**
     * Create an adjusting journal entry.
     */
    public function createAdjustingEntry(
        string $date,
        string $description,
        array $entries
    ): JournalEntry {
        $ref = 'ADJ-' . now()->format('Ymd') . '-' . str_pad(
            JournalEntry::where('entry_type', 'adjusting')->whereDate('date', $date)->count() + 1,
            4, '0', STR_PAD_LEFT
        );

        $journal = $this->createJournalEntry($ref, $date, $description, $entries);

        $journal->update([
            'entry_type' => 'adjusting',
            'is_posted'  => true,
        ]);

        return $journal;
    }
}
