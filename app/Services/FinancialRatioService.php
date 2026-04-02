<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;

class FinancialRatioService
{
    /**
     * Get all financial ratios for a given date.
     */
    public function getRatios(?string $date = null): array
    {
        $date = $date ?? now()->toDateString();

        $balances = $this->getAccountBalances($date);

        return [
            'liquidity'    => $this->getLiquidityRatios($balances),
            'profitability' => $this->getProfitabilityRatios($date),
            'leverage'     => $this->getLeverageRatios($balances),
            'efficiency'   => $this->getEfficiencyRatios($balances, $date),
            'date'         => $date,
        ];
    }

    private function getAccountBalances(string $date): array
    {
        $accounts = ChartOfAccount::all();
        $balances = [];

        foreach ($accounts as $account) {
            $debit = DB::table('journal_items')
                ->join('journal_entries', 'journal_entries.id', '=', 'journal_items.journal_entry_id')
                ->where('journal_items.account_id', $account->id)
                ->where('journal_entries.date', '<=', $date)
                ->sum('journal_items.debit');

            $credit = DB::table('journal_items')
                ->join('journal_entries', 'journal_entries.id', '=', 'journal_items.journal_entry_id')
                ->where('journal_items.account_id', $account->id)
                ->where('journal_entries.date', '<=', $date)
                ->sum('journal_items.credit');

            $balance = in_array($account->type, ['asset', 'expense'])
                ? $debit - $credit
                : $credit - $debit;

            $balances[$account->code] = $balance;
            $balances['type_' . $account->type][$account->code] = $balance;
        }

        return $balances;
    }

    private function sumByType(array $balances, string $type): float
    {
        return array_sum($balances['type_' . $type] ?? []);
    }

    private function sumByPrefix(array $balances, string $prefix): float
    {
        $sum = 0;
        foreach ($balances as $code => $balance) {
            if (is_string($code) && str_starts_with($code, $prefix)) {
                $sum += $balance;
            }
        }
        return $sum;
    }

    /**
     * Liquidity: Current Ratio, Quick Ratio, Working Capital
     */
    private function getLiquidityRatios(array $balances): array
    {
        $currentAssets = $this->sumByPrefix($balances, '11') + $this->sumByPrefix($balances, '12') + $this->sumByPrefix($balances, '13');
        $inventory = $this->sumByPrefix($balances, '13');
        $currentLiabilities = $this->sumByPrefix($balances, '20') + $this->sumByPrefix($balances, '21');

        $currentRatio = $currentLiabilities > 0 ? $currentAssets / $currentLiabilities : 0;
        $quickRatio = $currentLiabilities > 0 ? ($currentAssets - $inventory) / $currentLiabilities : 0;
        $workingCapital = $currentAssets - $currentLiabilities;

        return [
            'current_ratio'   => ['value' => round($currentRatio, 2), 'label' => 'Current Ratio', 'benchmark' => '> 1.5'],
            'quick_ratio'     => ['value' => round($quickRatio, 2), 'label' => 'Quick Ratio', 'benchmark' => '> 1.0'],
            'working_capital' => ['value' => round($workingCapital, 2), 'label' => 'Working Capital', 'benchmark' => '> 0'],
            'current_assets'      => $currentAssets,
            'current_liabilities' => $currentLiabilities,
            'inventory'           => $inventory,
        ];
    }

    /**
     * Profitability: Gross Margin, Net Margin, ROA, ROE
     */
    private function getProfitabilityRatios(string $date): array
    {
        $yearStart = substr($date, 0, 4) . '-01-01';

        $revenue = DB::table('journal_items')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_items.journal_entry_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_items.account_id')
            ->where('chart_of_accounts.type', 'revenue')
            ->whereBetween('journal_entries.date', [$yearStart, $date])
            ->selectRaw('COALESCE(SUM(credit) - SUM(debit), 0) as total')
            ->value('total');

        $cogs = DB::table('journal_items')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_items.journal_entry_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_items.account_id')
            ->where('chart_of_accounts.code', 'like', '50%')
            ->whereBetween('journal_entries.date', [$yearStart, $date])
            ->selectRaw('COALESCE(SUM(debit) - SUM(credit), 0) as total')
            ->value('total');

        $totalExpenses = DB::table('journal_items')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_items.journal_entry_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_items.account_id')
            ->where('chart_of_accounts.type', 'expense')
            ->whereBetween('journal_entries.date', [$yearStart, $date])
            ->selectRaw('COALESCE(SUM(debit) - SUM(credit), 0) as total')
            ->value('total');

        $netIncome = $revenue - $totalExpenses;
        $grossProfit = $revenue - $cogs;

        $totalAssets = DB::table('journal_items')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_items.journal_entry_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_items.account_id')
            ->where('chart_of_accounts.type', 'asset')
            ->where('journal_entries.date', '<=', $date)
            ->selectRaw('COALESCE(SUM(debit) - SUM(credit), 0) as total')
            ->value('total');

        $totalEquity = DB::table('journal_items')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_items.journal_entry_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_items.account_id')
            ->where('chart_of_accounts.type', 'equity')
            ->where('journal_entries.date', '<=', $date)
            ->selectRaw('COALESCE(SUM(credit) - SUM(debit), 0) as total')
            ->value('total');

        $grossMargin = $revenue > 0 ? ($grossProfit / $revenue) * 100 : 0;
        $netMargin = $revenue > 0 ? ($netIncome / $revenue) * 100 : 0;
        $roa = $totalAssets > 0 ? ($netIncome / $totalAssets) * 100 : 0;
        $roe = $totalEquity > 0 ? ($netIncome / $totalEquity) * 100 : 0;

        return [
            'gross_margin' => ['value' => round($grossMargin, 1), 'label' => 'Gross Margin (%)', 'benchmark' => '> 30%'],
            'net_margin'   => ['value' => round($netMargin, 1), 'label' => 'Net Margin (%)', 'benchmark' => '> 10%'],
            'roa'          => ['value' => round($roa, 1), 'label' => 'Return on Assets (%)', 'benchmark' => '> 5%'],
            'roe'          => ['value' => round($roe, 1), 'label' => 'Return on Equity (%)', 'benchmark' => '> 15%'],
            'revenue'      => $revenue,
            'net_income'   => $netIncome,
            'gross_profit' => $grossProfit,
        ];
    }

