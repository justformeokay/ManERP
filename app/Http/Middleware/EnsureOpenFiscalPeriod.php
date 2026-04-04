<?php

namespace App\Http\Middleware;

use App\Models\FiscalPeriod;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOpenFiscalPeriod
{
    /**
     * Financial date fields to inspect in the request, in priority order.
     */
    private const DATE_FIELDS = [
        'date',             // journal entries, stock movements
        'order_date',       // sales/purchase orders
        'invoice_date',     // invoices
        'payment_date',     // payments
        'bill_date',        // supplier bills
    ];

    /**
     * Prevent write operations when the transaction date falls in a closed fiscal period.
     *
     * Usage:
     *   middleware('fiscal-lock')          — auto-detect date from request
     *   middleware('fiscal-lock:bill_date') — explicit date field
     */
    public function handle(Request $request, Closure $next, ?string $dateField = null): Response
    {
        // Only apply to state-changing methods
        if ($request->isMethod('GET')) {
            return $next($request);
        }

        $transactionDate = $this->resolveDate($request, $dateField);

        if ($transactionDate && $this->isInClosedPeriod($transactionDate)) {
            $formatted = date('d M Y', strtotime($transactionDate));

            return back()->withInput()->with(
                'error',
                __('messages.transaction_in_closed_period', [
                    'date' => $formatted,
                ])
            );
        }

        return $next($request);
    }

    /**
     * Detect the transaction date from the request.
     */
    private function resolveDate(Request $request, ?string $dateField): ?string
    {
        // If an explicit field is specified, use it
        if ($dateField) {
            return $request->input($dateField);
        }

        // Auto-detect from common financial date fields
        foreach (self::DATE_FIELDS as $field) {
            if ($date = $request->input($field)) {
                return $date;
            }
        }

        // Check route-bound models for a date
        foreach ($request->route()?->parameters() ?? [] as $param) {
            if (is_object($param) && method_exists($param, 'getAttribute')) {
                foreach (self::DATE_FIELDS as $field) {
                    $value = $param->getAttribute($field);
                    if ($value) {
                        return $value instanceof \DateTimeInterface
                            ? $value->format('Y-m-d')
                            : (string) $value;
                    }
                }
            }
        }

        // Default to today — any write in a closed period must be blocked
        return now()->toDateString();
    }

    /**
     * Check if the date falls within a closed fiscal period.
     */
    private function isInClosedPeriod(string $date): bool
    {
        return FiscalPeriod::closed()
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->exists();
    }
}
