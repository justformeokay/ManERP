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

if (!function_exists('currency_config')) {
    /**
     * Get the full currency configuration from localization settings.
     * Returns an array with: symbol, thousand_separator, decimal_separator, decimal_places, code.
     */
    function currency_config(): array
    {
        return [
            'symbol'             => Setting::get('currency_symbol', 'Rp'),
            'thousand_separator' => Setting::get('thousand_separator', '.'),
            'decimal_separator'  => Setting::get('decimal_separator', ','),
            'decimal_places'     => (int) Setting::get('decimal_places', '0'),
            'code'               => Setting::get('default_currency', 'IDR'),
        ];
    }
}

if (!function_exists('format_currency')) {
    /**
     * Format a number as currency using the system's localization settings.
     * Uses bcmath for precision when available.
     */
    function format_currency(float|int|string|null $amount, ?string $currency = null): string
    {
        $cfg    = currency_config();
        $raw    = $amount ?? 0;
        $places = $cfg['decimal_places'];

        if (function_exists('bcadd')) {
            $value = bcadd((string) $raw, '0', $places);
        } else {
            $value = number_format((float) $raw, $places, '.', '');
        }

        // Split integer and decimal parts
        $parts   = explode('.', $value, 2);
        $integer = ltrim($parts[0], '-') ?: '0';
        $neg     = str_starts_with($parts[0], '-');

        // Add thousand separators
        $integer = strrev(implode($cfg['thousand_separator'], str_split(strrev($integer), 3)));

        $formatted = $integer;
        if ($places > 0 && isset($parts[1])) {
            $formatted .= $cfg['decimal_separator'] . str_pad($parts[1], $places, '0');
        }

        return ($neg ? '-' : '') . $cfg['symbol'] . ' ' . $formatted;
    }
}

if (!function_exists('currency_symbol')) {
    /**
     * Get the currency symbol from localization settings.
     */
    function currency_symbol(?string $currency = null): string
    {
        // If a specific currency is requested, return its known symbol
        if ($currency !== null && $currency !== Setting::get('default_currency', 'IDR')) {
            return match ($currency) {
                'IDR' => 'Rp', 'USD' => '$', 'CNY' => '¥', 'KRW' => '₩',
                'EUR' => '€',  'GBP' => '£', 'JPY' => '¥', 'SGD' => 'S$',
                'MYR' => 'RM', 'AUD' => 'A$',
                default => $currency,
            };
        }

        return Setting::get('currency_symbol', 'Rp');
    }
}

if (!function_exists('currency_code')) {
    /**
     * Get the active currency code from settings.
     */
    function currency_code(): string
    {
        return Setting::get('default_currency', 'IDR');
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

if (!function_exists('formatBytes')) {
    function formatBytes(int|float $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        return round($bytes / (1024 ** $pow), $precision) . ' ' . $units[$pow];
    }
}
