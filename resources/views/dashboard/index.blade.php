@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">
    {{-- Page Header --}}
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
        <p class="mt-1 text-sm text-gray-500">Selamat datang kembali, {{ auth()->user()->name }}! Berikut ringkasan bisnis Anda.</p>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
        {{-- Total Clients --}}
        <div class="bg-white rounded-2xl p-5 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100">
                    <svg class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_clients']) }}</p>
                    <p class="text-xs text-gray-500">Total Klien</p>
                </div>
            </div>
        </div>

        {{-- Total Products --}}
        <div class="bg-white rounded-2xl p-5 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-green-100">
                    <svg class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_products']) }}</p>
                    <p class="text-xs text-gray-500">Total Produk</p>
                </div>
            </div>
        </div>

        {{-- Sales Orders --}}
        <div class="bg-white rounded-2xl p-5 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-100">
                    <svg class="h-5 w-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['sales_orders']) }}</p>
                    <p class="text-xs text-gray-500">Order Penjualan</p>
                </div>
            </div>
        </div>

        {{-- Purchase Orders --}}
        <div class="bg-white rounded-2xl p-5 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-100">
                    <svg class="h-5 w-5 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['purchase_orders']) }}</p>
                    <p class="text-xs text-gray-500">Order Pembelian</p>
                </div>
            </div>
        </div>

        {{-- Pending Manufacturing --}}
        <div class="bg-white rounded-2xl p-5 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-yellow-100">
                    <svg class="h-5 w-5 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['pending_manufacturing']) }}</p>
                    <p class="text-xs text-gray-500">Produksi Pending</p>
                </div>
            </div>
        </div>

        {{-- Low Stock --}}
        <div class="bg-white rounded-2xl p-5 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-100">
                    <svg class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['low_stock_items']) }}</p>
                    <p class="text-xs text-gray-500">Stok Menipis</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Revenue Card --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-2xl p-6 text-white shadow-lg">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <p class="text-blue-100 text-sm">Total Pendapatan</p>
                    <p class="text-3xl font-bold mt-1">Rp {{ number_format($salesStats['total_revenue'], 0, ',', '.') }}</p>
                </div>
                <div class="bg-white/20 rounded-xl p-2">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="flex gap-6 text-sm">
                <div>
                    <p class="text-blue-200">Bulan Ini</p>
                    <p class="font-semibold">Rp {{ number_format($salesStats['this_month'], 0, ',', '.') }}</p>
                </div>
                <div>
                    <p class="text-blue-200">Order Pending</p>
                    <p class="font-semibold">{{ $salesStats['pending_orders'] }} orders</p>
                </div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="lg:col-span-2 bg-white rounded-2xl p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="font-semibold text-gray-900 mb-4">Aksi Cepat</h3>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <a href="{{ route('sales.create') }}" class="flex flex-col items-center gap-2 p-4 rounded-xl bg-gray-50 hover:bg-blue-50 hover:text-blue-600 transition group">
                    <svg class="h-6 w-6 text-gray-400 group-hover:text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span class="text-sm font-medium text-gray-700 group-hover:text-blue-600">Order Baru</span>
                </a>
                <a href="{{ route('inventory.products.create') }}" class="flex flex-col items-center gap-2 p-4 rounded-xl bg-gray-50 hover:bg-green-50 hover:text-green-600 transition group">
                    <svg class="h-6 w-6 text-gray-400 group-hover:text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    <span class="text-sm font-medium text-gray-700 group-hover:text-green-600">Produk Baru</span>
                </a>
                <a href="{{ route('clients.create') }}" class="flex flex-col items-center gap-2 p-4 rounded-xl bg-gray-50 hover:bg-purple-50 hover:text-purple-600 transition group">
                    <svg class="h-6 w-6 text-gray-400 group-hover:text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                    <span class="text-sm font-medium text-gray-700 group-hover:text-purple-600">Klien Baru</span>
                </a>
                <a href="{{ route('reports.index') }}" class="flex flex-col items-center gap-2 p-4 rounded-xl bg-gray-50 hover:bg-orange-50 hover:text-orange-600 transition group">
                    <svg class="h-6 w-6 text-gray-400 group-hover:text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <span class="text-sm font-medium text-gray-700 group-hover:text-orange-600">Lihat Laporan</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Recent Activity Section --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Recent Sales Orders --}}
        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <h3 class="font-semibold text-gray-900">Order Penjualan Terbaru</h3>
                <a href="{{ route('sales.index') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">Lihat Semua</a>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($recentSales as $order)
                    <div class="px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition">
                        <div class="flex items-center gap-3">
                            <div class="h-9 w-9 rounded-full bg-blue-100 flex items-center justify-center text-sm font-bold text-blue-700">
                                {{ strtoupper(substr($order->client->name ?? 'N', 0, 1)) }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $order->number }}</p>
                                <p class="text-xs text-gray-500">{{ $order->client->name ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-gray-900">Rp {{ number_format($order->total ?? 0, 0, ',', '.') }}</p>
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium
                                @if($order->status === 'completed') bg-green-100 text-green-700
                                @elseif($order->status === 'draft') bg-gray-100 text-gray-600
                                @elseif($order->status === 'confirmed') bg-blue-100 text-blue-700
                                @elseif($order->status === 'cancelled') bg-red-100 text-red-700
                                @else bg-yellow-100 text-yellow-700
                                @endif">
                                {{ ucfirst($order->status) }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-8 text-center text-gray-500">
                        <svg class="mx-auto h-8 w-8 text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="text-sm">Belum ada order penjualan</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Recent Purchase Orders --}}
        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <h3 class="font-semibold text-gray-900">Order Pembelian Terbaru</h3>
                <a href="{{ route('purchasing.index') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">Lihat Semua</a>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($recentPurchases as $order)
                    <div class="px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition">
                        <div class="flex items-center gap-3">
                            <div class="h-9 w-9 rounded-full bg-orange-100 flex items-center justify-center text-sm font-bold text-orange-700">
                                {{ strtoupper(substr($order->supplier->name ?? 'N', 0, 1)) }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $order->number }}</p>
                                <p class="text-xs text-gray-500">{{ $order->supplier->name ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-gray-900">Rp {{ number_format($order->total ?? 0, 0, ',', '.') }}</p>
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium
                                @if($order->status === 'received') bg-green-100 text-green-700
                                @elseif($order->status === 'draft') bg-gray-100 text-gray-600
                                @elseif($order->status === 'confirmed') bg-blue-100 text-blue-700
                                @elseif($order->status === 'cancelled') bg-red-100 text-red-700
                                @else bg-yellow-100 text-yellow-700
                                @endif">
                                {{ ucfirst($order->status) }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-8 text-center text-gray-500">
                        <svg class="mx-auto h-8 w-8 text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="text-sm">Belum ada order pembelian</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Low Stock Alert --}}
    @if($lowStockItems->count() > 0)
    <div class="bg-white rounded-2xl shadow-sm ring-1 ring-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <h3 class="font-semibold text-gray-900">Peringatan Stok Menipis</h3>
            </div>
            <a href="{{ route('inventory.stocks.index') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">Kelola Stok</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Produk</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">SKU</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Stok</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Min. Stok</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($lowStockItems as $stock)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $stock->product->name ?? 'N/A' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $stock->product->sku ?? '-' }}</td>
                        <td class="px-6 py-4 text-sm text-right font-semibold text-red-600">{{ number_format($stock->quantity) }}</td>
                        <td class="px-6 py-4 text-sm text-right text-gray-500">{{ number_format($stock->product->min_stock ?? 0) }}</td>
                        <td class="px-6 py-4 text-right">
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium bg-red-100 text-red-700">
                                Stok Rendah
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
