@extends('layouts.app')

@section('title', __('messages.stock_management'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">{{ __('messages.dashboard') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('messages.stock_management') }}</span>
@endsection

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.stock_management') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.stock_management_subtitle') }}</p>
        </div>
        <div class="flex gap-2">
            @include('components.button', [
                'label' => __('messages.new_movement'),
                'type' => 'primary',
                'href' => route('inventory.movements.create'),
                'icon' => '<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>',
            ])
            @include('components.button', [
                'label' => __('messages.new_transfer'),
                'type' => 'secondary',
                'href' => route('inventory.transfers.create'),
                'icon' => '<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>',
            ])
        </div>
    </div>
@endsection

@section('content')
    {{-- Tab Navigation --}}
    <div x-data="{ activeTab: '{{ request('tab', 'levels') }}' }" class="space-y-6">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex gap-6" aria-label="Tabs">
                <button @click="activeTab = 'levels'" :class="activeTab === 'levels' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'" class="whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium transition">
                    {{ __('messages.stock_levels') }}
                    <span class="ml-1.5 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">{{ $stocks->total() }}</span>
                </button>
                <button @click="activeTab = 'movements'" :class="activeTab === 'movements' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'" class="whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium transition">
                    {{ __('messages.stock_movements_title') }}
                    <span class="ml-1.5 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">{{ $movements->total() }}</span>
                </button>
                <button @click="activeTab = 'transfers'" :class="activeTab === 'transfers' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'" class="whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium transition">
                    {{ __('messages.stock_transfers_title') }}
                    <span class="ml-1.5 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">{{ $transfers->total() }}</span>
                </button>
            </nav>
        </div>

        {{-- ==================== TAB: STOCK LEVELS ==================== --}}
        <div x-show="activeTab === 'levels'" x-cloak>
            <div class="mb-4 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
                <form method="GET" action="{{ route('inventory.stock.index') }}" class="flex flex-col sm:flex-row gap-3">
                    <input type="hidden" name="tab" value="levels">
                    <div class="flex-1">
                        <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('messages.search_by_product_name_sku') }}"
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition" />
                    </div>
                    <select name="warehouse_id" class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                        <option value="">{{ __('messages.all_warehouses') }}</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" @selected(request('warehouse_id') == $wh->id)>{{ $wh->name }}</option>
                        @endforeach
                    </select>
                    <div class="flex gap-2">
                        @include('components.button', ['label' => __('messages.filter_btn'), 'type' => 'secondary', 'buttonType' => 'submit'])
                        @if(request()->hasAny(['search', 'warehouse_id']))
                            @include('components.button', ['label' => __('messages.clear_btn'), 'type' => 'ghost', 'href' => route('inventory.stock.index', ['tab' => 'levels'])])
                        @endif
                    </div>
                </form>
            </div>
            <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.product_column') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.warehouse_column') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.quantity_column') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.reserved_column') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.available_column') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.status_column') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($stocks as $stock)
                                @php
                                    $isLow = $stock->product && $stock->product->min_stock > 0 && $stock->quantity <= $stock->product->min_stock;
                                    $available = max(0, $stock->quantity - $stock->reserved_quantity);
                                @endphp
                                <tr class="hover:bg-gray-50/50 transition-colors {{ $isLow ? 'bg-red-50/30' : '' }}">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-700 font-semibold text-xs">
                                                {{ strtoupper(substr($stock->product->name ?? '?', 0, 2)) }}
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">{{ $stock->product->name ?? '—' }}</p>
                                                <p class="text-xs text-gray-500">{{ $stock->product->sku ?? '' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $stock->warehouse->name ?? '—' }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold text-gray-900">{{ number_format($stock->quantity) }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-500">{{ number_format($stock->reserved_quantity) }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold {{ $available > 0 ? 'text-green-700' : 'text-gray-400' }}">{{ number_format($available) }}</td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        @if($isLow)
                                            <span class="inline-flex rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-700 ring-1 ring-red-300">{{ __('messages.low_stock_label') }}</span>
                                        @else
                                            <span class="inline-flex rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700 ring-1 ring-green-300">{{ __('messages.in_stock_label') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-6 py-12 text-center text-sm text-gray-400">{{ __('messages.no_stock_data') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($stocks->hasPages())
                    <div class="border-t border-gray-100 px-6 py-4">{{ $stocks->appends(request()->query())->links() }}</div>
                @endif
            </div>
        </div>

        {{-- ==================== TAB: MOVEMENTS ==================== --}}
        <div x-show="activeTab === 'movements'" x-cloak>
            <div class="mb-4 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
                <form method="GET" action="{{ route('inventory.stock.index') }}" class="flex flex-col sm:flex-row gap-3">
                    <input type="hidden" name="tab" value="movements">
                    <div class="flex-1">
                        <input type="search" name="mv_search" value="{{ request('mv_search') }}" placeholder="{{ __('messages.search_movement_placeholder') }}"
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition" />
                    </div>
                    <select name="mv_type" class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                        <option value="">{{ __('messages.all_types') }}</option>
                        <option value="in" @selected(request('mv_type') === 'in')>{{ __('messages.stock_in_label') }}</option>
                        <option value="out" @selected(request('mv_type') === 'out')>{{ __('messages.stock_out_label') }}</option>
                        <option value="adjustment" @selected(request('mv_type') === 'adjustment')>{{ __('messages.adjustment_label') }}</option>
                    </select>
                    <div class="flex gap-2">
                        @include('components.button', ['label' => __('messages.filter_btn'), 'type' => 'secondary', 'buttonType' => 'submit'])
                        @if(request()->hasAny(['mv_search', 'mv_type']))
                            @include('components.button', ['label' => __('messages.clear_btn'), 'type' => 'ghost', 'href' => route('inventory.stock.index', ['tab' => 'movements'])])
                        @endif
                    </div>
                </form>
            </div>
            <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.movement_date_header') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.movement_product_header') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.movement_warehouse_header') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.movement_type_header') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.movement_qty_header') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.movement_reference_header') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($movements as $mv)
                                @php $typeColors = \App\Models\StockMovement::typeColors(); @endphp
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ $mv->created_at->format('M d, Y') }}</td>
                                    <td class="px-6 py-4">
                                        <p class="text-sm font-medium text-gray-900">{{ $mv->product->name ?? '—' }}</p>
                                        <p class="text-xs text-gray-500">{{ $mv->product->sku ?? '' }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $mv->warehouse->name ?? '—' }}</td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $typeColors[$mv->type] ?? 'bg-gray-100 text-gray-600 ring-gray-500/20' }}">{{ ucfirst($mv->type) }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold {{ $mv->type === 'in' ? 'text-green-700' : ($mv->type === 'out' ? 'text-red-700' : 'text-amber-700') }}">
                                        {{ $mv->type === 'in' ? '+' : ($mv->type === 'out' ? '-' : '±') }}{{ number_format($mv->quantity) }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $mv->reference ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-6 py-12 text-center text-sm text-gray-400">{{ __('messages.no_movements') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($movements->hasPages())
                    <div class="border-t border-gray-100 px-6 py-4">{{ $movements->appends(request()->query())->links() }}</div>
                @endif
            </div>
        </div>

        {{-- ==================== TAB: TRANSFERS ==================== --}}
        <div x-show="activeTab === 'transfers'" x-cloak>
            <div class="mb-4 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
                <form method="GET" action="{{ route('inventory.stock.index') }}" class="flex flex-col sm:flex-row gap-3">
                    <input type="hidden" name="tab" value="transfers">
                    <div class="flex-1">
                        <input type="search" name="tf_search" value="{{ request('tf_search') }}" placeholder="{{ __('messages.search_transfer_placeholder') }}"
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition" />
                    </div>
                    <select name="tf_status" class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                        <option value="">{{ __('messages.all_status') }}</option>
                        @foreach(\App\Models\StockTransfer::statusOptions() as $s)
                            <option value="{{ $s }}" @selected(request('tf_status') === $s)>{{ __('messages.status_' . $s) }}</option>
                        @endforeach
                    </select>
                    <div class="flex gap-2">
                        @include('components.button', ['label' => __('messages.filter_btn'), 'type' => 'secondary', 'buttonType' => 'submit'])
                        @if(request()->hasAny(['tf_search', 'tf_status']))
                            @include('components.button', ['label' => __('messages.clear_btn'), 'type' => 'ghost', 'href' => route('inventory.stock.index', ['tab' => 'transfers'])])
                        @endif
                    </div>
                </form>
            </div>
            <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.transfer_number_header') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.transfer_product_header') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.transfer_from_to_header') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.transfer_qty_header') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.transfer_status_header') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.transfer_date_header') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.transfer_actions_header') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($transfers as $transfer)
                                @php $statusColors = \App\Models\StockTransfer::statusColors(); @endphp
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-6 py-4"><span class="text-sm font-semibold text-gray-900">{{ $transfer->number }}</span></td>
                                    <td class="px-6 py-4">
                                        <p class="text-sm font-medium text-gray-900">{{ $transfer->product->name ?? '—' }}</p>
                                        <p class="text-xs text-gray-500">{{ $transfer->product->sku ?? '' }}</p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2 text-sm">
                                            <span class="text-gray-700">{{ $transfer->fromWarehouse->name ?? '—' }}</span>
                                            <svg class="h-4 w-4 text-gray-400 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                                            <span class="text-gray-700">{{ $transfer->toWarehouse->name ?? '—' }}</span>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold text-gray-900">{{ number_format($transfer->quantity) }}</td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $statusColors[$transfer->status] ?? '' }}">{{ __('messages.status_' . $transfer->status) }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ $transfer->created_at->format('M d, Y') }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right">
                                        @if($transfer->status === 'pending')
                                            <div class="flex justify-end gap-2">
                                                <form method="POST" action="{{ route('inventory.transfers.execute', $transfer) }}">
                                                    @csrf
                                                    @include('components.button', ['label' => __('messages.execute'), 'type' => 'primary', 'buttonType' => 'submit', 'size' => 'sm'])
                                                </form>
                                                <form method="POST" action="{{ route('inventory.transfers.cancel', $transfer) }}">
                                                    @csrf
                                                    @include('components.button', ['label' => __('messages.cancel'), 'type' => 'ghost', 'buttonType' => 'submit', 'size' => 'sm'])
                                                </form>
                                            </div>
                                        @else
                                            <span class="text-sm text-gray-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-6 py-12 text-center text-sm text-gray-400">{{ __('messages.no_transfers') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($transfers->hasPages())
                    <div class="border-t border-gray-100 px-6 py-4">{{ $transfers->appends(request()->query())->links() }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection
