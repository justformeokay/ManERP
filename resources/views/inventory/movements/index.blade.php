@extends('layouts.app')

@section('title', __('messages.stock_movements_title'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">{{ __('messages.dashboard') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('messages.stock_movements_title') }}</span>
@endsection

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.stock_movements_title') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.stock_movements_subtitle') }}</p>
        </div>
        @include('components.button', [
            'label' => __('messages.new_movement'),
            'type' => 'primary',
            'href' => route('inventory.movements.create'),
            'icon' => '<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>',
        ])
    </div>
@endsection

@section('content')
    {{-- Filters --}}
    <div class="mb-6 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
        <form method="GET" action="{{ route('inventory.movements.index') }}" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <input type="search" name="search" value="{{ request('search') }}"
                    placeholder="{{ __('messages.search_movement_placeholder') }}"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition" />
            </div>
            <select name="warehouse_id" class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                <option value="">{{ __('messages.all_warehouses') }}</option>
                @foreach($warehouses as $wh)
                    <option value="{{ $wh->id }}" @selected(request('warehouse_id') == $wh->id)>{{ $wh->name }}</option>
                @endforeach
            </select>
            <select name="type" class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                <option value="">{{ __('messages.all_types') }}</option>
                <option value="in" @selected(request('type') === 'in')>{{ __('messages.stock_in_label') }}</option>
                <option value="out" @selected(request('type') === 'out')>{{ __('messages.stock_out_label') }}</option>
                <option value="adjustment" @selected(request('type') === 'adjustment')>{{ __('messages.adjustment_label') }}</option>
            </select>
            <div class="flex gap-2">
                @include('components.button', ['label' => __('messages.filter_btn'), 'type' => 'secondary', 'buttonType' => 'submit'])
                @if(request()->hasAny(['search', 'warehouse_id', 'type']))
                    @include('components.button', ['label' => __('messages.clear_btn'), 'type' => 'ghost', 'href' => route('inventory.movements.index')])
                @endif
            </div>
        </form>
    </div>

    {{-- Table --}}
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
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.movement_balance_header') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.movement_reference_header') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.movement_notes_header') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($movements as $mv)
                        @php
                            $typeColors = \App\Models\StockMovement::typeColors();
                            $typeBadge = $typeColors[$mv->type] ?? 'bg-gray-100 text-gray-600 ring-gray-500/20';
                        @endphp
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                {{ $mv->created_at->format('M d, Y') }}
                                <p class="text-xs text-gray-400">{{ $mv->created_at->format('H:i') }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-medium text-gray-900">{{ $mv->product->name ?? '—' }}</p>
                                <p class="text-xs text-gray-500">{{ $mv->product->sku ?? '' }}</p>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                {{ $mv->warehouse->name ?? '—' }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $typeBadge }}">
                                    {{ strtoupper($mv->type) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold
                                {{ $mv->type === 'in' ? 'text-green-700' : ($mv->type === 'out' ? 'text-red-700' : 'text-amber-700') }}">
                                {{ $mv->type === 'in' ? '+' : ($mv->type === 'out' ? '-' : '~') }}{{ number_format($mv->quantity, 0) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-500">
                                {{ number_format($mv->balance_after, 0) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                {{ $mv->reference_type ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                                {{ $mv->notes ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <svg class="h-12 w-12 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-500">{{ __('messages.no_movements_found') }}</p>
                                    <a href="{{ route('inventory.movements.create') }}" class="mt-3 text-sm font-medium text-primary-600 hover:text-primary-700">
                                        {{ __('messages.record_first_movement') }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($movements->hasPages())
            <div class="border-t border-gray-100 px-6 py-4">
                {{ $movements->links() }}
            </div>
        @endif
    </div>
@endsection
