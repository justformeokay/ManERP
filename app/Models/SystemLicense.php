<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class SystemLicense extends Model
{
    protected $fillable = [
        'license_key',
        'license_type',
        'plan_name',
        'user_limit',
        'features_enabled',
        'starts_at',
        'expires_at',
        'is_active',
        'activated_at',
        'company_name',
        'domain',
        'serial_number',
    ];

    protected function casts(): array
    {
        return [
            'features_enabled' => 'array',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'activated_at' => 'datetime',
            'is_active' => 'boolean',
            'user_limit' => 'integer',
        ];
    }

    public const TYPE_SUBSCRIPTION = 'subscription';
    public const TYPE_LIFETIME = 'lifetime';

    public const GRACE_PERIOD_DAYS = 3;

    public function setLicenseKeyAttribute($value): void
    {
        $this->attributes['license_key'] = Crypt::encryptString($value);
    }

    public function getLicenseKeyAttribute($value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception) {
            return null;
        }
    }

    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->license_type === self::TYPE_LIFETIME) {
            return true;
        }

        // Subscription: check expiry with grace period
        if ($this->expires_at) {
            return now()->lte($this->expires_at->addDays(self::GRACE_PERIOD_DAYS));
        }

        return true;
    }

    public function isExpired(): bool
    {
        if ($this->license_type === self::TYPE_LIFETIME) {
            return false;
        }

        if (!$this->expires_at) {
            return false;
        }

        return now()->gt($this->expires_at);
    }

    public function isInGracePeriod(): bool
    {
        if (!$this->isExpired()) {
            return false;
        }

        return now()->lte($this->expires_at->addDays(self::GRACE_PERIOD_DAYS));
    }

    public function userLimitReached(): bool
    {
        $activeUsers = User::where('status', User::STATUS_ACTIVE)->count();
        return $activeUsers >= $this->user_limit;
    }

    public function activeUserCount(): int
    {
        return User::where('status', User::STATUS_ACTIVE)->count();
    }

    public function daysUntilExpiry(): ?int
    {
        if (!$this->expires_at || $this->license_type === self::TYPE_LIFETIME) {
            return null;
        }

        return (int) now()->diffInDays($this->expires_at, false);
    }
}
