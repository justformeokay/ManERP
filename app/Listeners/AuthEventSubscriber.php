<?php

namespace App\Listeners;

use App\Services\AuditLogService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;

class AuthEventSubscriber
{
    public function __construct(
        private Request $request,
    ) {}

    public function handleLogin(Login $event): void
    {
        AuditLogService::log(
            'auth',
            'login',
            "User {$event->user->email} logged in",
            null,
            ['user_id' => $event->user->id, 'email' => $event->user->email],
            $event->user
        );
    }

    public function handleLogout(Logout $event): void
    {
        if ($event->user) {
            AuditLogService::log(
                'auth',
                'logout',
                "User {$event->user->email} logged out",
                null,
                ['user_id' => $event->user->id, 'email' => $event->user->email],
                $event->user
            );
        }
    }

    public function handleFailed(Failed $event): void
    {
        AuditLogService::log(
            'auth',
            'failed_login',
            "Failed login attempt for {$event->credentials['email']}",
            null,
            [
                'email' => $event->credentials['email'],
                'ip'    => $this->request->ip(),
                'user_agent' => $this->request->userAgent(),
            ]
        );
    }

    public function handleLockout(Lockout $event): void
    {
        $email = $event->request->input('email', 'unknown');

        AuditLogService::log(
            'auth',
            'lockout',
            "Account locked out for {$email} after too many failed attempts",
            null,
            [
                'email' => $email,
                'ip'    => $this->request->ip(),
            ]
        );
    }

    public function handlePasswordReset(PasswordReset $event): void
    {
        AuditLogService::log(
            'auth',
            'password_reset',
            "Password reset for {$event->user->email}",
            null,
            ['user_id' => $event->user->id, 'email' => $event->user->email],
            $event->user
        );
    }

    public function subscribe($events): array
    {
        return [
            Login::class         => 'handleLogin',
            Logout::class        => 'handleLogout',
            Failed::class        => 'handleFailed',
            Lockout::class       => 'handleLockout',
            PasswordReset::class => 'handlePasswordReset',
        ];
    }
}
