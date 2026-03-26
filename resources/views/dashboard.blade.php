@extends('layouts.app')

@section('title', 'Dashboard')

@section('breadcrumbs')
    <span class="text-gray-900 font-medium">Dashboard</span>
@endsection

@section('page-header')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
            <p class="mt-1 text-sm text-gray-500">Welcome back. Here's an overview of your operations.</p>
        </div>
        <div class="flex gap-2">
            @include('components.button', [
                'label' => 'Export',
                'type' => 'secondary',
                'icon' => '<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>',
            ])
            @include('components.button', [
                'label' => 'New Order',
                'type' => 'primary',
                'href' => '#',
                'icon' => '<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>',
            ])
        </div>
    </div>
@endsection

@section('content')
    {{-- KPI Stats --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4 mb-8">
        @include('components.stat-card', [
            'title' => 'Total Revenue',
            'value' => '$124,500',
            'trend' => '+14.2%',
            'trendUp' => true,
            'icon' => '<svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
            'iconBg' => 'bg-green-50 text-green-600',
        ])

        @include('components.stat-card', [
            'title' => 'Active Orders',
            'value' => '38',
            'trend' => '+5',
            'trendUp' => true,
            'icon' => '<svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>',
            'iconBg' => 'bg-primary-50 text-primary-600',
        ])

        @include('components.stat-card', [
            'title' => 'Inventory Items',
            'value' => '1,247',
            'trend' => '-3.1%',
            'trendUp' => false,
            'icon' => '<svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>',
            'iconBg' => 'bg-amber-50 text-amber-600',
        ])

        @include('components.stat-card', [
            'title' => 'Active Clients',
            'value' => '156',
            'trend' => '+8',
            'trendUp' => true,
            'icon' => '<svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>',
            'iconBg' => 'bg-purple-50 text-purple-600',
        ])
    </div>

    {{-- Two-column layout --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8">
        {{-- Recent Sales Orders --}}
        <div class="xl:col-span-2">
            @include('components.data-table', [
                'tableTitle' => 'Recent Sales Orders',
                'tableAction' => '<a href="#" class="text-sm font-medium text-primary-600 hover:text-primary-700">View all &rarr;</a>',
                'headers' => ['Order #', 'Client', 'Amount', 'Status', 'Date'],
                'actions' => false,
                'rows' => [
                    ['cells' => ['<span class="font-medium text-gray-900">SO-2026-001</span>', 'Acme Corp', '$12,400', '<span class="inline-flex rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700 ring-1 ring-green-600/20">Completed</span>', 'Mar 25, 2026']],
                    ['cells' => ['<span class="font-medium text-gray-900">SO-2026-002</span>', 'TechVault Inc', '$8,750', '<span class="inline-flex rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-amber-600/20">Processing</span>', 'Mar 24, 2026']],
                    ['cells' => ['<span class="font-medium text-gray-900">SO-2026-003</span>', 'BuildRight LLC', '$23,100', '<span class="inline-flex rounded-full bg-primary-50 px-2.5 py-0.5 text-xs font-medium text-primary-700 ring-1 ring-primary-600/20">Pending</span>', 'Mar 24, 2026']],
                    ['cells' => ['<span class="font-medium text-gray-900">SO-2026-004</span>', 'Nova Systems', '$5,200', '<span class="inline-flex rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700 ring-1 ring-green-600/20">Completed</span>', 'Mar 23, 2026']],
                    ['cells' => ['<span class="font-medium text-gray-900">SO-2026-005</span>', 'Peak Industries', '$17,830', '<span class="inline-flex rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-700 ring-1 ring-red-600/20">Cancelled</span>', 'Mar 22, 2026']],
                ],
            ])
        </div>

        {{-- Quick Actions & Low Stock Alerts --}}
        <div class="space-y-6">
            {{-- Quick Actions --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-base font-semibold text-gray-900 mb-4">Quick Actions</h3>
                <div class="grid grid-cols-2 gap-3">
                    <a href="#" class="flex flex-col items-center gap-2 rounded-xl bg-gray-50 p-4 text-center hover:bg-primary-50 hover:text-primary-700 transition group">
                        <svg class="h-6 w-6 text-gray-400 group-hover:text-primary-600 transition" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                        <span class="text-xs font-medium text-gray-700 group-hover:text-primary-700">New Sale</span>
                    </a>
                    <a href="#" class="flex flex-col items-center gap-2 rounded-xl bg-gray-50 p-4 text-center hover:bg-primary-50 hover:text-primary-700 transition group">
                        <svg class="h-6 w-6 text-gray-400 group-hover:text-primary-600 transition" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" /></svg>
                        <span class="text-xs font-medium text-gray-700 group-hover:text-primary-700">Purchase</span>
                    </a>
                    <a href="#" class="flex flex-col items-center gap-2 rounded-xl bg-gray-50 p-4 text-center hover:bg-primary-50 hover:text-primary-700 transition group">
                        <svg class="h-6 w-6 text-gray-400 group-hover:text-primary-600 transition" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                        <span class="text-xs font-medium text-gray-700 group-hover:text-primary-700">Add Client</span>
                    </a>
                    <a href="#" class="flex flex-col items-center gap-2 rounded-xl bg-gray-50 p-4 text-center hover:bg-primary-50 hover:text-primary-700 transition group">
                        <svg class="h-6 w-6 text-gray-400 group-hover:text-primary-600 transition" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        <span class="text-xs font-medium text-gray-700 group-hover:text-primary-700">Production</span>
                    </a>
                </div>
            </div>

            {{-- Low Stock Alerts --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-semibold text-gray-900">Low Stock Alerts</h3>
                    <span class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-700 ring-1 ring-red-600/20">3 items</span>
                </div>
                <div class="space-y-3">
                    @foreach([
                        ['name' => 'Steel Rod 12mm', 'stock' => 15, 'min' => 50],
                        ['name' => 'Copper Wire 2.5mm', 'stock' => 8, 'min' => 30],
                        ['name' => 'Bearing SKF 6205', 'stock' => 3, 'min' => 20],
                    ] as $item)
                        <div class="flex items-center justify-between rounded-xl bg-red-50/50 p-3">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $item['name'] }}</p>
                                <p class="text-xs text-gray-500">Min: {{ $item['min'] }} units</p>
                            </div>
                            <span class="text-sm font-bold text-red-600">{{ $item['stock'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Manufacturing Orders --}}
    @include('components.data-table', [
        'tableTitle' => 'Active Manufacturing Orders',
        'tableAction' => '<a href="#" class="text-sm font-medium text-primary-600 hover:text-primary-700">View all &rarr;</a>',
        'headers' => ['MO #', 'Product', 'Quantity', 'Progress', 'Status', 'Due Date'],
        'actions' => false,
        'rows' => [
            ['cells' => [
                '<span class="font-medium text-gray-900">MO-2026-012</span>',
                'Hydraulic Pump Assembly',
                '50 units',
                '<div class="w-24"><div class="h-2 rounded-full bg-gray-100"><div class="h-2 rounded-full bg-primary-500" style="width: 75%"></div></div></div>',
                '<span class="inline-flex rounded-full bg-primary-50 px-2.5 py-0.5 text-xs font-medium text-primary-700 ring-1 ring-primary-600/20">In Progress</span>',
                'Mar 30, 2026',
            ]],
            ['cells' => [
                '<span class="font-medium text-gray-900">MO-2026-013</span>',
                'Motor Shaft XL',
                '200 units',
                '<div class="w-24"><div class="h-2 rounded-full bg-gray-100"><div class="h-2 rounded-full bg-amber-500" style="width: 30%"></div></div></div>',
                '<span class="inline-flex rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-amber-600/20">Waiting Materials</span>',
                'Apr 5, 2026',
            ]],
            ['cells' => [
                '<span class="font-medium text-gray-900">MO-2026-014</span>',
                'Control Panel Type B',
                '25 units',
                '<div class="w-24"><div class="h-2 rounded-full bg-gray-100"><div class="h-2 rounded-full bg-green-500" style="width: 100%"></div></div></div>',
                '<span class="inline-flex rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700 ring-1 ring-green-600/20">Completed</span>',
                'Mar 25, 2026',
            ]],
        ],
    ])
@endsection
