<?php

namespace App\Http\Middleware;

use App\Services\LicenseService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLicenseValid
{
    /**
     * Routes that should bypass license checks (license management, auth, etc.)
     */
    private const BYPASS_ROUTES = [
        'license.*',
        'login',
        'logout',
        'register',
        'password.*',
        'profile.*',
        'language.switch',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // Skip for non-authenticated users
        if (!$request->user()) {
            return $next($request);
        }

        // Skip for license management routes
        foreach (self::BYPASS_ROUTES as $pattern) {
            if ($request->routeIs($pattern)) {
                return $next($request);
            }
        }

        $license = LicenseService::current();

        // No license found — allow access (fresh install / development)
        if (!$license) {
            return $next($request);
        }

        // Check if license is completely invalid (past grace period)
        if (!$license->isValid()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => __('messages.license_expired')], 403);
            }
            return redirect()->route('license.expired');
        }

        // Check user limit on user creation routes
        if ($request->routeIs('settings.users.store') && $license->userLimitReached()) {
            return back()->with('error', __('messages.license_user_limit_reached'));
        }

        // Inject grace period warning into session
        if ($license->isInGracePeriod()) {
            session()->flash('license_warning', __('messages.license_grace_period_warning', [
                'days' => $license->daysUntilExpiry() + \App\Models\SystemLicense::GRACE_PERIOD_DAYS,
            ]));
        }

        return $next($request);
    }
}
