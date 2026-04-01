@extends('layouts.app')

@section('title', 'Products')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">{{ __('messages.dashboard') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('messages.products') }}</span>
@endsection

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.products') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.manage_stock') }}</p>
        </div>
        @include('components.button', [
            'label' => __('messages.add_product'),
            'type' => 'primary',
            'href' => route('inventory.products.create'),
            'icon' => '<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>',
        ])
    </div>
@endsection

@section('content')
    {{-- Filters --}}
    <div class="mb-6 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
        <form method="GET" action="{{ route('inventory.products.index') }}" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <input type="search" name="search" value="{{ request('search') }}"
                    placeholder="{{ __('messages.search_by_name_or_sku') }}"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition" />
            </div>
            <select name="category_id" class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                <option value="">{{ __('messages.all_categories') }}</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>{{ $cat->name }}</option>
                @endforeach
            </select>
            <select name="type" class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                <option value="">{{ __('messages.all_types') }}</option>
                @foreach(\App\Models\Product::typeOptions() as $type)
                    <option value="{{ $type }}" @selected(request('type') === $type)>{{ __('messages.' . str_replace('_', '_', $type)) ?? ucwords(str_replace('_', ' ', $type)) }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                @include('components.button', ['label' => __('messages.filter_btn'), 'type' => 'secondary', 'buttonType' => 'submit'])
                @if(request()->hasAny(['search', 'category_id', 'type']))
                    @include('components.button', ['label' => __('messages.clear_btn'), 'type' => 'ghost', 'href' => route('inventory.products.index')])
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
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.product_header') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.category_header') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.type_header') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.unit_header') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.price_header') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.stock_header') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.actions_header') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($products as $product)
                        @php
                            $totalStock = $product->inventoryStocks->sum('quantity');
                            $lowStock = $product->min_stock > 0 && $totalStock <= $product->min_stock;
                        @endphp
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $product->is_active ? 'bg-primary-50 text-primary-700' : 'bg-gray-100 text-gray-400' }} font-semibold text-xs">
                                        {{ strtoupper(substr($product->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ $product->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $product->sku }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                {{ $product->category?->name ?? '—' }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @php
                                    $typeBadge = match($product->type) {
                                        'raw_material'   => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                                        'semi_finished'  => 'bg-purple-50 text-purple-700 ring-purple-600/20',
                                        'finished_good'  => 'bg-green-50 text-green-700 ring-green-600/20',
                                        'consumable'     => 'bg-gray-100 text-gray-600 ring-gray-500/20',
                                        default          => 'bg-gray-100 text-gray-600 ring-gray-500/20',
                                    };
                                @endphp
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $typeBadge }}">
                                    {{ ucwords(str_replace('_', ' ', $product->type)) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                {{ $product->unit }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-700">
                                <p>{{ format_currency($product->sell_price) }}</p>
                                <p class="text-xs text-gray-400">{{ __('messages.cost_label') }}: {{ format_currency($product->cost_price) }}</p>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                <span class="inline-flex items-center gap-1 text-sm font-semibold {{ $lowStock ? 'text-red-600' : 'text-gray-900' }}">
                                    @if($lowStock)
                                        <svg class="h-4 w-4 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    @endif
                                    {{ number_format($totalStock, 0) }}
                                </span>
                                @if($product->min_stock > 0)
                                    <p class="text-xs {{ $lowStock ? 'text-red-400' : 'text-gray-400' }}">{{ __('messages.min_label') }}: {{ $product->min_stock }}</p>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm space-x-1">
                                <a href="{{ route('inventory.products.edit', $product) }}"
                                   class="inline-flex items-center rounded-lg px-2.5 py-1.5 text-xs font-medium text-primary-700 bg-primary-50 hover:bg-primary-100 transition">
                                    {{ __('messages.edit_btn') }}
                                </a>
                                <form method="POST" action="{{ route('inventory.products.destroy', $product) }}" class="inline" onsubmit="return confirm('{{ __('messages.delete_product_confirm') }} {{ $product->name }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="inline-flex items-center rounded-lg px-2.5 py-1.5 text-xs font-medium text-red-700 bg-red-50 hover:bg-red-100 transition">
                                        {{ __('messages.delete_btn') }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <svg class="h-12 w-12 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-500">{{ __('messages.no_products_found') }}</p>
                                    <a href="{{ route('inventory.products.create') }}" class="mt-3 text-sm font-medium text-primary-600 hover:text-primary-700">
                                        {{ __('messages.add_your_first_product') }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($products->hasPages())
            <div class="border-t border-gray-100 px-6 py-4">
                {{ $products->links() }}
            </div>
        @endif
    </div>
@endsection
