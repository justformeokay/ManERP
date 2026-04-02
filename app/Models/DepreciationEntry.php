<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepreciationEntry extends Model
{
    protected $fillable = [
        'fixed_asset_id', 'period_date', 'depreciation_amount',
        'accumulated_amount', 'book_value', 'journal_entry_id',
    ];

    protected function casts(): array
    {
        return [
            'period_date'          => 'date',
            'depreciation_amount'  => 'decimal:2',
            'accumulated_amount'   => 'decimal:2',
            'book_value'           => 'decimal:2',
        ];
    }

    public function fixedAsset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
