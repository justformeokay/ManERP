@extends('layouts.app')

@section('title', $order->number)

@php
    $statusColors = \App\Models\SalesOrder::statusColors();
@endphp

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <a href="{{ route('sales.index') }}" class="hover:text-gray-700">Sales Orders</a>
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
            </div>
            <p class="mt-1 text-sm text-gray-500">{{ $order->client->name ?? '—' }} {{ $order->client->company ? '— ' . $order->client->company : '' }}</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            @if($order->status === 'draft')
                <form method="POST" action="{{ route('sales.confirm', $order) }}" class="inline"
                      onsubmit="return confirm('Confirm this order? Stock will be deducted from inventory.')">
                    @csrf
                    @include('components.button', [
                        'label' => 'Confirm Order',
                        'type' => 'primary',
                        'buttonType' => 'submit',
                        'icon' => '<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>',
                    ])
                </form>
                @include('components.button', ['label' => 'Edit', 'type' => 'secondary', 'href' => route('sales.edit', $order)])
                <form method="POST" action="{{ route('sales.destroy', $order) }}" class="inline"
                      onsubmit="return confirm('Delete this sales order?')">
                    @csrf @method('DELETE')
                    @include('components.button', ['label' => 'Delete', 'type' => 'danger', 'buttonType' => 'submit'])
                </form>
            @endif

            @if(in_array($order->status, ['confirmed', 'processing']))
                <form method="POST" action="{{ route('sales.deliver', $order) }}" class="inline"
                      onsubmit="return confirm('Mark this order as shipped/delivered?')">
                    @csrf
                    @include('components.button', [
                        'label' => 'Mark Delivered',
                        'type' => 'primary',
                        'buttonType' => 'submit',
                        'icon' => '<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8" /></svg>',
                    ])
                </form>
            @endif

            @if(in_array($order->status, ['confirmed', 'shipped']))
                <form method="POST" action="{{ route('sales.invoice', $order) }}" class="inline"
                      onsubmit="return confirm('Invoice this order and mark as completed?')">
                    @csrf
                    @include('components.button', [
                        'label' => 'Invoice & Complete',
                        'type' => 'primary',
                        'buttonType' => 'submit',
                        'icon' => '<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>',
                    ])
                </form>
            @endif

            @if(!in_array($order->status, ['completed', 'cancelled']))
                <form method="POST" action="{{ route('sales.cancel', $order) }}" class="inline"
                      onsubmit="return confirm('Cancel this order?{{ in_array($order->status, ['confirmed','processing','shipped']) ? ' Stock will be restored.' : '' }}')">
                    @csrf
                    @include('components.button', ['label' => 'Cancel Order', 'type' => 'danger', 'buttonType' => 'submit'])
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
                        <dt class="text-gray-500">Client</dt>
                        <dd class="font-medium text-gray-900">{{ $order->client->name ?? '—' }}</dd>
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
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Order Date</dt>
                        <dd class="font-medium text-gray-900">{{ $order->order_date->format('M d, Y') }}</dd>
                    </div>
                    @if($order->delivery_date)
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Delivery Date</dt>
                            <dd class="font-medium text-gray-900">{{ $order->delivery_date->format('M d, Y') }}</dd>
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
                        <dd class="font-medium text-gray-900">{{ number_format($order->subtotal, 2) }}</dd>
                    </div>
                    @if($order->tax_amount > 0)
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Tax</dt>
                            <dd class="font-medium text-gray-900">+ {{ number_format($order->tax_amount, 2) }}</dd>
                        </div>
                    @endif
                    @if($order->discount > 0)
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Discount</dt>
                            <dd class="font-medium text-red-600">- {{ number_format($order->discount, 2) }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between border-t border-gray-100 pt-3">
                        <dt class="font-semibold text-gray-900">Grand Total</dt>
                        <dd class="text-lg font-bold text-gray-900">{{ number_format($order->total, 2) }}</dd>
                    </div>
                </dl>
            </div>

            @if($order->notes)
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <h3 class="text-sm font-semibold text-gray-900 mb-2">Notes</h3>
                    <p class="text-sm text-gray-600 whitespace-pre-line">{{ $order->notes }}</p>
                </div>
            @endif
        </div>

        {{-- Right: Items --}}
        <div class="lg:col-span-2">

            @if($order->status === 'confirmed')
                <div class="mb-6 rounded-2xl bg-blue-50 p-4 shadow-sm ring-1 ring-blue-100">
                    <div class="flex items-center gap-3">
                        <svg class="h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        <p class="text-sm font-medium text-blue-800">Order confirmed — stock has been deducted. Ready to deliver.</p>
                    </div>
                </div>
            @elseif($order->status === 'shipped')
                <div class="mb-6 rounded-2xl bg-sky-50 p-4 shadow-sm ring-1 ring-sky-100">
                    <div class="flex items-center gap-3">
                        <svg class="h-5 w-5 text-sky-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8" />
                        </svg>
                        <p class="text-sm font-medium text-sky-800">Order shipped / delivered — ready to be invoiced.</p>
                    </div>
                </div>
            @elseif($order->status === 'completed')
                <div class="mb-6 rounded-2xl bg-green-50 p-4 shadow-sm ring-1 ring-green-100">
                    <div class="flex items-center gap-3">
                        <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-sm font-medium text-green-800">Order completed — invoiced and fulfilled.</p>
                    </div>
                </div>
            @elseif($order->status === 'cancelled')
                <div class="mb-6 rounded-2xl bg-red-50 p-4 shadow-sm ring-1 ring-red-100">
                    <div class="flex items-center gap-3">
                        <svg class="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        <p class="text-sm font-medium text-red-800">This order has been cancelled. Stock has been restored.</p>
                    </div>
                </div>
            @endif

            <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-900">Order Items</h3>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $order->items->count() }} item(s)</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">#</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Product</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Qty</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Unit Price</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Discount</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Total</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Stock</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($order->items as $i => $item)
                                @php
                                    $stock = $item->product->inventoryStocks->sum('quantity');
                                @endphp
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
                                    <td class="px-6 py-3 text-right text-sm text-gray-700">{{ number_format($item->unit_price, 2) }}</td>
                                    <td class="px-6 py-3 text-right text-sm text-gray-500">
                                        {{ $item->discount > 0 ? number_format($item->discount, 2) : '—' }}
                                    </td>
                                    <td class="px-6 py-3 text-right text-sm font-semibold text-gray-900">{{ number_format($item->total, 2) }}</td>
                                    <td class="px-6 py-3 text-right">
                                        <span class="text-xs font-medium {{ $stock < $item->quantity ? 'text-red-600' : 'text-green-600' }}">
                                            {{ number_format($stock, 0) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
