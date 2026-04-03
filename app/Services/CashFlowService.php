<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Cash Flow Statement — Indirect Method (PSAK 2 / IAS 7).
 *
 * Starts from net income and adjusts for:
 *  1. Non-cash charges (depreciation, amortization)
 *  2. Changes in operating working capital
 *  3. Investing activities (fixed/intangible assets, LT investments)
 *  4. Financing activities (debt, equity)
 *
 * Account categorisation is driven by `chart_of_accounts.cash_flow_category`:
 *   operating | investing | financing | cash | none
 */
class CashFlowService
{
    /**
     * Default COA codes for cash/bank accounts.
     */
    public const CASH_CODES = ['1100', '1101', '1102', '1110'];

    /**
     * Generate full Cash Flow Statement with reconciliation.
     */
    public function getCashFlowStatement(?string $startDate = null, ?string $endDate = null): array
    {
        $endDate   ??= now()->toDateString();
        $startDate ??= now()->startOfYear()->toDateString();

        // ── Core calculations ──────────────────────────────────
        $netIncome    = $this->getNetIncome($startDate, $endDate);
        $depreciation = $this->getDepreciationAmortization($startDate, $endDate);

        $operatingItems = $this->getOperatingActivities($startDate, $endDate);
        $investingItems = $this->getInvestingActivities($startDate, $endDate);
        $financingItems = $this->getFinancingActivities($startDate, $endDate);

        // Prepend depreciation add-back before working-capital items
        $operating = [];
        if (abs($depreciation) > 0.01) {
            $operating[] = [
                'label'  => 'depreciation_amortization',
                'amount' => round($depreciation, 2),
            ];
        }
        $operating = array_merge($operating, $operatingItems);

        $totalOperating = round($netIncome + array_sum(array_column($operating, 'amount')), 2);
        $totalInvesting = round(array_sum(array_column($investingItems, 'amount')), 2);
        $totalFinancing = round(array_sum(array_column($financingItems, 'amount')), 2);
        $netCashChange  = round($totalOperating + $totalInvesting + $totalFinancing, 2);

        // ── Cash balances ──────────────────────────────────────
        $beginningCash     = $this->getCashBalance($startDate, false);
        $computedEndingCash = round($beginningCash + $netCashChange, 2);

        // ── Reconciliation (Golden Rule) ───────────────────────
        $actualEndingCash  = $this->getCashBalance($endDate, true);
        $discrepancyAmount = round($actualEndingCash - $computedEndingCash, 2);
        $hasDiscrepancy    = abs($discrepancyAmount) > 0.01;

        return [
            'start_date'          => $startDate,
            'end_date'            => $endDate,
            'net_income'          => round($netIncome, 2),
            'depreciation'        => round($depreciation, 2),
            'operating'           => $operating,
            'total_operating'     => $totalOperating,
            'investing'           => $investingItems,
            'total_investing'     => $totalInvesting,
            'financing'           => $financingItems,
            'total_financing'     => $totalFinancing,
            'net_cash_change'     => $netCashChange,
            'beginning_cash'      => round($beginningCash, 2),
            'ending_cash'         => $computedEndingCash,
            'actual_ending_cash'  => round($actualEndingCash, 2),
            'discrepancy_amount'  => $discrepancyAmount,
            'has_discrepancy'     => $hasDiscrepancy,
        ];
    }

    // ══════════════════════════════════════════════════════════
    //  Net Income
    // ══════════════════════════════════════════════════════════

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
                DB::raw('SUM(journal_items.debit)  as total_debit'),
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

    // ══════════════════════════════════════════════════════════
    //  Depreciation / Amortization Add-Back
    // ══════════════════════════════════════════════════════════

