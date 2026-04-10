<?php

namespace App\Providers;

use App\Listeners\AuthEventSubscriber;
use App\Models\Invoice;
use App\Models\User;
use App\Observers\InvoiceObserver;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
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

        // ── Invoice Observer (auto-convert prospect → customer on payment) ──
        Invoice::observe(InvoiceObserver::class);

        // ── Permission Gates (Phase 7: Industrial RBAC) ──
        // Register all standard and special permissions as Gates so @can() works in Blade
        foreach (User::allPermissions() as $permission) {
            Gate::define($permission, fn(User $user) => $user->hasPermission($permission));
        }

        // Super Admin implicit grant — before callback gives admin all abilities
        Gate::before(fn(User $user) => $user->isAdmin() ? true : null);
    }
}
