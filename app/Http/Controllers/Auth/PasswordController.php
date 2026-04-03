<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
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
            "User {$user->email} changed their password",
            null,
            ['user_id' => $user->id],
            $user
        );

        return back()->with('status', 'password-updated');
    }
}
