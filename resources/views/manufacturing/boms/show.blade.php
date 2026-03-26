@extends('layouts.app')

@section('title', $bom->name)

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <a href="{{ route('manufacturing.boms.index') }}" class="hover:text-gray-700">Bill of Materials</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ $bom->name }}</span>
@endsection

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $bom->name }}</h1>
            <p class="mt-1 text-sm text-gray-500">BOM details and material requirements.</p>
        </div>
        <div class="flex gap-2">
            @include('components.button', ['label' => 'Edit', 'type' => 'secondary', 'href' => route('manufacturing.boms.edit', $bom)])
            @include('components.button', ['label' => 'Back', 'type' => 'ghost', 'href' => route('manufacturing.boms.index')])
        </div>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- BOM Info --}}
        <div class="lg:col-span-1 space-y-6">
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-base font-semibold text-gray-900 mb-4">BOM Info</h3>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-gray-500">Output Product</dt>
                        <dd class="font-medium text-gray-900 mt-0.5">{{ $bom->product->name ?? '—' }}</dd>
                        <dd class="text-xs text-gray-400">{{ $bom->product->sku ?? '' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Output Quantity</dt>
                        <dd class="font-medium text-gray-900 mt-0.5">{{ number_format($bom->output_quantity, 0) }} {{ $bom->product->unit ?? '' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Status</dt>
                        <dd class="mt-0.5">
                            @if($bom->is_active)
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 bg-green-50 text-green-700 ring-green-600/20">Active</span>
                            @else
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 bg-gray-100 text-gray-600 ring-gray-500/20">Inactive</span>
                            @endif
                        </dd>
                    </div>
                    @if($bom->description)
                        <div>
                            <dt class="text-gray-500">Description</dt>
                            <dd class="text-gray-700 mt-0.5">{{ $bom->description }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>

        {{-- Materials Table --}}
        <div class="lg:col-span-2">
            <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-900">Required Materials</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Per {{ number_format($bom->output_quantity, 0) }} {{ $bom->product->unit ?? 'unit' }} of output</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">#</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Material</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Quantity</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($bom->items as $item)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-6 py-3 text-sm text-gray-400">{{ $loop->iteration }}</td>
                                    <td class="px-6 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-700 font-semibold text-xs">
                                                {{ strtoupper(substr($item->product->name ?? '?', 0, 2)) }}
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">{{ $item->product->name ?? '—' }}</p>
                                                <p class="text-xs text-gray-500">{{ $item->product->sku ?? '' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-3 text-right text-sm font-semibold text-gray-900">
                                        {{ rtrim(rtrim(number_format($item->quantity, 4), '0'), '.') }}
                                        <span class="text-xs font-normal text-gray-400">{{ $item->product->unit ?? '' }}</span>
                                    </td>
                                    <td class="px-6 py-3 text-sm text-gray-500">
                                        {{ $item->notes ?? '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-400">
                                        No materials defined.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
