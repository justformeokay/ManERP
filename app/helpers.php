<?php

use App\Models\Setting;
use App\Services\AuditLogService;

if (!function_exists('audit_log')) {
    function audit_log(
        string $module,
        string $action,
        string $description,
        ?array $oldData = null,
        ?array $newData = null
    ) {
        return AuditLogService::log($module, $action, $description, $oldData, $newData);
    }
}

if (!function_exists('format_currency')) {
    /**
     * Format a number as currency using the system's default currency setting.
     */
    function format_currency(float|int|string|null $amount, ?string $currency = null): string
    {
        $amount = (float) ($amount ?? 0);
        $currency = $currency ?? Setting::get('default_currency', 'USD');

        return match ($currency) {
            'IDR' => 'Rp ' . number_format($amount, 0, ',', '.'),
            'USD' => '$ ' . number_format($amount, 2, '.', ','),
            'CNY' => '¥ ' . number_format($amount, 2, '.', ','),
            'KRW' => '₩ ' . number_format($amount, 0, '.', ','),
            'EUR' => '€ ' . number_format($amount, 2, '.', ','),
            'GBP' => '£ ' . number_format($amount, 2, '.', ','),
            'JPY' => '¥ ' . number_format($amount, 0, '.', ','),
            default => $currency . ' ' . number_format($amount, 2, '.', ','),
        };
    }
}

if (!function_exists('currency_symbol')) {
    /**
     * Get the currency symbol for the current or given currency code.
     */
    function currency_symbol(?string $currency = null): string
    {
        $currency = $currency ?? Setting::get('default_currency', 'USD');

        return match ($currency) {
            'IDR' => 'Rp',
            'USD' => '$',
            'CNY' => '¥',
            'KRW' => '₩',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            default => $currency,
        };
    }
}

if (!function_exists('currency_code')) {
    /**
     * Get the active currency code from settings.
     */
    function currency_code(): string
    {
        return Setting::get('default_currency', 'USD');
    }
}

if (!function_exists('app_timezone')) {
    /**
     * Get the application timezone from settings.
     */
    function app_timezone(): string
    {
        return Setting::get('timezone', 'UTC');
    }
}
