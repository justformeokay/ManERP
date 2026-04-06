<?php

namespace App\Services;

use App\Models\FiscalPeriod;
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
 *
 * All arithmetic uses bcmath (scale 2) before rounding for final output.
 */
class CashFlowService
{
    private const BC_SCALE = 2;

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

        // ── Core calculations (bcmath) ─────────────────────────
        $netIncome    = $this->getNetIncome($startDate, $endDate);
        $depreciation = $this->getDepreciationAmortization($startDate, $endDate);

        $operatingItems = $this->getOperatingActivities($startDate, $endDate);
        $investingItems = $this->getInvestingActivities($startDate, $endDate);
        $financingItems = $this->getFinancingActivities($startDate, $endDate);

        // Prepend depreciation add-back before working-capital items
        $operating = [];
        if (bccomp($depreciation, '0', self::BC_SCALE) !== 0) {
            $operating[] = [
                'label'  => 'depreciation_amortization',
                'amount' => (float) $depreciation,
            ];
        }
        $operating = array_merge($operating, $operatingItems);

        // Sum with bcmath
        $opSum = '0';
        foreach ($operating as $item) {
            $opSum = bcadd($opSum, (string) $item['amount'], self::BC_SCALE);
        }
        $totalOperating = (float) bcadd($netIncome, $opSum, self::BC_SCALE);

        $invSum = '0';
        foreach ($investingItems as $item) {
            $invSum = bcadd($invSum, (string) $item['amount'], self::BC_SCALE);
        }
        $totalInvesting = (float) $invSum;

        $finSum = '0';
        foreach ($financingItems as $item) {
            $finSum = bcadd($finSum, (string) $item['amount'], self::BC_SCALE);
        }
        $totalFinancing = (float) $finSum;

        $netCashChange = (float) bcadd(
            bcadd((string) $totalOperating, (string) $totalInvesting, self::BC_SCALE),
            (string) $totalFinancing,
            self::BC_SCALE
        );

        // ── Cash balances ──────────────────────────────────────
        $beginningCash      = $this->getCashBalance($startDate, false);
        $computedEndingCash = (float) bcadd((string) $beginningCash, (string) $netCashChange, self::BC_SCALE);

        // ── Reconciliation (Golden Rule) ───────────────────────
        $actualEndingCash  = $this->getCashBalance($endDate, true);
        $discrepancyAmount = (float) bcsub((string) $actualEndingCash, (string) $computedEndingCash, self::BC_SCALE);
        $hasDiscrepancy    = bccomp((string) abs($discrepancyAmount), '0.01', self::BC_SCALE) >= 0;

        // ── Fiscal period draft detection ──────────────────────
        $isDraft = $this->isAnyPeriodOpen($startDate, $endDate);

