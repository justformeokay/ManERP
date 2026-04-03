<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ForcePasswordChangeController extends Controller
{
    public function show(): View
    {
        return view('auth.force-password-change');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Password::defaults(), 'different:current_password'],
        ]);

        $user = $request->user();

        $user->update([
            'password'            => Hash::make($validated['password']),
            'password_changed_at' => now(),
        ]);

        // Session hardening: invalidate all other sessions
        Auth::logoutOtherDevices($validated['password']);

        AuditLogService::log(
            'auth',
            'password_change',
            "User {$user->email} changed their password (forced expiry)",
            null,
            ['user_id' => $user->id],
            $user
        );

        return redirect()->route('dashboard')
            ->with('success', 'Password updated successfully.');
    }
}