    /**
     * Sum depreciation & amortization expense journals for the period.
     *
     * Detects depreciation entries two ways:
     *  1. Journal reference starts with 'DEP-' (FixedAssetService convention)
     *  2. Credit side hits accumulated depreciation accounts (contra-asset)
     *
     * Returns positive value (add-back to net income).
     */
    private function getDepreciationAmortization(string $start, string $end): float
    {
        // Method 1: tagged depreciation journals (DEP- prefix)
        $depByRef = DB::table('journal_items')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_items.journal_entry_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_items.account_id')
            ->where('journal_entries.is_posted', true)
            ->whereBetween('journal_entries.date', [$start, $end])
            ->where('journal_entries.reference', 'like', 'DEP-%')
            ->where('chart_of_accounts.type', 'expense')
            ->select(DB::raw('SUM(journal_items.debit) - SUM(journal_items.credit) as net'))
            ->value('net');

        // Method 2: credit to accumulated depreciation accounts (asset accounts used as contra)
        // on fixed_assets table via coa_depreciation_id
        $depByContra = DB::table('journal_items')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_items.journal_entry_id')
            ->where('journal_entries.is_posted', true)
            ->whereBetween('journal_entries.date', [$start, $end])
            ->where('journal_entries.reference', 'not like', 'DEP-%')
            ->whereIn('journal_items.account_id', function ($q) {
                $q->select('coa_depreciation_id')
                  ->from('fixed_assets')
                  ->whereNotNull('coa_depreciation_id');
            })
            ->select(DB::raw('SUM(journal_items.credit) - SUM(journal_items.debit) as net'))
            ->value('net');

        return round(abs((float) $depByRef) + abs((float) $depByContra), 2);
    }

    // ══════════════════════════════════════════════════════════
    //  Operating Activities (Working Capital Changes)
    // ══════════════════════════════════════════════════════════

    private function getOperatingActivities(string $start, string $end): array
    {
        $items = [];

        // Query all accounts categorised as 'operating' in COA
        $accounts = DB::table('chart_of_accounts')
            ->where('cash_flow_category', 'operating')
            ->where('is_active', true)
            ->select('id', 'code', 'name', 'type')
            ->orderBy('code')
            ->get();

        // Group by type for proper sign treatment
        $assetAccounts     = $accounts->where('type', 'asset');
        $liabilityAccounts = $accounts->where('type', 'liability');

        // Current assets (AR, Inventory, Prepaid, etc.)
        // Asset increase → negative cash flow; decrease → positive cash flow
        foreach ($this->groupAccountChanges($assetAccounts, $start, $end) as $item) {
            if (abs($item['change']) > 0.01) {
                $items[] = [
                    'label'  => $this->makeLabel($item['code'], $item['name']),
                    'amount' => round(-$item['change'], 2), // Invert for indirect method
                ];
            }
        }

        // Current liabilities (AP, Tax Payable, Accrued, etc.)
        // Liability increase → positive cash flow; decrease → negative cash flow
        foreach ($this->groupAccountChanges($liabilityAccounts, $start, $end) as $item) {
            if (abs($item['change']) > 0.01) {
                $items[] = [
                    'label'  => $this->makeLabel($item['code'], $item['name']),
                    'amount' => round($item['change'], 2),
                ];
            }
        }

        // Manually tagged operating entries on journal_entries.cash_flow_category
        $manualOp = $this->getTaggedCashFlowAmount('operating', $start, $end);
        if (abs($manualOp) > 0.01) {
            $items[] = [
                'label'  => 'other_operating',
                'amount' => round($manualOp, 2),
            ];
        }

        return $items;
    }

    // ══════════════════════════════════════════════════════════
    //  Investing Activities
    // ══════════════════════════════════════════════════════════

