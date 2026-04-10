@extends('layouts.app')

@section('title', $order->number)

@php
    $statusColors = \App\Models\PurchaseOrder::statusColors();
    $purchaseTypeColors = \App\Models\PurchaseOrder::purchaseTypeColors();
    $priorityColors = \App\Models\PurchaseOrder::priorityColors();
    $canReceive = in_array($order->status, ['confirmed', 'partial']);
@endphp

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <a href="{{ route('purchasing.index') }}" class="hover:text-gray-700">Purchase Orders</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ $order->number }}</span>
@endsection

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-gray-900">{{ $order->number }}</h1>
                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $statusColors[$order->status] ?? '' }}">
                    {{ ucfirst($order->status) }}
                </span>
                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $purchaseTypeColors[$order->purchase_type] ?? $purchaseTypeColors['operational'] }}">
                    {{ $order->purchaseTypeLabel() }}
                </span>
                @if($order->priority !== 'normal')
                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $priorityColors[$order->priority] ?? $priorityColors['normal'] }}">
                        {{ __('messages.po_priority_' . $order->priority) }}
                    </span>
                @endif
            </div>
            <p class="mt-1 text-sm text-gray-500">{{ $order->supplier->name ?? '—' }} {{ $order->supplier->company ? '— ' . $order->supplier->company : '' }}</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            @if($order->status === 'draft')
                <form method="POST" action="{{ route('purchasing.confirm', $order) }}" class="inline"
                      onsubmit="return confirm('Confirm this purchase order?')">
                    @csrf
                    @include('components.button', [
                        'label' => 'Confirm PO',
                        'type' => 'primary',
                        'buttonType' => 'submit',
                        'icon' => '<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>',
                    ])
                </form>
                @include('components.button', ['label' => 'Edit', 'type' => 'secondary', 'href' => route('purchasing.edit', $order)])
                <form method="POST" action="{{ route('purchasing.destroy', $order) }}" class="inline"
                      onsubmit="return confirm('Delete this purchase order?')">
                    @csrf @method('DELETE')
                    @include('components.button', ['label' => 'Delete', 'type' => 'danger', 'buttonType' => 'submit'])
                </form>
            @endif

            @if(!in_array($order->status, ['received', 'cancelled']))
                <form method="POST" action="{{ route('purchasing.cancel', $order) }}" class="inline"
                      onsubmit="return confirm('Cancel this purchase order?{{ in_array($order->status, ['partial']) ? ' Received stock will be reversed.' : '' }}')">
                    @csrf
                    @include('components.button', ['label' => 'Cancel PO', 'type' => 'danger', 'buttonType' => 'submit'])
                </form>
            @endif
        </div>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left: Order Info --}}
        <div class="lg:col-span-1 space-y-6">

            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-sm font-semibold text-gray-900 mb-4">Order Information</h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Supplier</dt>
                        <dd class="font-medium text-gray-900">{{ $order->supplier->name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Warehouse</dt>
                        <dd class="font-medium text-gray-900">{{ $order->warehouse->name ?? '—' }}</dd>
                    </div>
                    @if($order->project)
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Project</dt>
                            <dd class="font-medium text-gray-900">{{ $order->project->name }}</dd>
                        </div>
                    @endif
                    @if($order->department)
                        <div class="flex justify-between">
                            <dt class="text-gray-500">{{ __('messages.po_requesting_dept') }}</dt>
                            <dd class="font-medium text-gray-900">{{ $order->department->name }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <dt class="text-gray-500">{{ __('messages.po_priority') }}</dt>
                        <dd>
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $priorityColors[$order->priority] ?? $priorityColors['normal'] }}">
                                {{ __('messages.po_priority_' . $order->priority) }}
                            </span>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Order Date</dt>
                        <dd class="font-medium text-gray-900">{{ $order->order_date->format('M d, Y') }}</dd>
                    </div>
                    @if($order->expected_date)
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Expected Delivery</dt>
                            <dd class="font-medium text-gray-900">{{ $order->expected_date->format('M d, Y') }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            {{-- Totals --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-sm font-semibold text-gray-900 mb-4">Summary</h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Subtotal</dt>
                        <dd class="font-medium text-gray-900">{{ format_currency($order->subtotal) }}</dd>
                    </div>
                    @if($order->tax_amount > 0)
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Tax</dt>
                            <dd class="font-medium text-gray-900">+ {{ format_currency($order->tax_amount) }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between border-t border-gray-100 pt-3">
                        <dt class="font-semibold text-gray-900">Grand Total</dt>
                        <dd class="text-lg font-bold text-gray-900">{{ format_currency($order->total) }}</dd>
                    </div>
                </dl>
            </div>

            @if($order->notes)
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <h3 class="text-sm font-semibold text-gray-900 mb-2">Notes</h3>
                    <p class="text-sm text-gray-600 whitespace-pre-line">{{ $order->notes }}</p>
                </div>
            @endif

            @if($order->justification)
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <h3 class="text-sm font-semibold text-gray-900 mb-2">{{ __('messages.po_justification') }}</h3>
                    <p class="text-sm text-gray-600 whitespace-pre-line">{{ $order->justification }}</p>
                </div>
            @endif
        </div>

        {{-- Right: Items & Receive --}}
        <div class="lg:col-span-2 space-y-6">

            @if($order->status === 'received')
                <div class="rounded-2xl bg-green-50 p-4 shadow-sm ring-1 ring-green-100">
                    <div class="flex items-center gap-3">
                        <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-sm font-medium text-green-800">All items have been received and stocked.</p>
                    </div>
                </div>
            @elseif($order->status === 'partial')
                <div class="rounded-2xl bg-amber-50 p-4 shadow-sm ring-1 ring-amber-100">
                    <div class="flex items-center gap-3">
                        <svg class="h-5 w-5 text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-sm font-medium text-amber-800">Some items have been received. Fill in quantities below to receive more.</p>
                    </div>
                </div>
            @elseif($order->status === 'confirmed')
                <div class="rounded-2xl bg-blue-50 p-4 shadow-sm ring-1 ring-blue-100">
                    <div class="flex items-center gap-3">
                        <svg class="h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        <p class="text-sm font-medium text-blue-800">Order confirmed — enter quantities below to receive items into stock.</p>
                    </div>
                </div>
            @elseif($order->status === 'cancelled')
                <div class="rounded-2xl bg-red-50 p-4 shadow-sm ring-1 ring-red-100">
                    <div class="flex items-center gap-3">
                        <svg class="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        <p class="text-sm font-medium text-red-800">This purchase order has been cancelled.</p>
                    </div>
                </div>
            @endif

            {{-- Items Table / Receive Form --}}
            @if($canReceive)
                <form method="POST" action="{{ route('purchasing.receive', $order) }}">
                    @csrf
            @endif

            <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Order Items</h3>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $order->items->count() }} item(s)</p>
                    </div>
                    @if($canReceive)
                        @include('components.button', [
                            'label' => 'Receive Items',
                            'type' => 'primary',
                            'buttonType' => 'submit',
                            'icon' => '<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>',
                        ])
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">#</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Product</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Ordered</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Received</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Unit Price</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Total</th>
                                @if($canReceive)
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Receive Qty</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($order->items as $i => $item)
                                @php $remaining = $item->quantity - $item->received_quantity; @endphp
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
                                        {{ number_format($item->quantity, 0) }}
                                        <span class="text-gray-400">{{ $item->product->unit ?? '' }}</span>
                                    </td>
                                    <td class="px-6 py-3 text-right text-sm">
                                        <span class="{{ $item->received_quantity >= $item->quantity ? 'text-green-600 font-medium' : 'text-gray-700' }}">
                                            {{ number_format($item->received_quantity, 0) }}
                                        </span>
                                        @if($item->received_quantity >= $item->quantity)
                                            <svg class="inline h-4 w-4 text-green-500 ml-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 text-right text-sm text-gray-700">{{ format_currency($item->unit_price) }}</td>
                                    <td class="px-6 py-3 text-right text-sm font-semibold text-gray-900">{{ format_currency($item->total) }}</td>
                                    @if($canReceive)
                                        <td class="px-6 py-3 text-right">
                                            <input type="hidden" name="receive[{{ $i }}][item_id]" value="{{ $item->id }}">
                                            @if($remaining > 0)
                                                <input type="number" name="receive[{{ $i }}][quantity]" value="0"
                                                    min="0" max="{{ $remaining }}" step="0.01"
                                                    class="w-24 rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-sm text-right text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                                                <p class="text-xs text-gray-400 mt-0.5">max {{ number_format($remaining, 0) }}</p>
                                            @else
                                                <span class="text-xs text-green-600 font-medium">Done</span>
                                                <input type="hidden" name="receive[{{ $i }}][quantity]" value="0">
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if($canReceive)
                </form>
            @endif
        </div>
    </div>
@endsection
