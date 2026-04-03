<?php

namespace App\Http\Controllers;

use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Show the MFA setup page with QR code.
     */
    public function setup(Request $request): View
    {
        $user = $request->user();

        // Generate a new secret (don't persist yet — only after confirmation)
        $secret = $this->google2fa->generateSecretKey();
        $request->session()->put('2fa_setup_secret', $secret);

        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name', 'ManERP'),
            $user->email,
            $secret
        );

        // Generate inline SVG QR code via BaconQrCode
        $renderer = new \BaconQrCode\Renderer\Image\SvgImageBackEnd();
        $imageRenderer = new \BaconQrCode\Renderer\ImageRenderer(
            new \BaconQrCode\Renderer\RendererStyle\RendererStyle(200),
            $renderer
        );
        $writer = new \BaconQrCode\Writer($imageRenderer);
        $qrSvg = $writer->writeString($qrCodeUrl);

        return view('profile.two-factor-setup', compact('secret', 'qrSvg', 'user'));
    }

    /**
     * Confirm and enable MFA after user verifies with a valid TOTP code.
     */
    public function enable(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $secret = $request->session()->pull('2fa_setup_secret');

        if (! $secret) {
            return back()->with('error', 'Setup session expired. Please try again.');
        }

        $valid = $this->google2fa->verifyKey($secret, $request->code);

        if (! $valid) {
            $request->session()->put('2fa_setup_secret', $secret);
            return back()->withErrors(['code' => 'Invalid verification code. Please try again.']);
        }

        // Generate recovery codes
        $recoveryCodes = collect(range(1, 8))
            ->map(fn() => strtoupper(substr(bin2hex(random_bytes(5)), 0, 10)))
            ->map(fn($c) => substr($c, 0, 5) . '-' . substr($c, 5))
            ->toArray();

        $user = $request->user();
        $user->forceFill([
            'two_factor_secret'       => encrypt($secret),
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
            'two_factor_confirmed_at' => now(),
        ])->save();

        AuditLogService::log('auth', 'mfa_enabled', "MFA enabled for {$user->email}", null, null, $user);

        return redirect()->route('profile.edit')
            ->with('status', 'two-factor-enabled')
            ->with('recovery_codes', $recoveryCodes);
    }

    /**
     * Disable MFA after password confirmation.
     */
    public function disable(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        $user->forceFill([
            'two_factor_secret'       => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        AuditLogService::log('auth', 'mfa_disabled', "MFA disabled for {$user->email}", null, null, $user);

        return redirect()->route('profile.edit')->with('status', 'two-factor-disabled');
    }
}
