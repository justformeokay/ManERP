<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::composer('partials.sidebar', function ($view) {
            $view->with('sidebarMenu', $this->sidebarMenu());
        });
    }

    private function sidebarMenu(): array
    {
        $svg = fn(string $d) => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="' . $d . '" /></svg>';

        return [
            ['heading' => 'Main'],
            [
                'label' => 'Dashboard',
                'url' => route('dashboard'),
                'active' => 'dashboard',
                'icon' => $svg('M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1'),
            ],
            ['heading' => 'Operations'],
            [
                'label' => 'CRM / Clients',
                'url' => route('clients.index'),
                'active' => 'clients.*',
                'icon' => $svg('M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'),
            ],
            [
                'label' => 'Projects',
                'url' => route('projects.index'),
                'active' => 'projects.*',
                'icon' => $svg('M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'),
            ],
            ['heading' => 'Inventory'],
            [
                'label' => 'Categories',
                'url' => route('inventory.categories.index'),
                'active' => 'inventory.categories.*',
                'icon' => $svg('M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z'),
            ],
            [
                'label' => 'Products',
                'url' => route('inventory.products.index'),
                'active' => 'inventory.products.*',
                'icon' => $svg('M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'),
            ],
            [
                'label' => 'Warehouses',
                'url' => route('warehouses.index'),
                'active' => 'warehouses.*',
                'icon' => $svg('M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'),
            ],
            [
                'label' => 'Stock Levels',
                'url' => route('inventory.stocks.index'),
                'active' => 'inventory.stocks.*',
                'icon' => $svg('M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'),
            ],
            [
                'label' => 'Stock Movements',
                'url' => route('inventory.movements.index'),
                'active' => 'inventory.movements.*',
                'icon' => $svg('M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4'),
            ],
            ['heading' => 'Manufacturing'],
            [
                'label' => 'Bill of Materials',
                'url' => route('manufacturing.boms.index'),
                'active' => 'manufacturing.boms.*',
                'icon' => $svg('M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'),
            ],
            [
                'label' => 'Work Orders',
                'url' => route('manufacturing.orders.index'),
                'active' => 'manufacturing.orders.*',
                'icon' => $svg('M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'),
            ],
            ['heading' => 'Supply Chain'],
            [
                'label' => 'Suppliers',
                'url' => route('suppliers.index'),
                'active' => 'suppliers.*',
                'icon' => $svg('M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'),
            ],
            ['heading' => 'Commerce'],
            [
                'label' => 'Sales Orders',
                'url' => route('sales.index'),
                'active' => 'sales.*',
                'icon' => $svg('M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z'),
            ],
            [
                'label' => 'Purchase Orders',
                'url' => route('purchasing.index'),
                'active' => 'purchasing.*',
                'icon' => $svg('M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z'),
            ],
            ['heading' => 'Analytics'],
            [
                'label' => 'Reports',
                'url' => route('reports.index'),
                'active' => 'reports.*',
                'icon' => $svg('M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'),
            ],
            ['heading' => 'Administration', 'admin_only' => true],
            [
                'label' => 'Settings',
                'url' => route('settings.index'),
                'active' => 'settings.index',
                'icon' => $svg('M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4'),
                'admin_only' => true,
            ],
            [
                'label' => 'Users',
                'url' => route('settings.users.index'),
                'active' => 'settings.users.*',
                'icon' => $svg('M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'),
                'admin_only' => true,
            ],
        ];
    }
}
