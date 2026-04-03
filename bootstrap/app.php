<?php

use App\Http\Middleware\ApplyTimezone;
use App\Http\Middleware\EnsureOpenFiscalPeriod;
use App\Http\Middleware\EnsureTwoFactorVerified;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\Middleware\AuthenticateSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Append to web middleware group
        $middleware->web(append: [
            SecurityHeaders::class,
            EnsureUserIsActive::class,
            SetLocale::class,
            ApplyTimezone::class,
            AuthenticateSession::class,
            ForcePasswordChange::class,
            EnsureTwoFactorVerified::class,
        ]);
        
        // Register middleware aliases
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'active' => EnsureUserIsActive::class,
            'permission' => CheckPermission::class,
            'locale' => SetLocale::class,
            'fiscal-lock' => EnsureOpenFiscalPeriod::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
