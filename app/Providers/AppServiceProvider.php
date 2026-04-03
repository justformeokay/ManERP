<?php

namespace App\Providers;

use App\Listeners\AuthEventSubscriber;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ── Password Complexity Policy (ITGC compliance) ──
        Password::defaults(function () {
            return Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised();
        });

        // ── Auth Event Subscriber (login/logout/failed login audit) ──
        Event::subscribe(AuthEventSubscriber::class);
    }
}
