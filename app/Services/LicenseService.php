<?php

namespace App\Services;

use App\Models\SystemLicense;
use Illuminate\Support\Facades\Cache;

class LicenseService
{
    private const CACHE_KEY = 'system_license_status';
    private const CACHE_TTL = 300; // 5 minutes

    public static function current(): ?SystemLicense
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return SystemLicense::where('is_active', true)->latest()->first();
        });
    }

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public static function generateSerialNumber(string $companyName, string $domain): string
    {
        $salt = config('app.key');
        return hash('sha256', $companyName . '|' . $domain . '|' . $salt);
    }

    public static function validateSerialNumber(string $serialNumber, string $companyName, string $domain): bool
    {
        $expected = self::generateSerialNumber($companyName, $domain);
        return hash_equals($expected, $serialNumber);
    }

    public static function activate(string $serialNumber, string $companyName, string $domain): array
    {
        if (!self::validateSerialNumber($serialNumber, $companyName, $domain)) {
            AuditLogService::log(
                'license',
                'activation_failed',
                "Failed license activation attempt for {$companyName} ({$domain})"
            );

            return ['success' => false, 'message' => 'license_invalid_serial'];
        }

        $license = SystemLicense::where('is_active', true)->latest()->first();

        if (!$license) {
            return ['success' => false, 'message' => 'license_not_found'];
        }

        $license->update([
            'serial_number' => $serialNumber,
            'company_name' => $companyName,
            'domain' => $domain,
            'activated_at' => now(),
        ]);

        self::clearCache();

        AuditLogService::log(
            'license',
            'activated',
            "License activated for {$companyName} ({$domain})",
            null,
            [
                'company_name' => $companyName,
                'domain' => $domain,
                'plan_name' => $license->plan_name,
            ],
            $license
        );

        return ['success' => true, 'message' => 'license_activated'];
    }

    public static function isValid(): bool
    {
        $license = self::current();
        return $license && $license->isValid();
    }

    public static function userLimitReached(): bool
    {
        $license = self::current();
        return $license && $license->userLimitReached();
    }
}
