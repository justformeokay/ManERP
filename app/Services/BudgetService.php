<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\ChartOfAccount;
use Illuminate\Support\Facades\DB;

class BudgetService
{
    /**
     * Get budget vs actual comparison for a given budget.
     */
    public function getBudgetVsActual(Budget $budget): array
    {
        $budget->load('lines.account');
        $year = $budget->fiscal_year;
        $report = [];

        foreach ($budget->lines as $line) {
            $monthly = [];
            $totalBudget = 0;
            $totalActual = 0;

            for ($month = 1; $month <= 12; $month++) {
                $budgetAmount = $line->getMonthAmount($month);
                $actualAmount = $this->getActualAmount($line->account_id, $year, $month);
                $variance = $budgetAmount - $actualAmount;
                $percentUsed = $budgetAmount > 0 ? ($actualAmount / $budgetAmount) * 100 : 0;

                $totalBudget += $budgetAmount;
                $totalActual += $actualAmount;

                $monthly[$month] = [
                    'budget'   => $budgetAmount,
                    'actual'   => $actualAmount,
                    'variance' => $variance,
                    'percent'  => round($percentUsed, 1),
                ];
            }

            $report[] = [
                'account_id'   => $line->account_id,
                'account_code' => $line->account->code,
                'account_name' => $line->account->name,
                'account_type' => $line->account->type,
                'monthly'      => $monthly,
                'total_budget' => $totalBudget,
                'total_actual' => $totalActual,
                'total_variance' => $totalBudget - $totalActual,
                'total_percent'  => $totalBudget > 0 ? round(($totalActual / $totalBudget) * 100, 1) : 0,
            ];
        }

        return [
            'budget' => $budget,
            'lines'  => $report,
            'totals' => $this->calculateTotals($report),
        ];
    }

    /**
     * Get actual amount from journal entries for an account in a given month.
     */
    private function getActualAmount(int $accountId, int $year, int $month): float
    {
        $start = sprintf('%d-%02d-01', $year, $month);
        $end = date('Y-m-t', strtotime($start));

        $account = ChartOfAccount::find($accountId);
        if (!$account) return 0;

        $debit = DB::table('journal_items')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_items.journal_entry_id')
            ->where('journal_items.account_id', $accountId)
            ->whereBetween('journal_entries.date', [$start, $end])
            ->sum('journal_items.debit');

        $credit = DB::table('journal_items')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_items.journal_entry_id')
            ->where('journal_items.account_id', $accountId)
            ->whereBetween('journal_entries.date', [$start, $end])
            ->sum('journal_items.credit');

        // For expense accounts, actual = debit - credit
        // For revenue accounts, actual = credit - debit
        return in_array($account->type, ['expense', 'asset'])
            ? $debit - $credit
            : $credit - $debit;
    }

    /**
     * Calculate totals across all budget lines.
     */
    private function calculateTotals(array $lines): array
    {
        $totalBudget = 0;
        $totalActual = 0;

        foreach ($lines as $line) {
            $totalBudget += $line['total_budget'];
            $totalActual += $line['total_actual'];
        }

        return [
            'budget'   => $totalBudget,
            'actual'   => $totalActual,
            'variance' => $totalBudget - $totalActual,
            'percent'  => $totalBudget > 0 ? round(($totalActual / $totalBudget) * 100, 1) : 0,
        ];
    }

    /**
     * Get summary for dashboard: current month budget utilization.
     */
    public function getCurrentMonthSummary(?int $year = null): array
    {
        $year = $year ?? now()->year;
        $month = now()->month;

        $budget = Budget::where('fiscal_year', $year)->where('status', 'approved')->first();
        if (!$budget) {
            return ['has_budget' => false];
        }

        $report = $this->getBudgetVsActual($budget);
        $monthlyTotals = ['budget' => 0, 'actual' => 0];

        foreach ($report['lines'] as $line) {
            if (isset($line['monthly'][$month])) {
                $monthlyTotals['budget'] += $line['monthly'][$month]['budget'];
                $monthlyTotals['actual'] += $line['monthly'][$month]['actual'];
            }
        }

        $monthlyTotals['variance'] = $monthlyTotals['budget'] - $monthlyTotals['actual'];
        $monthlyTotals['percent'] = $monthlyTotals['budget'] > 0
            ? round(($monthlyTotals['actual'] / $monthlyTotals['budget']) * 100, 1)
            : 0;

        return [
            'has_budget' => true,
            'budget_name' => $budget->name,
            'month' => $month,
            'year' => $year,
            'totals' => $monthlyTotals,
        ];
    }
}
