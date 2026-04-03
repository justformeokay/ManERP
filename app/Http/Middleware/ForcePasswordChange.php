<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    /**
     * Routes that are exempt from the password change requirement.
     */
    private const EXEMPT_ROUTES = [
        'password.force-change',
        'password.force-change.update',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Skip exempt routes
        if (in_array($request->route()?->getName(), self::EXEMPT_ROUTES, true)) {
            return $next($request);
        }

        // Check if password has expired (90-day policy)
        $passwordChangedAt = $user->password_changed_at;

        if (! $passwordChangedAt || $passwordChangedAt->diffInDays(now()) >= 90) {
            return redirect()->route('password.force-change')
                ->with('warning', 'Your password has expired. Please set a new password to continue.');
        }

        return $next($request);
    }
}