    private function getInvestingActivities(string $start, string $end): array
    {
        $items = [];

        $accounts = DB::table('chart_of_accounts')
            ->where('cash_flow_category', 'investing')
            ->where('is_active', true)
            ->select('id', 'code', 'name', 'type')
            ->orderBy('code')
            ->get();

        // For investing accounts: net increase in assets = cash outflow (negative)
        foreach ($this->groupAccountChanges($accounts, $start, $end) as $item) {
            if (abs($item['change']) > 0.01) {
                $items[] = [
                    'label'  => $this->makeLabel($item['code'], $item['name']),
                    'amount' => round(-$item['change'], 2),
                ];
            }
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

    // ══════════════════════════════════════════════════════════
    //  Financing Activities
    // ══════════════════════════════════════════════════════════

    private function getFinancingActivities(string $start, string $end): array
    {
        $items = [];

        $accounts = DB::table('chart_of_accounts')
            ->where('cash_flow_category', 'financing')
            ->where('is_active', true)
            ->select('id', 'code', 'name', 'type')
            ->orderBy('code')
            ->get();

        // Liability/equity increase = cash inflow; decrease = cash outflow
        foreach ($this->groupAccountChanges($accounts, $start, $end) as $item) {
            if (abs($item['change']) > 0.01) {
                $change = $item['type'] === 'asset'
                    ? -$item['change']  // Asset-type financing (rare)
                    : $item['change'];  // Liability & equity: natural sign

                $items[] = [
                    'label'  => $this->makeLabel($item['code'], $item['name']),
                    'amount' => round($change, 2),
                ];
            }
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

    // ══════════════════════════════════════════════════════════
    //  Helper: Account Balance Changes (Period Delta)
    // ══════════════════════════════════════════════════════════

    /**
     * Get net debit−credit change per account for the period.
     *
     * @param  \Illuminate\Support\Collection  $accounts
     * @return array  [{code, name, type, change}]
     */
    private function groupAccountChanges($accounts, string $start, string $end): array
    {
        if ($accounts->isEmpty()) {
            return [];
        }

        $accountIds = $accounts->pluck('id')->toArray();

        $rows = DB::table('journal_items')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_items.journal_entry_id')
            ->where('journal_entries.is_posted', true)
            ->whereBetween('journal_entries.date', [$start, $end])
            ->whereIn('journal_items.account_id', $accountIds)
            ->select(
                'journal_items.account_id',
                DB::raw('SUM(journal_items.debit)  as total_debit'),
                DB::raw('SUM(journal_items.credit) as total_credit')
            )
            ->groupBy('journal_items.account_id')
            ->get()
            ->keyBy('account_id');

        $results = [];
        foreach ($accounts as $acct) {
            $row = $rows->get($acct->id);
            if (!$row) continue;

            $change = round((float) $row->total_debit - (float) $row->total_credit, 2);
            $results[] = [
                'code'   => $acct->code,
                'name'   => $acct->name,
                'type'   => $acct->type,
                'change' => $change,
            ];
        }

        return $results;
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
            ->where(function ($q) {
                foreach (self::CASH_CODES as $code) {
                    $q->orWhere('chart_of_accounts.code', $code);
                }
            })
            ->select(
                DB::raw('SUM(journal_items.debit)  as total_debit'),
                DB::raw('SUM(journal_items.credit) as total_credit')
            )
            ->first();

        if (!$row) return 0;

        return round((float) $row->total_debit - (float) $row->total_credit, 2);
    }

    // ══════════════════════════════════════════════════════════
    //  Cash Balance
    // ══════════════════════════════════════════════════════════

    /**
     * Get the cash/bank balance as of a date.
     * Uses cash_flow_category='cash' with fallback to CASH_CODES.
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
                $q->where('chart_of_accounts.cash_flow_category', 'cash')
                  ->orWhere(function ($q2) {
                      foreach (self::CASH_CODES as $code) {
                          $q2->orWhere('chart_of_accounts.code', $code);
                      }
                  });
            })
            ->select(
                DB::raw('SUM(journal_items.debit)  as total_debit'),
                DB::raw('SUM(journal_items.credit) as total_credit')
            )
            ->first();

        if (!$row) return 0;

        return round((float) $row->total_debit - (float) $row->total_credit, 2);
    }

    // ══════════════════════════════════════════════════════════
    //  Label Helper
    // ══════════════════════════════════════════════════════════

    /**
     * Generate a display label from account code & name.
     * Checks for a translation key first, falls back to name.
     */
    private function makeLabel(string $code, string $name): string
    {
        // Use snake_case of account name as translation key
        $key = 'cf_' . str_replace([' ', '&', '-'], ['_', '', '_'], strtolower($name)) . '_change';
        $translated = __('messages.' . $key);

        // If translation exists, use the key for the view; otherwise use raw name
        if ($translated !== 'messages.' . $key) {
            return $key;
        }

        // Fallback: return a descriptive label
        return "change_in_{$code}_{$name}";
    }
}
