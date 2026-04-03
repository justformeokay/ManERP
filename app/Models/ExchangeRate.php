<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExchangeRate extends Model
{
    protected $fillable = [
        'currency_id', 'effective_date', 'rate', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'rate'           => 'decimal:6',
        ];
    }

    // ── Relationships ───────────────────────────────────────────

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Helpers ─────────────────────────────────────────────────

    /**
     * Get the effective rate for a currency on a given date.
     * Falls back to the nearest prior date.
     */
    public static function getRate(int $currencyId, string $date): float
    {
        $rate = static::where('currency_id', $currencyId)
            ->where('effective_date', '<=', $date)
            ->orderByDesc('effective_date')
            ->value('rate');

        return $rate ? (float) $rate : 1.0;
    }

    // ── Scopes ──────────────────────────────────────────────────

    public function scopeForCurrency($query, int $currencyId)
    {
        return $query->where('currency_id', $currencyId);
    }

    public function scopeOnDate($query, string $date)
    {
        return $query->where('effective_date', $date);
    }
}
