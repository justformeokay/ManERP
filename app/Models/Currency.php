<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    protected $fillable = [
        'code', 'name', 'symbol', 'decimal_places', 'is_base', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_base'        => 'boolean',
            'is_active'      => 'boolean',
            'decimal_places' => 'integer',
        ];
    }

    // ── Relationships ───────────────────────────────────────────

    public function exchangeRates(): HasMany
    {
        return $this->hasMany(ExchangeRate::class);
    }

    // ── Helpers ─────────────────────────────────────────────────

    /**
     * Get the exchange rate for a given date (nearest prior rate).
     */
    public function getRateForDate(string $date): float
    {
        if ($this->is_base) {
            return 1.0;
        }

        $rate = $this->exchangeRates()
            ->where('effective_date', '<=', $date)
            ->orderByDesc('effective_date')
            ->value('rate');

        return $rate ? (float) $rate : 1.0;
    }

    /**
     * Convert an amount from this currency to base currency.
     */
    public function toBase(float $amount, float $exchangeRate): float
    {
        return round($amount * $exchangeRate, 2);
    }

    /**
     * Get the base currency instance.
     */
    public static function base(): ?self
    {
        return static::where('is_base', true)->first();
    }

    // ── Scopes ──────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;

        return $query->where(function ($q) use ($term) {
            $q->where('code', 'like', "%{$term}%")
              ->orWhere('name', 'like', "%{$term}%");
        });
    }
}
