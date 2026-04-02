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
            ['heading' => __('messages.main')],
            [
                'label' => __('messages.dashboard'),
                'url' => route('dashboard'),
                'active' => 'dashboard',
                'icon' => $svg('M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1'),
            ],
            ['heading' => __('messages.operations')],
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
            ['heading' => __('messages.inventory'), 'permission' => 'inventory'],
            [
                'label' => __('messages.categories'),
                'url' => route('inventory.categories.index'),
                'active' => 'inventory.categories.*',
                'permission' => 'products',
                'icon' => $svg('M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z'),
            ],
            [
                'label' => __('messages.products'),
                'url' => route('inventory.products.index'),
                'active' => 'inventory.products.*',
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
                'label' => __('messages.stock_levels'),
                'url' => route('inventory.stocks.index'),
                'active' => 'inventory.stocks.*',
                'permission' => 'inventory',
                'icon' => $svg('M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'),
            ],
            [
                'label' => __('messages.stock_movements'),
                'url' => route('inventory.movements.index'),
                'active' => 'inventory.movements.*',
                'permission' => 'inventory',
                'icon' => $svg('M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4'),
            ],
            [
                'label' => __('messages.stock_transfers'),
                'url' => route('inventory.transfers.index'),
                'active' => 'inventory.transfers.*',
                'permission' => 'inventory',
                'icon' => $svg('M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4'),
            ],
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
            ['heading' => __('messages.quality_control'), 'permission' => 'manufacturing'],
            [
                'label' => __('messages.qc_parameters'),
                'url' => route('qc.parameters.index'),
                'active' => 'qc.parameters.*',
                'permission' => 'manufacturing',
                'icon' => $svg('M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'),
            ],
            [
                'label' => __('messages.qc_inspections'),
                'url' => route('qc.inspections.index'),
                'active' => 'qc.inspections.*',
                'permission' => 'manufacturing',
                'icon' => $svg('M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'),
            ],
            ['heading' => __('messages.supply_chain'), 'permission' => 'suppliers'],
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
            ['heading' => __('messages.commerce')],
            [
                'label' => __('messages.sales_orders'),
                'url' => route('sales.index'),
                'active' => 'sales.*',
                'permission' => 'sales',
                'icon' => $svg('M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z'),
            ],
            [
                'label' => __('messages.purchase_orders'),
                'url' => route('purchasing.index'),
                'active' => 'purchasing.*',
                'permission' => 'purchasing',
                'icon' => $svg('M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z'),
            ],
            ['heading' => __('messages.finance'), 'permission' => 'finance'],
            [
                'label' => __('messages.invoices'),
                'url' => route('finance.invoices.index'),
                'active' => 'finance.invoices.*',
                'permission' => 'finance',
                'icon' => $svg('M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'),
            ],
            ['heading' => __('messages.accounting'), 'permission' => 'accounting'],
            [
                'label' => __('messages.chart_of_accounts'),
                'url' => route('accounting.coa.index'),
                'active' => 'accounting.coa.*',
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
                'label' => __('messages.general_ledger'),
                'url' => route('accounting.ledger'),
                'active' => 'accounting.ledger',
                'permission' => 'accounting',
                'icon' => $svg('M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'),
            ],
            [
                'label' => __('messages.trial_balance'),
                'url' => route('accounting.trial-balance'),
                'active' => 'accounting.trial-balance',
                'permission' => 'accounting',
                'icon' => $svg('M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'),
            ],
            [
                'label' => __('messages.balance_sheet'),
                'url' => route('accounting.balance-sheet'),
                'active' => 'accounting.balance-sheet',
                'permission' => 'accounting',
                'icon' => $svg('M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3'),
            ],
            [
                'label' => __('messages.profit_loss'),
                'url' => route('accounting.profit-loss'),
                'active' => 'accounting.profit-loss',
                'permission' => 'accounting',
                'icon' => $svg('M13 7h8m0 0v8m0-8l-8 8-4-4-6 6'),
            ],
            [
                'label' => __('messages.cash_flow_title'),
                'url' => route('accounting.cash-flow'),
                'active' => 'accounting.cash-flow',
                'permission' => 'accounting',
                'icon' => $svg('M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'),
            ],
            [
                'label' => __('messages.ar_aging_title'),
                'url' => route('accounting.ar-aging'),
                'active' => 'accounting.ar-aging',
                'permission' => 'accounting',
                'icon' => $svg('M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'),
            ],
            [
                'label' => __('messages.ppn_tax'),
                'url' => route('accounting.tax.spt-masa-ppn'),
                'active' => 'accounting.tax.*',
                'permission' => 'accounting',
                'icon' => $svg('M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z'),
            ],
            [
                'label' => __('messages.fiscal_periods_title'),
                'url' => route('accounting.fiscal-periods.index'),
                'active' => 'accounting.fiscal-periods.*',
                'permission' => 'accounting',
                'icon' => $svg('M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'),
            ],
            [
                'label' => __('messages.journal_templates_title'),
                'url' => route('accounting.journals.templates'),
                'active' => 'accounting.journals.templates.*',
                'permission' => 'accounting',
                'icon' => $svg('M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2'),
            ],
            ['heading' => __('messages.analytics'), 'permission' => 'reports'],
            [
                'label' => __('messages.reports'),
                'url' => route('reports.index'),
                'active' => 'reports.*',
                'permission' => 'reports',
                'icon' => $svg('M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'),
            ],
            ['heading' => __('messages.administration'), 'admin_only' => true],
            [
                'label' => __('messages.settings'),
                'url' => route('settings.index'),
                'active' => 'settings.index',
                'icon' => $svg('M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4'),
                'admin_only' => true,
            ],
            [
                'label' => __('messages.users'),
                'url' => route('settings.users.index'),
                'active' => 'settings.users.*',
                'icon' => $svg('M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'),
                'admin_only' => true,
            ],
            [
                'label' => __('messages.audit_logs'),
                'url' => route('audit-logs.index'),
                'active' => 'audit-logs.*',
                'icon' => $svg('M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z'),
                'admin_only' => true,
            ],
        ];
    }
}