        return [
            'start_date'          => $startDate,
            'end_date'            => $endDate,
            'net_income'          => (float) $netIncome,
            'depreciation'        => (float) $depreciation,
            'operating'           => $operating,
            'total_operating'     => $totalOperating,
            'investing'           => $investingItems,
            'total_investing'     => $totalInvesting,
            'financing'           => $financingItems,
            'total_financing'     => $totalFinancing,
            'net_cash_change'     => $netCashChange,
            'beginning_cash'      => (float) $beginningCash,
            'ending_cash'         => $computedEndingCash,
            'actual_ending_cash'  => (float) $actualEndingCash,
            'discrepancy_amount'  => $discrepancyAmount,
            'has_discrepancy'     => $hasDiscrepancy,
            'is_draft'            => $isDraft,
        ];
    }

    /**
     * Detect if the reporting period overlaps any open fiscal period.
     */
    public function isAnyPeriodOpen(string $startDate, string $endDate): bool
    {
        return FiscalPeriod::where('status', 'open')
            ->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate)
            ->exists();
    }

    // ══════════════════════════════════════════════════════════
    //  Net Income
    // ══════════════════════════════════════════════════════════

    private function getNetIncome(string $start, string $end): string
    {
        $rows = DB::table('journal_items')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_items.journal_entry_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_items.account_id')
            ->where('journal_entries.is_posted', true)
            ->whereDate('journal_entries.date', '>=', $start)
            ->whereDate('journal_entries.date', '<=', $end)
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
            ? bcsub((string) $rows['revenue']->total_credit, (string) $rows['revenue']->total_debit, self::BC_SCALE)
            : '0';

        $expense = isset($rows['expense'])
            ? bcsub((string) $rows['expense']->total_debit, (string) $rows['expense']->total_credit, self::BC_SCALE)
            : '0';

        return bcsub($revenue, $expense, self::BC_SCALE);
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
    private function getDepreciationAmortization(string $start, string $end): string
    {
        // Method 1: tagged depreciation journals (DEP- prefix)
        $depByRef = DB::table('journal_items')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_items.journal_entry_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_items.account_id')
            ->where('journal_entries.is_posted', true)
            ->whereDate('journal_entries.date', '>=', $start)
            ->whereDate('journal_entries.date', '<=', $end)
            ->where('journal_entries.reference', 'like', 'DEP-%')
            ->where('chart_of_accounts.type', 'expense')
            ->select(DB::raw('SUM(journal_items.debit) - SUM(journal_items.credit) as net'))
            ->value('net');

        // Method 2: credit to accumulated depreciation accounts (asset accounts used as contra)
        // on fixed_assets table via coa_depreciation_id
        $depByContra = DB::table('journal_items')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_items.journal_entry_id')
            ->where('journal_entries.is_posted', true)
            ->whereDate('journal_entries.date', '>=', $start)
            ->whereDate('journal_entries.date', '<=', $end)
            ->where('journal_entries.reference', 'not like', 'DEP-%')
            ->whereIn('journal_items.account_id', function ($q) {
                $q->select('coa_depreciation_id')
                  ->from('fixed_assets')
                  ->whereNotNull('coa_depreciation_id');
            })
            ->select(DB::raw('SUM(journal_items.credit) - SUM(journal_items.debit) as net'))
            ->value('net');

        $a = bcabs((string) ($depByRef ?? '0'), self::BC_SCALE);
        $b = bcabs((string) ($depByContra ?? '0'), self::BC_SCALE);

        return bcadd($a, $b, self::BC_SCALE);
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
            if (bccomp(bcabs((string) $item['change'], self::BC_SCALE), '0.01', self::BC_SCALE) >= 0) {
                $items[] = [
                    'label'  => $this->makeLabel($item['code'], $item['name']),
                    'amount' => (float) bcmul((string) $item['change'], '-1', self::BC_SCALE),
                ];
            }
        }

        // Current liabilities (AP, Tax Payable, Accrued, etc.)
        // Liability increase → positive cash flow; decrease → negative cash flow
        foreach ($this->groupAccountChanges($liabilityAccounts, $start, $end) as $item) {
            if (bccomp(bcabs((string) $item['change'], self::BC_SCALE), '0.01', self::BC_SCALE) >= 0) {
                $items[] = [
                    'label'  => $this->makeLabel($item['code'], $item['name']),
                    'amount' => (float) $item['change'],
                ];
            }
        }

        // Manually tagged operating entries on journal_entries.cash_flow_category
        $manualOp = $this->getTaggedCashFlowAmount('operating', $start, $end);
        if (bccomp(bcabs($manualOp, self::BC_SCALE), '0.01', self::BC_SCALE) >= 0) {
            $items[] = [
                'label'  => 'other_operating',
                'amount' => (float) $manualOp,
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
            if (bccomp(bcabs((string) $item['change'], self::BC_SCALE), '0.01', self::BC_SCALE) >= 0) {
                $items[] = [
                    'label'  => $this->makeLabel($item['code'], $item['name']),
                    'amount' => (float) bcmul((string) $item['change'], '-1', self::BC_SCALE),
                ];
            }
        }

        // Manually tagged investing entries
        $manualInv = $this->getTaggedCashFlowAmount('investing', $start, $end);
        if (bccomp(bcabs($manualInv, self::BC_SCALE), '0.01', self::BC_SCALE) >= 0) {
            $items[] = [
                'label'  => 'other_investing',
                'amount' => (float) $manualInv,
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
            if (bccomp(bcabs((string) $item['change'], self::BC_SCALE), '0.01', self::BC_SCALE) >= 0) {
                $change = $item['type'] === 'asset'
                    ? bcmul((string) $item['change'], '-1', self::BC_SCALE)
                    : (string) $item['change'];

                $items[] = [
                    'label'  => $this->makeLabel($item['code'], $item['name']),
                    'amount' => (float) $change,
                ];
            }
        }

        // Manually tagged financing entries
        $manualFin = $this->getTaggedCashFlowAmount('financing', $start, $end);
        if (bccomp(bcabs($manualFin, self::BC_SCALE), '0.01', self::BC_SCALE) >= 0) {
            $items[] = [
                'label'  => 'other_financing',
                'amount' => (float) $manualFin,
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
            ->whereDate('journal_entries.date', '>=', $start)
            ->whereDate('journal_entries.date', '<=', $end)
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

            $change = bcsub((string) ($row->total_debit ?? '0'), (string) ($row->total_credit ?? '0'), self::BC_SCALE);
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
    private function getTaggedCashFlowAmount(string $category, string $start, string $end): string
    {
        $row = DB::table('journal_items')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_items.journal_entry_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_items.account_id')
            ->where('journal_entries.is_posted', true)
            ->where('journal_entries.cash_flow_category', $category)
            ->whereDate('journal_entries.date', '>=', $start)
            ->whereDate('journal_entries.date', '<=', $end)
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

        if (!$row) return '0';

        return bcsub((string) ($row->total_debit ?? '0'), (string) ($row->total_credit ?? '0'), self::BC_SCALE);
    }

    // ══════════════════════════════════════════════════════════
    //  Cash Balance
    // ══════════════════════════════════════════════════════════

    /**
     * Get the cash/bank balance as of a date.
     * Uses cash_flow_category='cash' with fallback to CASH_CODES.
     */
    private function getCashBalance(string $date, bool $inclusive = true): string
    {
        $operator = $inclusive ? '<=' : '<';

        $row = DB::table('journal_items')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_items.journal_entry_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_items.account_id')
            ->where('journal_entries.is_posted', true)
            ->whereDate('journal_entries.date', $operator, $date)
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

        if (!$row) return '0';

        return bcsub((string) ($row->total_debit ?? '0'), (string) ($row->total_credit ?? '0'), self::BC_SCALE);
    }

    // ══════════════════════════════════════════════════════════
    //  Helpers
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

/**
 * bcmath absolute value (not provided by ext-bcmath).
 */
function bcabs(string $value, int $scale = 2): string
{
    if (bccomp($value, '0', $scale) < 0) {
        return bcmul($value, '-1', $scale);
    }
    return bcadd($value, '0', $scale); // normalise
}
