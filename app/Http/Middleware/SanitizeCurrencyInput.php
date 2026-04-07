<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Strips currency formatting (thousand separators, symbols) from known
 * numeric input fields before they reach validation and controllers.
 *
 * Only runs on POST/PUT/PATCH requests and only touches fields whose
 * values look like they still contain currency formatting characters.
 */
class SanitizeCurrencyInput
{
    /**
     * Fields that should always be treated as currency / numeric.
     * Add more as needed.
     */
    private const CURRENCY_FIELDS = [
        // HR & Payroll
        'basic_salary', 'fixed_allowance', 'meal_allowance',
        'transport_allowance', 'overtime_rate',
        'bpjs_jp_max_salary', 'bpjs_kes_min_salary', 'bpjs_kes_max_salary',
        'late_deduction_per_minute', 'night_shift_bonus',
        // Accounting & Finance
        'amount', 'tax_amount', 'discount', 'unit_price',
        'purchase_cost', 'salvage_value', 'disposal_amount',
        'opening_balance', 'statement_balance',
        'cost_price', 'sell_price', 'credit_limit', 'budget',
        // Generic
        'debit', 'credit',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET')) {
            $this->sanitize($request);
        }

        return $next($request);
    }

    private function sanitize(Request $request): void
    {
        $symbol    = Setting::get('currency_symbol', 'Rp');
        $thousandSep = Setting::get('thousand_separator', '.');
        $decimalSep  = Setting::get('decimal_separator', ',');

        $input = $request->all();
        $changed = false;

        foreach ($input as $key => $value) {
            if (is_string($value) && $this->isCurrencyField($key, $input)) {
                $clean = $this->stripFormatting($value, $symbol, $thousandSep, $decimalSep);
                if ($clean !== $value) {
                    $input[$key] = $clean;
                    $changed = true;
                }
            }

            // Handle nested arrays (e.g., items[0][unit_price])
            if (is_array($value)) {
                foreach ($value as $idx => $row) {
                    if (! is_array($row)) continue;
                    foreach ($row as $field => $val) {
                        if (is_string($val) && $this->isCurrencyField($field, $row)) {
                            $clean = $this->stripFormatting($val, $symbol, $thousandSep, $decimalSep);
                            if ($clean !== $val) {
                                $input[$key][$idx][$field] = $clean;
                                $changed = true;
                            }
                        }
                    }
                }
            }
        }

        if ($changed) {
            $request->merge($input);
        }
    }

    private function isCurrencyField(string $key, array $context): bool
    {
        if (in_array($key, self::CURRENCY_FIELDS, true)) {
            return true;
        }

        // Match dynamic month fields in budgets (jan, feb, ..., dec)
        if (preg_match('/^(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)$/i', $key)) {
            return true;
        }

        return false;
    }

    private function stripFormatting(string $value, string $symbol, string $thousandSep, string $decimalSep): string
    {
        // Remove currency symbol
        $value = str_replace($symbol, '', $value);
        // Remove thousand separators
        $value = str_replace($thousandSep, '', $value);
        // Convert decimal separator to standard dot
        if ($decimalSep !== '.') {
            $value = str_replace($decimalSep, '.', $value);
        }
        // Trim spaces
        $value = trim($value);
        // Only return if it looks numeric
        if (is_numeric($value)) {
            return $value;
        }

        // If stripping didn't yield a number, return original (let validation catch it)
        return str_replace([' '], '', $value);
    }
}
