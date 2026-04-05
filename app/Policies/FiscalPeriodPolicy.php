<?php

namespace App\Policies;

use App\Models\FiscalPeriod;
use App\Models\User;

/**
 * Phase 7 — Fiscal Period Policy (Segregation of Duties)
 *
 * Only users with 'accounting.close_period' can close/reopen periods.
 * Standard CRUD follows existing permission middleware.
 */
class FiscalPeriodPolicy
{
    public function close(User $user, FiscalPeriod $period): bool
    {
        return $user->hasPermission('accounting.close_period');
    }

    public function reopen(User $user, FiscalPeriod $period): bool
    {
        return $user->hasPermission('accounting.close_period');
    }
}
