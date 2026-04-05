<?php

namespace App\Policies;

use App\Models\PayrollPeriod;
use App\Models\User;

/**
 * Phase 7 — Payroll Period Policy (Segregation of Duties)
 *
 * Approve: requires 'hr.approve_payroll'
 * Post to Accounting: requires 'hr.post_payroll'
 * Edit: blocked when status is 'approved' or 'posted' (state-based lock)
 */
class PayrollPeriodPolicy
{
    public function approve(User $user, PayrollPeriod $period): bool
    {
        return $user->hasPermission('hr.approve_payroll');
    }

    public function post(User $user, PayrollPeriod $period): bool
    {
        return $user->hasPermission('hr.post_payroll');
    }

    public function update(User $user, PayrollPeriod $period): bool
    {
        // Posted and approved periods are locked from edits
        if (in_array($period->status, ['approved', 'posted'], true)) {
            return false;
        }

        return $user->hasPermission('hr.edit');
    }
}
