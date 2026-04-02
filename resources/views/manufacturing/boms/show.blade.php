@extends('layouts.app')

@section('title', $bom->name)

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">{{ __('messages.dashboard') }}</a>
    <span class="mx-1">/</span>
    <a href="{{ route('manufacturing.boms.index') }}" class="hover:text-gray-700">{{ __('messages.bill_of_materials_title') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ $bom->name }}</span>
@endsection

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $bom->name }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.bom_details_subtitle') }}
                @if($bom->version > 1) <span class="ml-1 inline-flex rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700 ring-1 ring-indigo-600/20">v{{ $bom->version }}</span> @endif
                @if($maxDepth > 0) <span class="ml-1 inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-amber-600/20">{{ __('messages.bom_depth_label') }}: {{ $maxDepth }}</span> @endif
            </p>
        </div>
        <div class="flex gap-2">
            <form action="{{ route('manufacturing.boms.new-version', $bom) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-indigo-50 px-4 py-2.5 text-sm font-semibold text-indigo-700 hover:bg-indigo-100 transition">
                    {{ __('messages.new_version_btn') }}
                </button>
            </form>
            @include('components.button', ['label' => __('messages.edit_bom_btn'), 'type' => 'secondary', 'href' => route('manufacturing.boms.edit', $bom)])
            @include('components.button', ['label' => __('messages.cancel'), 'type' => 'ghost', 'href' => route('manufacturing.boms.index')])
        </div>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- BOM Info --}}
        <div class="lg:col-span-1 space-y-6">
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-base font-semibold text-gray-900 mb-4">{{ __('messages.bom_info_section') }}</h3>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-gray-500">{{ __('messages.output_product_label_show') }}</dt>
                        <dd class="font-medium text-gray-900 mt-0.5">{{ $bom->product->name ?? '—' }}</dd>
                        <dd class="text-xs text-gray-400">{{ $bom->product->sku ?? '' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('messages.output_quantity_label_show') }}</dt>
                        <dd class="font-medium text-gray-900 mt-0.5">{{ number_format($bom->output_quantity, 0) }} {{ $bom->product->unit ?? '' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('messages.status_label_show') }}</dt>
                        <dd class="mt-0.5">
                            @if($bom->is_active)
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 bg-green-50 text-green-700 ring-green-600/20">{{ __('messages.bom_status_active') }}</span>
                            @else
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 bg-gray-100 text-gray-600 ring-gray-500/20">{{ __('messages.bom_status_inactive') }}</span>
                            @endif
                        </dd>
                    </div>
                    @if($bom->description)
                        <div>
                            <dt class="text-gray-500">{{ __('messages.bom_description_label') }}</dt>
                            <dd class="text-gray-700 mt-0.5">{{ $bom->description }}</dd>
                        </div>
                    @endif
                    @if($bom->version > 1)
                        <div>
                            <dt class="text-gray-500">{{ __('messages.bom_version_label') }}</dt>
                            <dd class="font-medium text-gray-900 mt-0.5">v{{ $bom->version }}</dd>
                        </div>
                    @endif
                    @if($bom->parentBom)
                        <div>
                            <dt class="text-gray-500">{{ __('messages.parent_bom_label') }}</dt>
                            <dd class="mt-0.5"><a href="{{ route('manufacturing.boms.show', $bom->parentBom) }}" class="text-primary-600 hover:underline text-sm font-medium">{{ $bom->parentBom->name }}</a></dd>
                        </div>
                    @endif
                </dl>
            </div>

            {{-- Cost Summary --}}
            @if($costBreakdown)
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">{{ __('messages.cost_summary') }}</h3>
                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="text-gray-500">{{ __('messages.total_material_cost') }}</dt>
                            <dd class="font-semibold text-gray-900 mt-0.5">{{ format_currency($costBreakdown['material_cost'] ?? 0) }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">{{ __('messages.cost_per_unit_label') }}</dt>
                            <dd class="font-semibold text-lg text-primary-600 mt-0.5">{{ format_currency($costBreakdown['cost_per_unit'] ?? 0) }}</dd>
                        </div>
                    </dl>
                </div>
            @endif
        </div>

        {{-- Materials Table --}}
        <div class="lg:col-span-2">
            <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-900">{{ __('messages.required_materials_section') }}</h3>
                    <p class="text-xs text-gray-500 mt-0.5">{{ __('messages.per_output_text') }} {{ number_format($bom->output_quantity, 0) }} {{ $bom->product->unit ?? 'unit' }} {{ __('messages.of_output_text') }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.item_number_header') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.material_header') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.quantity_header') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.unit_cost_label') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.line_cost_label') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.notes_header') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($bom->items as $item)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-6 py-3 text-sm text-gray-400">{{ $loop->iteration }}</td>
                                    <td class="px-6 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $item->isSubAssembly() ? 'bg-indigo-50 text-indigo-700' : 'bg-amber-50 text-amber-700' }} font-semibold text-xs">
                                                {{ strtoupper(substr($item->product->name ?? '?', 0, 2)) }}
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">{{ $item->product->name ?? '—' }}</p>
                                                <p class="text-xs text-gray-500">{{ $item->product->sku ?? '' }}</p>
                                                @if($item->isSubAssembly())
                                                    <a href="{{ route('manufacturing.boms.show', $item->sub_bom_id) }}" class="text-xs text-indigo-600 hover:underline">{{ __('messages.view_sub_bom') }} &rarr;</a>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-3 text-right text-sm font-semibold text-gray-900">
                                        {{ rtrim(rtrim(number_format($item->quantity, 4), '0'), '.') }}
                                        <span class="text-xs font-normal text-gray-400">{{ $item->product->unit ?? '' }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-3 text-right text-sm text-gray-600">{{ format_currency($item->unit_cost ?? 0) }}</td>
                                    <td class="whitespace-nowrap px-6 py-3 text-right text-sm font-semibold text-gray-900">{{ format_currency($item->line_cost ?? 0) }}</td>
                                    <td class="px-6 py-3 text-sm text-gray-500">{{ $item->notes ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-400">
                                        {{ __('messages.no_materials_defined') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Flattened Multi-level BOM --}}
            @if($maxDepth > 0 && count($flattenedMaterials) > 0)
                <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden mt-6">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="text-base font-semibold text-gray-900">{{ __('messages.flattened_bom_title') }}</h3>
                        <p class="text-xs text-gray-500 mt-0.5">{{ __('messages.flattened_bom_subtitle') }}</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead class="bg-gray-50/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">{{ __('messages.bom_level') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">{{ __('messages.material_header') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">{{ __('messages.total_qty_label') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">{{ __('messages.unit_cost_label') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">{{ __('messages.line_cost_label') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($flattenedMaterials as $mat)
                                    <tr class="hover:bg-gray-50/50">
                                        <td class="px-6 py-3 text-sm">
                                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ ($mat['depth'] ?? 0) === 0 ? 'bg-blue-50 text-blue-700' : 'bg-gray-100 text-gray-600' }}">
                                                L{{ $mat['depth'] ?? 0 }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-3 text-sm" style="padding-left: {{ 1.5 + ($mat['depth'] ?? 0) * 1.5 }}rem">
                                            <span class="font-medium text-gray-900">{{ $mat['product_name'] ?? '—' }}</span>
                                        </td>
                                        <td class="px-6 py-3 text-right text-sm font-semibold text-gray-900">{{ number_format($mat['quantity'] ?? 0, 4) }}</td>
                                        <td class="px-6 py-3 text-right text-sm text-gray-600">{{ format_currency($mat['unit_cost'] ?? 0) }}</td>
                                        <td class="px-6 py-3 text-right text-sm font-semibold text-gray-900">{{ format_currency(($mat['quantity'] ?? 0) * ($mat['unit_cost'] ?? 0)) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
