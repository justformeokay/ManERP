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
            // ─── DASHBOARD ───
            ['heading' => __('messages.main')],
            [
                'label' => __('messages.dashboard'),
                'url' => route('dashboard'),
                'active' => 'dashboard',
                'icon' => $svg('M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1'),
            ],

            // ─── PENJUALAN (SALES) ───
            ['heading' => __('messages.sales_heading'), 'permission' => 'sales'],
            [
                'label' => __('messages.crm_clients'),
                'url' => route('clients.index'),
                'active' => 'clients.*',
                'permission' => 'clients',
                'icon' => $svg('M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'),
            ],
            [
                'label' => __('messages.projects'),
                'url' => route('projects.index'),
                'active' => 'projects.*',
                'permission' => 'projects',
                'icon' => $svg('M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'),
            ],
            [
                'label' => __('messages.sales_orders'),
                'url' => route('sales.index'),
                'active' => 'sales.*',
                'permission' => 'sales',
                'icon' => $svg('M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z'),
            ],
            [
                'label' => __('messages.invoices'),
                'url' => route('finance.invoices.index'),
                'active' => 'finance.invoices.*',
                'permission' => 'finance',
                'icon' => $svg('M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'),
            ],
            [
                'label' => __('messages.credit_notes_title'),
                'url' => route('accounting.credit-notes.index'),
                'active' => 'accounting.credit-notes.*',
                'permission' => 'accounting',
                'icon' => $svg('M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z'),
            ],

            // ─── PEMBELIAN (PURCHASING) ───
            ['heading' => __('messages.purchasing_heading'), 'permission' => 'purchasing'],
            [
                'label' => __('messages.suppliers'),
                'url' => route('suppliers.index'),
                'active' => 'suppliers.*',
                'permission' => 'suppliers',
                'icon' => $svg('M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'),
            ],
            [
                'label' => __('messages.purchase_requests'),
                'url' => route('purchase-requests.index'),
                'active' => 'purchase-requests.*',
                'permission' => 'purchasing',
                'icon' => $svg('M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'),
            ],
            [
                'label' => __('messages.purchase_orders'),
                'url' => route('purchasing.index'),
                'active' => 'purchasing.*',
                'permission' => 'purchasing',
                'icon' => $svg('M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z'),
            ],
            [
                'label' => __('messages.debit_notes_title'),
                'url' => route('accounting.debit-notes.index'),
                'active' => 'accounting.debit-notes.*',
                'permission' => 'accounting',
                'icon' => $svg('M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z'),
            ],

            // ─── INVENTARIS (INVENTORY) ───
            ['heading' => __('messages.inventory'), 'permission' => 'inventory'],
            [
                'label' => __('messages.products'),
                'url' => route('inventory.products.index'),
                'active' => 'inventory.products.*|inventory.categories.*',
                'permission' => 'products',
                'icon' => $svg('M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'),
            ],
            [
                'label' => __('messages.warehouses'),
                'url' => route('warehouses.index'),
                'active' => 'warehouses.*',
                'permission' => 'warehouses',
                'icon' => $svg('M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'),
            ],
            [
                'label' => __('messages.stock_management'),
                'url' => route('inventory.stock.index'),
                'active' => 'inventory.stock.*|inventory.stocks.*|inventory.movements.*|inventory.transfers.*',
                'permission' => 'inventory',
                'icon' => $svg('M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'),
            ],

            // ─── PRODUKSI (MANUFACTURING) ───
            ['heading' => __('messages.manufacturing'), 'permission' => 'manufacturing'],
            [
                'label' => __('messages.bill_of_materials'),
                'url' => route('manufacturing.boms.index'),
                'active' => 'manufacturing.boms.*',
                'permission' => 'manufacturing',
                'icon' => $svg('M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'),
            ],
            [
                'label' => __('messages.work_orders'),
                'url' => route('manufacturing.orders.index'),
                'active' => 'manufacturing.orders.*',
                'permission' => 'manufacturing',
                'icon' => $svg('M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'),
            ],
            [
                'label' => __('messages.costing_hpp'),
                'url' => route('manufacturing.costing.index'),
                'active' => 'manufacturing.costing.*',
                'permission' => 'manufacturing',
                'icon' => $svg('M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'),
            ],
            [
                'label' => __('messages.quality_control'),
                'url' => route('qc.inspections.index'),
                'active' => 'qc.*',
                'permission' => 'manufacturing',
                'icon' => $svg('M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'),
            ],

            // ─── HR & PAYROLL ───
            ['heading' => __('messages.hr_heading'), 'permission' => 'hr'],
            [
                'label' => __('messages.employees'),
                'url' => route('hr.employees.index'),
                'active' => 'hr.employees.*',
                'permission' => 'hr',
                'icon' => $svg('M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'),
            ],
            [
                'label' => __('messages.payroll'),
                'url' => route('hr.payroll.index'),
                'active' => 'hr.payroll.*',
                'permission' => 'hr',
                'icon' => $svg('M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'),
            ],
            [
                'label' => __('messages.payroll_dashboard'),
                'url' => route('hr.payroll.dashboard'),
                'active' => 'hr.payroll.dashboard',
                'permission' => 'hr',
                'icon' => $svg('M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'),
            ],

            // ─── AKUNTANSI (ACCOUNTING) ───
            ['heading' => __('messages.accounting'), 'permission' => 'accounting'],
            [
                'label' => __('messages.chart_of_accounts'),
                'url' => route('accounting.coa.index'),
                'active' => 'accounting.coa.*|accounting.fiscal-periods.*',
                'permission' => 'accounting',
                'icon' => $svg('M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3'),
            ],
            [
                'label' => __('messages.journal_entries'),
                'url' => route('accounting.journals.index'),
                'active' => 'accounting.journals.*',
                'permission' => 'accounting',
                'icon' => $svg('M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'),
            ],
            [
                'label' => __('messages.bank_and_cash'),
                'url' => route('accounting.bank.index'),
                'active' => 'accounting.bank.*',
                'permission' => 'accounting',
                'icon' => $svg('M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'),
            ],
            [
                'label' => __('messages.fixed_assets_title'),
                'url' => route('accounting.assets.index'),
                'active' => 'accounting.assets.*',
                'permission' => 'accounting',
                'icon' => $svg('M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'),
            ],
            [
                'label' => __('messages.budgets_title'),
                'url' => route('accounting.budgets.index'),
                'active' => 'accounting.budgets.*',
                'permission' => 'accounting',
                'icon' => $svg('M9 7h6m0 10v-3m-3 3v-6m-3 6v-1m6-9a2 2 0 002-2V5a2 2 0 00-2-2H9a2 2 0 00-2 2v2a2 2 0 002 2zm-3 4H4a2 2 0 00-2 2v4a2 2 0 002 2h2m14-6h-2a2 2 0 00-2 2v4a2 2 0 002 2h2a2 2 0 002-2v-4a2 2 0 00-2-2z'),
            ],
            [
                'label' => __('messages.financial_reports_title'),
                'url' => route('accounting.reports.index'),
                'active' => 'accounting.reports.*|accounting.ledger|accounting.trial-balance|accounting.balance-sheet|accounting.profit-loss|accounting.cash-flow|accounting.ar-aging|accounting.financial-ratios|accounting.tax.*',
                'permission' => 'accounting',
                'icon' => $svg('M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'),
            ],

            // ─── ADMINISTRASI ───
            ['heading' => __('messages.administration'), 'permission' => 'admin'],
            [
                'label' => __('messages.settings'),
                'url' => route('settings.index'),
                'active' => 'settings.index',
                'icon' => $svg('M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4'),
                'permission' => 'admin',
                'special_permission' => 'admin.manage_settings',
            ],
            [
                'label' => __('messages.users'),
                'url' => route('settings.users.index'),
                'active' => 'settings.users.*',
                'icon' => $svg('M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'),
                'permission' => 'admin',
                'special_permission' => 'admin.manage_users',
            ],
            [
                'label' => __('messages.reports'),
                'url' => route('reports.index'),
                'active' => 'reports.*',
                'permission' => 'reports',
                'icon' => $svg('M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'),
            ],
            [
                'label' => __('messages.audit_logs'),
                'url' => route('audit-logs.index'),
                'active' => 'audit-logs.*',
                'icon' => $svg('M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z'),
                'permission' => 'admin',
                'special_permission' => 'admin.view_audit_logs',
            ],
            [
                'label' => __('maintenance.system_maintenance'),
                'url' => route('maintenance.index'),
                'active' => 'maintenance.*',
                'icon' => $svg('M11.42 15.17l-5.385-5.385a1.061 1.061 0 010-1.5l.707-.707a1.06 1.06 0 011.5 0l3.97 3.97 8.97-8.97a1.06 1.06 0 011.5 0l.707.707a1.06 1.06 0 010 1.5L12.92 15.17a1.06 1.06 0 01-1.5 0zM3.75 21h16.5M3.75 4.5v11.25a2.25 2.25 0 002.25 2.25h12a2.25 2.25 0 002.25-2.25V4.5m-16.5 0h16.5'),
                'permission' => 'admin',
                'special_permission' => 'admin.maintenance',
            ],
            [
                'label' => __('messages.license_management'),
                'url' => route('license.index'),
                'active' => 'license.*',
                'icon' => $svg('M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z'),
                'permission' => 'admin',
                'special_permission' => 'admin.manage_license',
            ],

            // ─── BANTUAN (HELP) ───
            ['heading' => __('messages.help')],
            [
                'label' => __('messages.notifications'),
                'url' => route('notifications.index'),
                'active' => 'notifications.*',
                'icon' => $svg('M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0'),
            ],
            [
                'label' => __('messages.support_tickets'),
                'url' => route('support.index'),
                'active' => 'support.*',
                'icon' => $svg('M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 010 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 010-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375z'),
            ],
            [
                'label' => __('messages.user_guide'),
                'url' => route('user-guide.index'),
                'active' => 'user-guide.*',
                'icon' => $svg('M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25'),
            ],
            [
                'label' => __('messages.about_application'),
                'url' => route('about'),
                'active' => 'about',
                'icon' => $svg('M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z'),
            ],
        ];
    }
}
