<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorChallengeController extends Controller
{
    public function create(): View
    {
        return view('auth.two-factor-challenge');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'code'          => ['nullable', 'digits:6'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        $user = Auth::user();

        if (! $user || ! $user->two_factor_confirmed_at) {
            return redirect()->route('login');
        }

        // Try TOTP code first
        if ($request->filled('code')) {
            $google2fa = new Google2FA();
            $secret = decrypt($user->two_factor_secret);

            if ($google2fa->verifyKey($secret, $request->code)) {
                $request->session()->put('2fa_verified', true);
                return redirect()->intended(route('dashboard'));
            }

            AuditLogService::log('auth', 'mfa_failed', "Invalid MFA code for {$user->email}", null, null, $user);
            return back()->withErrors(['code' => 'The provided two-factor code was invalid.']);
        }

        // Try recovery code
        if ($request->filled('recovery_code')) {
            $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);
            $code = $request->recovery_code;

            if (in_array($code, $recoveryCodes, true)) {
                // Remove used recovery code
                $remaining = array_values(array_filter($recoveryCodes, fn($c) => $c !== $code));
                $user->forceFill([
                    'two_factor_recovery_codes' => encrypt(json_encode($remaining)),
                ])->save();

                $request->session()->put('2fa_verified', true);

                AuditLogService::log('auth', 'mfa_recovery', "Recovery code used by {$user->email} (" . count($remaining) . " remaining)", null, null, $user);
                return redirect()->intended(route('dashboard'));
            }

            return back()->withErrors(['recovery_code' => 'The provided recovery code was invalid.']);
        }

        return back()->withErrors(['code' => 'Please provide a two-factor code or recovery code.']);
    }
}
