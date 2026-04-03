<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorVerified
{
    /**
     * Routes exempt from 2FA check (the challenge page itself + logout).
     */
    private const EXEMPT_ROUTES = [
        'two-factor.challenge',
        'two-factor.challenge.verify',
        'two-factor.setup',
        'two-factor.enable',
        'two-factor.disable',
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

        // Skip if user has NOT enabled MFA — don't block non-MFA users
        if (! $user->two_factor_confirmed_at) {
            return $next($request);
        }

        // Skip exempt routes
        if (in_array($request->route()?->getName(), self::EXEMPT_ROUTES, true)) {
            return $next($request);
        }

        // If MFA is active but not yet verified this session, redirect to challenge
        if (! $request->session()->get('2fa_verified')) {
            return redirect()->route('two-factor.challenge');
        }

        return $next($request);
    }
}
