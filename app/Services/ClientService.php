<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\SalesOrder;
use Illuminate\Support\Facades\DB;

class ClientService
{
    /**
     * Overdue grace period in days.
     * Invoices past due_date by more than this trigger a hard block.
     */
    public const OVERDUE_GRACE_DAYS = 7;

    /**
     * Calculate total credit exposure for a client.
     *
     * Exposure = Outstanding AR (unpaid/partial invoices)
     *          + Confirmed SO totals (not yet invoiced)
     *
     * Uses DB-level SUM for accuracy and performance.
     */
    public function calculateTotalExposure(int $clientId): string
    {
        // 1. Outstanding AR: sum of remaining balance on unpaid/partial invoices
        $outstandingAR = Invoice::where('client_id', $clientId)
            ->whereIn('status', ['unpaid', 'partial'])
            ->selectRaw('COALESCE(SUM(total_amount - paid_amount), 0) as total')
            ->value('total');

        // 2. Confirmed SO totals not yet invoiced
        //    Status in confirmed, processing, partial, shipped — not yet completed/cancelled
        //    Exclude orders that already have an invoice
        $confirmedSOTotal = SalesOrder::where('client_id', $clientId)
            ->whereIn('status', ['confirmed', 'processing', 'partial', 'shipped'])
            ->whereDoesntHave('invoices')
            ->selectRaw('COALESCE(SUM(total), 0) as total')
            ->value('total');

        return bcadd((string) $outstandingAR, (string) $confirmedSOTotal, 2);
    }

    /**
     * Check if a client has invoices overdue beyond the grace period.
     *
     * @return array{blocked: bool, worst_days: int, count: int}
     */
    public function checkOverdueInvoices(int $clientId): array
    {
        $overdueInvoices = Invoice::where('client_id', $clientId)
            ->whereIn('status', ['unpaid', 'partial'])
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->subDays(self::OVERDUE_GRACE_DAYS)->startOfDay())
            ->get(['id', 'due_date']);

        if ($overdueInvoices->isEmpty()) {
            return ['blocked' => false, 'worst_days' => 0, 'count' => 0];
        }

        $worstDays = $overdueInvoices->max(function ($inv) {
            return (int) now()->startOfDay()->diffInDays($inv->due_date, false) * -1;
        });

        return [
            'blocked'    => true,
            'worst_days' => $worstDays,
            'count'      => $overdueInvoices->count(),
        ];
    }

    /**
     * Validate whether a sales order can be confirmed for the given client.
     *
     * Runs inside a transaction with lockForUpdate to prevent race conditions.
     *
     * @return array{allowed: bool, reason: string|null, exposure: string, limit: string}
     */
    public function validateCreditForConfirmation(int $clientId, string $orderTotal): array
    {
        return DB::transaction(function () use ($clientId, $orderTotal) {
            // Lock the client row to prevent concurrent confirmations
            $client = Client::lockForUpdate()->findOrFail($clientId);

            $result = [
                'allowed'  => true,
                'reason'   => null,
                'exposure' => '0.00',
                'limit'    => (string) $client->credit_limit,
            ];

            // Check 1: Manual credit block
            if ($client->is_credit_blocked) {
                $result['allowed'] = false;
                $result['reason'] = 'credit_blocked';
                return $result;
            }

            // Check 2: Overdue invoices hard block
            $overdue = $this->checkOverdueInvoices($clientId);
            if ($overdue['blocked']) {
                $result['allowed'] = false;
                $result['reason'] = 'overdue_invoices';
                $result['overdue_days'] = $overdue['worst_days'];
                $result['overdue_count'] = $overdue['count'];
                return $result;
            }

            // Check 3: Credit limit (skip if limit == 0 → unlimited)
            $creditLimit = (string) $client->credit_limit;
            if (bccomp($creditLimit, '0', 2) > 0) {
                $currentExposure = $this->calculateTotalExposure($clientId);
                $projectedExposure = bcadd($currentExposure, $orderTotal, 2);

                $result['exposure'] = $currentExposure;

                if (bccomp($projectedExposure, $creditLimit, 2) > 0) {
                    $result['allowed'] = false;
                    $result['reason'] = 'credit_limit_exceeded';
                    $result['exposure'] = $currentExposure;
                    return $result;
                }
            }

            return $result;
        });
    }
}
