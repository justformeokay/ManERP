<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FixedAsset extends Model
{
    protected $fillable = [
        'code', 'name', 'category', 'description', 'purchase_date',
        'purchase_cost', 'useful_life_months', 'salvage_value',
        'depreciation_method', 'accumulated_depreciation', 'book_value',
        'status', 'location', 'coa_asset_id', 'coa_depreciation_id',
        'coa_expense_id', 'disposed_date', 'disposal_amount',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date'          => 'date',
            'purchase_cost'          => 'decimal:2',
            'salvage_value'          => 'decimal:2',
            'accumulated_depreciation' => 'decimal:2',
            'book_value'             => 'decimal:2',
            'disposed_date'          => 'date',
            'disposal_amount'        => 'decimal:2',
        ];
    }

    public function depreciationEntries(): HasMany
    {
        return $this->hasMany(DepreciationEntry::class);
    }

    public function assetAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'coa_asset_id');
    }

    public function depreciationAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'coa_depreciation_id');
    }

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'coa_expense_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isFullyDepreciated(): bool
    {
        return $this->status === 'fully_depreciated';
    }

    public function getDepreciableAmount(): float
    {
        return $this->purchase_cost - $this->salvage_value;
    }

    public function getMonthlyDepreciation(): float
    {
        if ($this->depreciation_method === 'straight_line') {
            return $this->getDepreciableAmount() / max($this->useful_life_months, 1);
        }

        // Declining balance: 2x straight-line rate applied to book value
        $annualRate = (2 / max($this->useful_life_months / 12, 1));
        return ($this->book_value * $annualRate) / 12;
    }

    public static function categoryOptions(): array
    {
        return ['building', 'vehicle', 'equipment', 'furniture', 'other'];
    }

    public static function methodOptions(): array
    {
        return ['straight_line', 'declining_balance'];
    }
}
