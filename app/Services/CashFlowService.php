<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class CashFlowService
{
    /**
     * Default COA codes for cash/bank accounts.
     */
    public const CASH_CODES = ['1100', '1101', '1102', '1110'];

    /**
     * Generate Cash Flow Statement (Indirect Method) for a date range.
     *
     * The indirect method starts from net income and adjusts for:
     * 1. Operating Activities (changes in working capital)
     * 2. Investing Activities
     * 3. Financing Activities
     */
    public function getCashFlowStatement(?string $startDate = null, ?string $endDate = null): array
    {
        $endDate   ??= now()->toDateString();
        $startDate ??= now()->startOfYear()->toDateString();

        $netIncome = $this->getNetIncome($startDate, $endDate);

        $operating  = $this->getOperatingActivities($startDate, $endDate);
        $investing  = $this->getInvestingActivities($startDate, $endDate);
        $financing  = $this->getFinancingActivities($startDate, $endDate);

        $totalOperating = $netIncome + array_sum(array_column($operating, 'amount'));
        $totalInvesting = array_sum(array_column($investing, 'amount'));
        $totalFinancing = array_sum(array_column($financing, 'amount'));

        $netCashChange = $totalOperating + $totalInvesting + $totalFinancing;

        $beginningCash = $this->getCashBalance($startDate, false);
        $endingCash    = $beginningCash + $netCashChange;

        return [
            'start_date'       => $startDate,
            'end_date'         => $endDate,
            'net_income'       => round($netIncome, 2),
            'operating'        => $operating,
            'total_operating'  => round($totalOperating, 2),
            'investing'        => $investing,
            'total_investing'  => round($totalInvesting, 2),
            'financing'        => $financing,
            'total_financing'  => round($totalFinancing, 2),
            'net_cash_change'  => round($netCashChange, 2),
            'beginning_cash'   => round($beginningCash, 2),
            'ending_cash'      => round($endingCash, 2),
        ];
    }

    /**
     * Net income = Revenue – Expenses for the period.
     */
    private function getNetIncome(string $start, string $end): float
    {
        $rows = DB::table('journal_items')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_items.journal_entry_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_items.account_id')
            ->where('journal_entries.is_posted', true)
            ->whereBetween('journal_entries.date', [$start, $end])
            ->whereIn('chart_of_accounts.type', ['revenue', 'expense'])
            ->select(
                'chart_of_accounts.type',
                DB::raw('SUM(journal_items.debit) as total_debit'),
                DB::raw('SUM(journal_items.credit) as total_credit')
            )
            ->groupBy('chart_of_accounts.type')
            ->get()
            ->keyBy('type');

        $revenue = isset($rows['revenue'])
            ? (float) $rows['revenue']->total_credit - (float) $rows['revenue']->total_debit
            : 0;

        $expense = isset($rows['expense'])
            ? (float) $rows['expense']->total_debit - (float) $rows['expense']->total_credit
            : 0;

        return round($revenue - $expense, 2);
    }

    /**
     * Operating Activities — changes in current assets and current liabilities.
     * Uses indirect method: adjustments to net income.
     */
    private function getOperatingActivities(string $start, string $end): array
    {
        $items = [];

        // Accounts Receivable changes (increase = cash outflow, decrease = cash inflow)
        $arChange = $this->getAccountBalanceChange('1200', $start, $end);
        if (abs($arChange) > 0.01) {
            $items[] = [
                'label'  => 'accounts_receivable_change',
                'amount' => round(-$arChange, 2), // Inverse: AR increase is negative cash flow
            ];
        }

        // Inventory changes
        $invChange = $this->getAccountBalanceChange('1300', $start, $end);
        if (abs($invChange) > 0.01) {
            $items[] = [
                'label'  => 'inventory_change',
                'amount' => round(-$invChange, 2),
            ];
        }

        // Accounts Payable changes (increase = cash inflow)
        $apChange = $this->getAccountBalanceChange('2000', $start, $end);
        if (abs($apChange) > 0.01) {
            $items[] = [
                'label'  => 'accounts_payable_change',
                'amount' => round($apChange, 2),
            ];
        }

        // Tax Payable changes
        $taxChange = $this->getAccountBalanceChange('2100', $start, $end);
        if (abs($taxChange) > 0.01) {
            $items[] = [
                'label'  => 'tax_payable_change',
                'amount' => round($taxChange, 2),
            ];
        }

        // Manually tagged operating entries
        $manualOp = $this->getTaggedCashFlowAmount('operating', $start, $end);
        if (abs($manualOp) > 0.01) {
            $items[] = [
                'label'  => 'other_operating',
                'amount' => round($manualOp, 2),
            ];
        }

        return $items;
    }

    /**
     * Investing Activities — changes in long-term assets.
     */
    private function getInvestingActivities(string $start, string $end): array
    {
        $items = [];

        // Fixed Asset changes (account codes starting with 15xx)
        $faChange = $this->getAccountGroupBalanceChange('15', $start, $end);
        if (abs($faChange) > 0.01) {
            $items[] = [
                'label'  => 'fixed_asset_change',
                'amount' => round(-$faChange, 2),
            ];
        }

        // Manually tagged investing entries
        $manualInv = $this->getTaggedCashFlowAmount('investing', $start, $end);
        if (abs($manualInv) > 0.01) {
            $items[] = [
                'label'  => 'other_investing',
                'amount' => round($manualInv, 2),
            ];
        }

        return $items;
    }

    /**
     * Financing Activities — changes in equity and long-term liabilities.
     */
    private function getFinancingActivities(string $start, string $end): array
    {
        $items = [];

        // Long-term liabilities (account codes 22xx-29xx)
        $ltDebt = $this->getAccountGroupBalanceChange('22', $start, $end)
                + $this->getAccountGroupBalanceChange('23', $start, $end);
        if (abs($ltDebt) > 0.01) {
            $items[] = [
                'label'  => 'long_term_debt_change',
                'amount' => round($ltDebt, 2),
            ];
        }

        // Equity changes (3xxx) excluding retained earnings
        $equityChange = $this->getAccountGroupBalanceChange('31', $start, $end);
        if (abs($equityChange) > 0.01) {
            $items[] = [
                'label'  => 'equity_change',
                'amount' => round($equityChange, 2),
            ];
        }

        // Manually tagged financing entries
        $manualFin = $this->getTaggedCashFlowAmount('financing', $start, $end);
        if (abs($manualFin) > 0.01) {
            $items[] = [
                'label'  => 'other_financing',
                'amount' => round($manualFin, 2),
            ];
        }

        return $items;
    }

    /**
     * Get the net balance change for a specific account code in a period.
     */
    private function getAccountBalanceChange(string $code, string $start, string $end): float
    {
        $row = DB::table('journal_items')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_items.journal_entry_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_items.account_id')
            ->where('journal_entries.is_posted', true)
            ->whereBetween('journal_entries.date', [$start, $end])
            ->where('chart_of_accounts.code', $code)
            ->select(
                DB::raw('SUM(journal_items.debit) as total_debit'),
                DB::raw('SUM(journal_items.credit) as total_credit')
            )
            ->first();

        if (!$row) return 0;

        return round((float) $row->total_debit - (float) $row->total_credit, 2);
    }

    /**
     * Get the net balance change for account codes starting with a prefix.
     */
    private function getAccountGroupBalanceChange(string $codePrefix, string $start, string $end): float
    {
        $row = DB::table('journal_items')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_items.journal_entry_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_items.account_id')
            ->where('journal_entries.is_posted', true)
            ->whereBetween('journal_entries.date', [$start, $end])
            ->where('chart_of_accounts.code', 'like', $codePrefix . '%')
            ->select(
                DB::raw('SUM(journal_items.debit) as total_debit'),
                DB::raw('SUM(journal_items.credit) as total_credit')
            )
            ->first();

        if (!$row) return 0;

        return round((float) $row->total_debit - (float) $row->total_credit, 2);
    }

    /**
     * Get the sum of cash movements from manually tagged journal entries.
     */
    private function getTaggedCashFlowAmount(string $category, string $start, string $end): float
    {
        $row = DB::table('journal_items')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_items.journal_entry_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_items.account_id')
            ->where('journal_entries.is_posted', true)
            ->where('journal_entries.cash_flow_category', $category)
            ->whereBetween('journal_entries.date', [$start, $end])
            ->whereIn('chart_of_accounts.type', ['asset'])
            ->where(function ($q) {
                foreach (self::CASH_CODES as $code) {
                    $q->orWhere('chart_of_accounts.code', $code);
                }
            })
            ->select(
                DB::raw('SUM(journal_items.debit) as total_debit'),
                DB::raw('SUM(journal_items.credit) as total_credit')
            )
            ->first();

        if (!$row) return 0;

        return round((float) $row->total_debit - (float) $row->total_credit, 2);
    }

    /**
     * Get the cash/bank balance as of a date.
     *
     * @param bool $inclusive Whether to include the given date
     */
    private function getCashBalance(string $date, bool $inclusive = true): float
    {
        $operator = $inclusive ? '<=' : '<';

        $row = DB::table('journal_items')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_items.journal_entry_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_items.account_id')
            ->where('journal_entries.is_posted', true)
            ->where('journal_entries.date', $operator, $date)
            ->where(function ($q) {
                foreach (self::CASH_CODES as $code) {
                    $q->orWhere('chart_of_accounts.code', $code);
                }
            })
            ->select(
                DB::raw('SUM(journal_items.debit) as total_debit'),
                DB::raw('SUM(journal_items.credit) as total_credit')
            )
            ->first();

        if (!$row) return 0;

        return round((float) $row->total_debit - (float) $row->total_credit, 2);
    }
}
