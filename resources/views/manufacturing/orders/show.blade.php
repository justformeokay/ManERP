@extends('layouts.app')

@section('title', $order->number)

@php
    $statusColors = \App\Models\ManufacturingOrder::statusColors();
    $priorityColors = \App\Models\ManufacturingOrder::priorityColors();
    $progress = $order->progressPercent();
    $remaining = $order->planned_quantity - $order->produced_quantity;
    $canProduce = in_array($order->status, ['confirmed', 'in_progress']) && $remaining > 0;
@endphp

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">{{ __('messages.dashboard') }}</a>
    <span class="mx-1">/</span>
    <a href="{{ route('manufacturing.orders.index') }}" class="hover:text-gray-700">{{ __('messages.work_orders') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ $order->number }}</span>
@endsection

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $order->number }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $order->product->name ?? '—' }}</p>
        </div>
        <div class="flex items-center gap-2">
            @if($order->status === 'draft')
                <form method="POST" action="{{ route('manufacturing.orders.confirm', $order) }}" class="inline">
                    @csrf
                    @include('components.button', ['label' => __('messages.confirm_order_btn'), 'type' => 'primary', 'buttonType' => 'submit'])
                </form>
            @endif
            @include('components.button', ['label' => __('messages.edit_order_btn'), 'type' => 'secondary', 'href' => route('manufacturing.orders.edit', $order)])
            <form method="POST" action="{{ route('manufacturing.orders.destroy', $order) }}" class="inline"
                  onsubmit="return confirm('{{ __('messages.delete_order_confirm') }}')">
                @csrf @method('DELETE')
                @include('components.button', ['label' => __('messages.delete_order_btn'), 'type' => 'danger', 'buttonType' => 'submit'])
            </form>
        </div>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left Column: Order Info --}}
        <div class="lg:col-span-1 space-y-6">

            {{-- Progress Card --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100 text-center">
                <div class="relative inline-flex items-center justify-center w-28 h-28">
                    <svg class="w-28 h-28 -rotate-90" viewBox="0 0 120 120">
                        <circle cx="60" cy="60" r="50" stroke-width="10" stroke="#f3f4f6" fill="none" />
                        <circle cx="60" cy="60" r="50" stroke-width="10" fill="none"
                            stroke="{{ $progress >= 100 ? '#22c55e' : '#3b82f6' }}"
                            stroke-linecap="round"
                            stroke-dasharray="{{ 2 * 3.14159 * 50 }}"
                            stroke-dashoffset="{{ 2 * 3.14159 * 50 * (1 - $progress / 100) }}" />
                    </svg>
                    <span class="absolute text-xl font-bold text-gray-900">{{ $progress }}%</span>
                </div>
                <p class="mt-3 text-sm text-gray-500">{{ number_format($order->produced_quantity, 0) }} {{ __('messages.produced_of_planned') }} {{ number_format($order->planned_quantity, 0) }}</p>
                <p class="text-xs text-gray-400 mt-0.5">{{ number_format($remaining, 0) }} {{ __('messages.remaining_text') }}</p>
            </div>

            {{-- Details Card --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-sm font-semibold text-gray-900 mb-4">{{ __('messages.order_info_card') }}</h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">{{ __('messages.status_header') }}</dt>
                        <dd>
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $statusColors[$order->status] ?? '' }}">
                                {{ ucwords(str_replace('_', ' ', $order->status)) }}
                            </span>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">{{ __('messages.priority_header') }}</dt>
                        <dd>
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $priorityColors[$order->priority] ?? '' }}">
                                {{ ucfirst($order->priority) }}
                            </span>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">{{ __('messages.bom_info_text') }}</dt>
                        <dd class="font-medium text-gray-900">
                            <a href="{{ route('manufacturing.boms.show', $order->bom) }}" class="text-primary-700 hover:text-primary-800">
                                {{ $order->bom->name ?? '—' }}
                            </a>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">{{ __('messages.warehouse_text') }}</dt>
                        <dd class="font-medium text-gray-900">{{ $order->warehouse->name ?? '—' }}</dd>
                    </div>
                    @if($order->project)
                        <div class="flex justify-between">
                            <dt class="text-gray-500">{{ __('messages.project_text') }}</dt>
                            <dd class="font-medium text-gray-900">{{ $order->project->name }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            {{-- Schedule Card --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-sm font-semibold text-gray-900 mb-4">{{ __('messages.schedule_card') }}</h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">{{ __('messages.start_date_text') }}</dt>
                        <dd class="font-medium text-gray-900">{{ $order->planned_start?->format('M d, Y') ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">{{ __('messages.due_date_text') }}</dt>
                        <dd class="font-medium text-gray-900">{{ $order->planned_end?->format('M d, Y') ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">{{ __('messages.start_date_text') }} ({{ __('messages.delivered_text') }})</dt>
                        <dd class="font-medium {{ $order->actual_start ? 'text-gray-900' : 'text-gray-400' }}">
                            {{ $order->actual_start?->format('M d, Y') ?? '—' }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">{{ __('messages.due_date_text') }} ({{ __('messages.delivered_text') }})</dt>
                        <dd class="font-medium {{ $order->actual_end ? 'text-gray-900' : 'text-gray-400' }}">
                            {{ $order->actual_end?->format('M d, Y') ?? '—' }}
                        </dd>
                    </div>
                </dl>
            </div>

            @if($order->notes)
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <h3 class="text-sm font-semibold text-gray-900 mb-2">{{ __('messages.quality_notes_text') }}</h3>
                    <p class="text-sm text-gray-600 whitespace-pre-line">{{ $order->notes }}</p>
                </div>
            @endif
        </div>

        {{-- Right Column --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Produce Action --}}
            @if($canProduce)
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-primary-100 border border-primary-100">
                    <h3 class="text-base font-semibold text-gray-900 mb-3">{{ __('messages.produce_label', ['default' => 'Record Production']) }}</h3>
                    <form method="POST" action="{{ route('manufacturing.orders.produce', $order) }}" class="flex flex-col sm:flex-row items-end gap-3">
                        @csrf
                        <div class="flex-1 w-full">
                            <label for="quantity" class="block text-sm font-medium text-gray-700 mb-1">Quantity to Produce</label>
                            <input type="number" name="quantity" id="quantity"
                                value="{{ old('quantity') }}" min="0.01" max="{{ $remaining }}" step="0.01" required
                                placeholder="Max {{ number_format($remaining, 2) }}"
                                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500" />
                            @error('quantity') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        @include('components.button', [
                            'label' => 'Produce',
                            'type' => 'primary',
                            'buttonType' => 'submit',
                            'icon' => '<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>',
                        ])
                    </form>
                    <p class="text-xs text-gray-500 mt-2">This will consume raw materials and produce finished goods in <strong>{{ $order->warehouse->name ?? 'warehouse' }}</strong>.</p>
                </div>
            @elseif($order->status === 'done')
                <div class="rounded-2xl bg-green-50 p-6 shadow-sm ring-1 ring-green-100">
                    <div class="flex items-center gap-3">
                        <svg class="h-6 w-6 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <p class="text-sm font-semibold text-green-800">Production Complete</p>
                            <p class="text-xs text-green-600">All {{ number_format($order->planned_quantity, 0) }} units have been produced.</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Materials Required --}}
            <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-900">Materials Required</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Scaled to planned quantity of {{ number_format($order->planned_quantity, 0) }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">#</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Material</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Per Batch</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Total Required</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @php
                                $ratio = $order->bom ? ($order->planned_quantity / max(1, $order->bom->output_quantity)) : 0;
                            @endphp
                            @forelse($order->bom->items ?? [] as $i => $item)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-6 py-3 text-sm text-gray-400">{{ $i + 1 }}</td>
                                    <td class="px-6 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="h-8 w-8 rounded-lg bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-500">
                                                {{ strtoupper(substr($item->product->name ?? '?', 0, 2)) }}
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">{{ $item->product->name ?? '—' }}</p>
                                                <p class="text-xs text-gray-500">{{ $item->product->sku ?? '' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-3 text-right text-sm text-gray-700">
                                        {{ number_format($item->quantity, 2) }}
                                        <span class="text-gray-400">{{ $item->product->unit ?? '' }}</span>
                                    </td>
                                    <td class="px-6 py-3 text-right text-sm font-medium text-gray-900">
                                        {{ number_format($item->quantity * $ratio, 2) }}
                                        <span class="text-gray-400">{{ $item->product->unit ?? '' }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-400">No materials defined in the BOM.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Output Product --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-base font-semibold text-gray-900 mb-3">Output Product</h3>
                <div class="flex items-center gap-4">
                    <div class="h-12 w-12 rounded-xl bg-primary-50 flex items-center justify-center text-sm font-bold text-primary-700">
                        {{ strtoupper(substr($order->product->name ?? '?', 0, 2)) }}
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $order->product->name ?? '—' }}</p>
                        <p class="text-xs text-gray-500">{{ $order->product->sku ?? '' }}</p>
                    </div>
                    <div class="ml-auto text-right">
                        <p class="text-lg font-bold text-gray-900">{{ number_format($order->planned_quantity, 0) }}</p>
                        <p class="text-xs text-gray-500">planned {{ $order->product->unit ?? 'units' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
