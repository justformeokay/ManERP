<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class CompanySetting extends Model
{
    protected $fillable = [
        'name',
        'logo',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'phone',
        'email',
        'website',
        'tax_id',
        'currency',
        'invoice_terms',
        'po_terms',
    ];

    /**
     * Get the company settings (singleton pattern with caching).
     */
    public static function getSettings(): static
    {
        return Cache::remember('company_settings', 3600, function () {
            return static::first() ?? new static([
                'name' => 'Company Name',
                'currency' => 'IDR',
            ]);
        });
    }

    /**
     * Clear cached settings.
     */
    public static function clearCache(): void
    {
        Cache::forget('company_settings');
    }

    /**
     * Get full address formatted.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Get logo URL.
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo) {
            return null;
        }

        return asset('storage/' . $this->logo);
    }

    /**
     * Override save to clear cache.
     */
    public function save(array $options = []): bool
    {
        $result = parent::save($options);
        static::clearCache();
        return $result;
    }
}
