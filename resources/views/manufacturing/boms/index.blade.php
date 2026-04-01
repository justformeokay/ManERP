@extends('layouts.app')

@section('title', __('messages.bill_of_materials_title'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">{{ __('messages.dashboard') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('messages.bill_of_materials_title') }}</span>
@endsection

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.bill_of_materials_title') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.bill_of_materials_subtitle') }}</p>
        </div>
        @include('components.button', [
            'label' => __('messages.create_bom_btn'),
            'type' => 'primary',
            'href' => route('manufacturing.boms.create'),
            'icon' => '<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>',
        ])
    </div>
@endsection

@section('content')
    {{-- Filters --}}
    <div class="mb-6 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
        <form method="GET" action="{{ route('manufacturing.boms.index') }}" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <input type="search" name="search" value="{{ request('search') }}"
                    placeholder="{{ __('messages.search_bom_placeholder') }}"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition" />
            </div>
            <div class="flex gap-2">
                @include('components.button', ['label' => __('messages.filter_btn'), 'type' => 'secondary', 'buttonType' => 'submit'])
                @if(request()->hasAny(['search']))
                    @include('components.button', ['label' => __('messages.clear_btn'), 'type' => 'ghost', 'href' => route('manufacturing.boms.index')])
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
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.bom_name_header') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.output_product_header') }}</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.output_qty_header') }}</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.materials_header') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.status_label_show') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.bom_actions_header') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($boms as $bom)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <a href="{{ route('manufacturing.boms.show', $bom) }}" class="text-sm font-medium text-primary-700 hover:text-primary-800">
                                    {{ $bom->name }}
                                </a>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-green-50 text-green-700 font-semibold text-xs">
                                        {{ strtoupper(substr($bom->product->name ?? '?', 0, 2)) }}
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-900">{{ $bom->product->name ?? '—' }}</p>
                                        <p class="text-xs text-gray-500">{{ $bom->product->sku ?? '' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-center text-sm font-semibold text-gray-900">
                                {{ number_format($bom->output_quantity, 0) }}
                                <span class="text-xs font-normal text-gray-400">{{ $bom->product->unit ?? '' }}</span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">
                                    {{ $bom->items_count ?? $bom->items->count() }} {{ __('messages.bom_items_badge') }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @if($bom->is_active)
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 bg-green-50 text-green-700 ring-green-600/20">{{ __('messages.bom_status_active') }}</span>
                                @else
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 bg-gray-100 text-gray-600 ring-gray-500/20">{{ __('messages.bom_status_inactive') }}</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm space-x-1">
                                <a href="{{ route('manufacturing.boms.show', $bom) }}"
                                   class="inline-flex items-center rounded-lg px-2.5 py-1.5 text-xs font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 transition">
                                    {{ __('messages.view_bom_btn') }}
                                </a>
                                <a href="{{ route('manufacturing.boms.edit', $bom) }}"
                                   class="inline-flex items-center rounded-lg px-2.5 py-1.5 text-xs font-medium text-primary-700 bg-primary-50 hover:bg-primary-100 transition">
                                    {{ __('messages.edit_bom_btn') }}
                                </a>
                                <form method="POST" action="{{ route('manufacturing.boms.destroy', $bom) }}" class="inline" onsubmit="return confirm('{{ __('messages.delete_bom_confirm') }}')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="inline-flex items-center rounded-lg px-2.5 py-1.5 text-xs font-medium text-red-700 bg-red-50 hover:bg-red-100 transition">
                                        {{ __('messages.delete_bom_btn') }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <svg class="h-12 w-12 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-500">{{ __('messages.no_boms_found') }}</p>
                                    <a href="{{ route('manufacturing.boms.create') }}" class="mt-3 text-sm font-medium text-primary-600 hover:text-primary-700">
                                        {{ __('messages.create_first_bom') }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($boms->hasPages())
            <div class="border-t border-gray-100 px-6 py-4">
                {{ $boms->links() }}
            </div>
        @endif
    </div>
@endsection