    /**
     * Leverage: Debt-to-Equity, Debt-to-Assets
     */
    private function getLeverageRatios(array $balances): array
    {
        $totalLiabilities = $this->sumByType($balances, 'liability');
        $totalEquity = $this->sumByType($balances, 'equity');
        $totalAssets = $this->sumByType($balances, 'asset');

        $debtToEquity = $totalEquity > 0 ? $totalLiabilities / $totalEquity : 0;
        $debtToAssets = $totalAssets > 0 ? $totalLiabilities / $totalAssets : 0;

        return [
            'debt_to_equity' => ['value' => round($debtToEquity, 2), 'label' => 'Debt to Equity', 'benchmark' => '< 2.0'],
            'debt_to_assets' => ['value' => round($debtToAssets, 2), 'label' => 'Debt to Assets', 'benchmark' => '< 0.5'],
            'total_liabilities' => $totalLiabilities,
            'total_equity'      => $totalEquity,
            'total_assets'      => $totalAssets,
        ];
    }

    /**
     * Efficiency: Receivables Turnover, Payables Turnover, Inventory Turnover
     */
    private function getEfficiencyRatios(array $balances, string $date): array
    {
        $yearStart = substr($date, 0, 4) . '-01-01';

        $revenue = DB::table('journal_items')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_items.journal_entry_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_items.account_id')
            ->where('chart_of_accounts.type', 'revenue')
            ->whereBetween('journal_entries.date', [$yearStart, $date])
            ->selectRaw('COALESCE(SUM(credit) - SUM(debit), 0) as total')
            ->value('total');

        $cogs = DB::table('journal_items')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_items.journal_entry_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_items.account_id')
            ->where('chart_of_accounts.code', 'like', '50%')
            ->whereBetween('journal_entries.date', [$yearStart, $date])
            ->selectRaw('COALESCE(SUM(debit) - SUM(credit), 0) as total')
            ->value('total');

        $ar = $this->sumByPrefix($balances, '12');
        $ap = $this->sumByPrefix($balances, '20');
        $inventory = $this->sumByPrefix($balances, '13');

        $arTurnover = $ar > 0 ? $revenue / $ar : 0;
        $arDays = $arTurnover > 0 ? 365 / $arTurnover : 0;
        $apTurnover = $ap > 0 ? $cogs / $ap : 0;
        $apDays = $apTurnover > 0 ? 365 / $apTurnover : 0;
        $invTurnover = $inventory > 0 ? $cogs / $inventory : 0;
        $invDays = $invTurnover > 0 ? 365 / $invTurnover : 0;

        return [
            'ar_turnover'    => ['value' => round($arTurnover, 1), 'label' => 'AR Turnover', 'benchmark' => '> 6x'],
            'ar_days'        => ['value' => round($arDays, 0), 'label' => 'Days Sales Outstanding', 'benchmark' => '< 60'],
            'ap_turnover'    => ['value' => round($apTurnover, 1), 'label' => 'AP Turnover', 'benchmark' => '> 6x'],
            'ap_days'        => ['value' => round($apDays, 0), 'label' => 'Days Payable Outstanding', 'benchmark' => '< 60'],
            'inv_turnover'   => ['value' => round($invTurnover, 1), 'label' => 'Inventory Turnover', 'benchmark' => '> 4x'],
            'inv_days'       => ['value' => round($invDays, 0), 'label' => 'Days Inventory Outstanding', 'benchmark' => '< 90'],
        ];
    }
}
