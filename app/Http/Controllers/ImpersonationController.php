<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

/**
 * Phase 7 — Role Simulation (Impersonation)
 *
 * Allows users with 'admin.impersonate' permission to "Login As" another user.
 * Original user ID stored in session for safe return.
 */
class ImpersonationController extends Controller
{
    /**
     * Start impersonating another user.
     */
    public function start(User $user)
    {
        $impersonator = auth()->user();

        if (!$impersonator->hasPermission('admin.impersonate')) {
            abort(403, __('messages.rbac_no_permission'));
        }

        // Cannot impersonate yourself
        if ($impersonator->id === $user->id) {
            return back()->with('error', __('messages.rbac_cannot_impersonate_self'));
        }

        // Cannot impersonate another admin (safety guard)
        if ($user->isAdmin()) {
            return back()->with('error', __('messages.rbac_cannot_impersonate_admin'));
        }

        // Store the original user ID in session
        session()->put('impersonator_id', $impersonator->id);

        AuditLogService::log(
            'admin',
            'impersonate_start',
            "User #{$impersonator->id} ({$impersonator->email}) started impersonating User #{$user->id} ({$user->email})",
            null,
            ['impersonator_id' => $impersonator->id, 'target_id' => $user->id],
            $user
        );

        auth()->login($user);

        return redirect()->route('dashboard')
            ->with('success', __('messages.rbac_impersonation_started', ['name' => $user->name]));
    }

    /**
     * Stop impersonating and return to original user.
     */
    public function stop()
    {
        $impersonatorId = session()->get('impersonator_id');

        if (!$impersonatorId) {
            return redirect()->route('dashboard');
        }

        $impersonator = User::find($impersonatorId);
        $impersonated = auth()->user();

        if (!$impersonator) {
            session()->forget('impersonator_id');
            return redirect()->route('dashboard')
                ->with('error', __('messages.rbac_impersonator_not_found'));
        }

        session()->forget('impersonator_id');

        AuditLogService::log(
            'admin',
            'impersonate_stop',
            "User #{$impersonator->id} ({$impersonator->email}) stopped impersonating User #{$impersonated->id} ({$impersonated->email})",
            null,
            ['impersonator_id' => $impersonator->id, 'target_id' => $impersonated->id],
            $impersonated
        );

        auth()->login($impersonator);

        return redirect()->route('dashboard')
            ->with('success', __('messages.rbac_impersonation_stopped'));
    }
}
