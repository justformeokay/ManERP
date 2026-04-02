<?php

namespace App\Services;

use App\Models\DepreciationEntry;
use App\Models\FixedAsset;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;

class FixedAssetService
{
    private AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    /**
     * Calculate and record monthly depreciation for a single asset.
     */
    public function depreciateAsset(FixedAsset $asset, string $periodDate): ?DepreciationEntry
    {
        if (!$asset->isActive()) {
            return null;
        }

        // Check already depreciated for this period
        $existing = DepreciationEntry::where('fixed_asset_id', $asset->id)
            ->where('period_date', $periodDate)
            ->first();

        if ($existing) {
            return $existing;
        }

        $amount = $this->calculateDepreciation($asset);

        if ($amount <= 0) {
            return null;
        }

        // Don't exceed depreciable amount
        $maxRemaining = $asset->book_value - $asset->salvage_value;
        if ($amount > $maxRemaining) {
            $amount = $maxRemaining;
        }

        $newAccumulated = $asset->accumulated_depreciation + $amount;
        $newBookValue = $asset->purchase_cost - $newAccumulated;

        return DB::transaction(function () use ($asset, $periodDate, $amount, $newAccumulated, $newBookValue) {
            // Create journal entry if accounts are set
            $journalEntry = null;
            if ($asset->coa_expense_id && $asset->coa_depreciation_id) {
                $journalEntry = $this->accountingService->createJournalEntry(
                    'DEP-' . $asset->code,
                    $periodDate,
                    "Depreciation: {$asset->name} ({$asset->code})",
                    [
                        ['account_id' => $asset->coa_expense_id, 'debit' => $amount, 'credit' => 0],
                        ['account_id' => $asset->coa_depreciation_id, 'debit' => 0, 'credit' => $amount],
                    ]
                );
            }

            // Record depreciation entry
            $entry = DepreciationEntry::create([
                'fixed_asset_id'      => $asset->id,
                'period_date'         => $periodDate,
                'depreciation_amount' => $amount,
                'accumulated_amount'  => $newAccumulated,
                'book_value'          => $newBookValue,
                'journal_entry_id'    => $journalEntry?->id,
            ]);

            // Update asset
            $asset->update([
                'accumulated_depreciation' => $newAccumulated,
                'book_value'               => $newBookValue,
                'status'                   => $newBookValue <= $asset->salvage_value ? 'fully_depreciated' : 'active',
            ]);

            return $entry;
        });
    }

    /**
     * Calculate monthly depreciation amount.
     */
    private function calculateDepreciation(FixedAsset $asset): float
    {
        if ($asset->depreciation_method === 'straight_line') {
            $depreciableAmount = $asset->purchase_cost - $asset->salvage_value;
            return $depreciableAmount / max($asset->useful_life_months, 1);
        }

        // Declining balance (double rate)
        $annualRate = 2 / max($asset->useful_life_months / 12, 1);
        $monthly = ($asset->book_value * $annualRate) / 12;

        // Don't go below salvage value
        $maxAllowed = $asset->book_value - $asset->salvage_value;
        return min($monthly, max($maxAllowed, 0));
    }

    /**
     * Run monthly depreciation for all active assets.
     */
    public function runMonthlyDepreciation(?string $periodDate = null): array
    {
        $periodDate = $periodDate ?? now()->startOfMonth()->toDateString();
        $assets = FixedAsset::where('status', 'active')->get();
        $results = ['processed' => 0, 'skipped' => 0, 'entries' => []];

        foreach ($assets as $asset) {
            $entry = $this->depreciateAsset($asset, $periodDate);
            if ($entry) {
                $results['processed']++;
                $results['entries'][] = $entry;
            } else {
                $results['skipped']++;
            }
        }

        return $results;
    }

    /**
     * Dispose an asset (sale or write-off).
     */
    public function disposeAsset(FixedAsset $asset, string $date, float $disposalAmount = 0): FixedAsset
    {
        return DB::transaction(function () use ($asset, $date, $disposalAmount) {
            $gainLoss = $disposalAmount - $asset->book_value;

            // Create disposal journal if accounts are set
            if ($asset->coa_asset_id && $asset->coa_depreciation_id) {
                $entries = [
                    // Remove accumulated depreciation (debit)
                    ['account_id' => $asset->coa_depreciation_id, 'debit' => $asset->accumulated_depreciation, 'credit' => 0],
                    // Remove asset cost (credit)
                    ['account_id' => $asset->coa_asset_id, 'debit' => 0, 'credit' => $asset->purchase_cost],
                ];

                if ($disposalAmount > 0) {
                    // Record cash received
                    $cashAccount = \App\Models\ChartOfAccount::where('code', '1100')->first();
                    if ($cashAccount) {
                        $entries[] = ['account_id' => $cashAccount->id, 'debit' => $disposalAmount, 'credit' => 0];
                    }
                }

                // Record gain or loss
                if ($gainLoss != 0) {
                    $glAccount = \App\Models\ChartOfAccount::where('code', 'like', $gainLoss > 0 ? '41%' : '59%')->first();
                    if ($glAccount) {
                        if ($gainLoss > 0) {
                            $entries[] = ['account_id' => $glAccount->id, 'debit' => 0, 'credit' => $gainLoss];
                        } else {
                            $entries[] = ['account_id' => $glAccount->id, 'debit' => abs($gainLoss), 'credit' => 0];
                        }
                    }
                }

                $this->accountingService->createJournalEntry(
                    'DISP-' . $asset->code,
                    $date,
                    "Disposal of asset: {$asset->name}",
                    $entries
                );
            }

            $asset->update([
                'status'          => $disposalAmount > 0 ? 'sold' : 'disposed',
                'disposed_date'   => $date,
                'disposal_amount' => $disposalAmount,
            ]);

            return $asset->fresh();
        });
    }

    /**
     * Get depreciation schedule projection for an asset.
     */
    public function getDepreciationSchedule(FixedAsset $asset): array
    {
        $schedule = [];
        $bookValue = $asset->purchase_cost;
        $accumulated = 0;

        for ($month = 1; $month <= $asset->useful_life_months; $month++) {
            $amount = $this->calculateProjectedDepreciation($asset, $bookValue);
            $maxRemaining = $bookValue - $asset->salvage_value;
            $amount = min($amount, max($maxRemaining, 0));

            if ($amount <= 0) break;

            $accumulated += $amount;
            $bookValue -= $amount;

            $periodDate = $asset->purchase_date->copy()->addMonths($month)->startOfMonth();

            $schedule[] = [
                'month'        => $month,
                'period'       => $periodDate->format('M Y'),
                'depreciation' => round($amount, 2),
                'accumulated'  => round($accumulated, 2),
                'book_value'   => round($bookValue, 2),
            ];
        }

        return $schedule;
    }

    private function calculateProjectedDepreciation(FixedAsset $asset, float $currentBookValue): float
    {
        if ($asset->depreciation_method === 'straight_line') {
            return ($asset->purchase_cost - $asset->salvage_value) / max($asset->useful_life_months, 1);
        }

        $annualRate = 2 / max($asset->useful_life_months / 12, 1);
        return ($currentBookValue * $annualRate) / 12;
    }
}
