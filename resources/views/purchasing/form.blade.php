@extends('layouts.app')

@php
    $isEdit = $order->exists;
    $pageTitle = $isEdit ? __('messages.po_edit_title', ['number' => $order->number]) : __('messages.po_new_title');
@endphp

@section('title', $pageTitle)

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">{{ __('messages.dashboard') }}</a>
    <span class="mx-1">/</span>
    <a href="{{ route('purchasing.index') }}" class="hover:text-gray-700">{{ __('messages.purchase_orders') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ $pageTitle }}</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $pageTitle }}</h1>
        <p class="mt-1 text-sm text-gray-500">
            {{ $isEdit ? __('messages.po_edit_subtitle') : __('messages.po_create_subtitle') }}
        </p>
    </div>
@endsection

@section('content')
    @php
        $initialItems = [];
        if ($isEdit && $order->items) {
            foreach ($order->items as $item) {
                $initialItems[] = [
                    'product_id' => $item->product_id,
                    'quantity'   => $item->quantity,
                    'unit_price' => $item->unit_price,
                ];
            }
        }

        $productData = $products->map(function ($p) {
            return [
                'id'         => $p->id,
                'name'       => $p->name,
                'sku'        => $p->sku,
                'cost_price' => (float) $p->cost_price,
                'unit'       => $p->unit,
            ];
        })->values()->toArray();

        // Build HMAC-signed project lists
        $salesProjectData = $salesProjects->map(fn($p) => [
            'id' => $p->id, 'name' => $p->name, 'code' => $p->code,
            'sig' => \App\Models\PurchaseOrder::projectHmac($p->id),
        ])->values()->toArray();

        $capexProjectData = $capexProjects->map(fn($p) => [
            'id' => $p->id, 'name' => $p->name, 'code' => $p->code,
            'sig' => \App\Models\PurchaseOrder::projectHmac($p->id),
        ])->values()->toArray();
    @endphp

    <form method="POST"
          action="{{ $isEdit ? route('purchasing.update', $order) : route('purchasing.store') }}"
          class="space-y-6"
          x-data="poForm()">
        @csrf
        @if($isEdit) @method('PUT') @endif

        {{-- Purchase Type Selector --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-1">{{ __('messages.po_purchase_type_label') }} <span class="text-red-500">*</span></h3>
            <p class="text-sm text-gray-500 mb-4">{{ __('messages.po_purchase_type_hint') }}</p>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                {{-- Operational --}}
                <label class="relative cursor-pointer rounded-xl border-2 p-4 transition-all"
                       :class="purchaseType === 'operational' ? 'border-gray-600 bg-gray-50 ring-1 ring-gray-300' : 'border-gray-200 bg-gray-50/50 hover:border-gray-300'">
                    <input type="radio" name="purchase_type" value="operational" class="sr-only" x-model="purchaseType">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
                             :class="purchaseType === 'operational' ? 'bg-gray-200 text-gray-700' : 'bg-gray-100 text-gray-400'">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold" :class="purchaseType === 'operational' ? 'text-gray-900' : 'text-gray-600'">{{ __('messages.po_purchase_type_operational') }}</p>
                            <p class="text-xs" :class="purchaseType === 'operational' ? 'text-gray-600' : 'text-gray-400'">{{ __('messages.po_type_operational_desc') }}</p>
                        </div>
                    </div>
                    <div x-show="purchaseType === 'operational'" class="absolute top-3 right-3">
                        <svg class="h-5 w-5 text-gray-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                    </div>
                </label>

                {{-- Project Sales --}}
                <label class="relative cursor-pointer rounded-xl border-2 p-4 transition-all"
                       :class="purchaseType === 'project_sales' ? 'border-blue-500 bg-blue-50/50 ring-1 ring-blue-200' : 'border-gray-200 bg-gray-50/50 hover:border-gray-300'">
                    <input type="radio" name="purchase_type" value="project_sales" class="sr-only" x-model="purchaseType">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
                             :class="purchaseType === 'project_sales' ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-400'">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold" :class="purchaseType === 'project_sales' ? 'text-blue-900' : 'text-gray-600'">{{ __('messages.po_purchase_type_project_sales') }}</p>
                            <p class="text-xs" :class="purchaseType === 'project_sales' ? 'text-blue-600' : 'text-gray-400'">{{ __('messages.po_type_sales_desc') }}</p>
                        </div>
                    </div>
                    <div x-show="purchaseType === 'project_sales'" class="absolute top-3 right-3">
                        <svg class="h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                    </div>
                </label>

                {{-- CAPEX / Investment --}}
                <label class="relative cursor-pointer rounded-xl border-2 p-4 transition-all"
                       :class="purchaseType === 'project_capex' ? 'border-violet-500 bg-violet-50/50 ring-1 ring-violet-200' : 'border-gray-200 bg-gray-50/50 hover:border-gray-300'">
                    <input type="radio" name="purchase_type" value="project_capex" class="sr-only" x-model="purchaseType">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
                             :class="purchaseType === 'project_capex' ? 'bg-violet-100 text-violet-600' : 'bg-gray-100 text-gray-400'">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold" :class="purchaseType === 'project_capex' ? 'text-violet-900' : 'text-gray-600'">{{ __('messages.po_purchase_type_project_capex') }}</p>
                            <p class="text-xs" :class="purchaseType === 'project_capex' ? 'text-violet-600' : 'text-gray-400'">{{ __('messages.po_type_capex_desc') }}</p>
                        </div>
                    </div>
                    <div x-show="purchaseType === 'project_capex'" class="absolute top-3 right-3">
                        <svg class="h-5 w-5 text-violet-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                    </div>
                </label>
            </div>
            @error('purchase_type') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror

            {{-- Project Dropdown — shown only for project types --}}
            <div x-show="purchaseType !== 'operational'" x-transition class="mt-4 pt-4 border-t border-gray-100">
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.po_project') }} <span class="text-red-500">*</span></label>
                <select x-model="selectedProjectId" @change="onProjectChange()"
                    :required="purchaseType !== 'operational'"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 transition @error('project_id') border-red-300 @enderror">
                    <option value="">{{ __('messages.po_select_project') }}</option>
                    <template x-if="purchaseType === 'project_sales'">
                        <template x-for="p in salesProjects" :key="p.id">
                            <option :value="p.id" x-text="`${p.code} — ${p.name}`"></option>
                        </template>
                    </template>
                    <template x-if="purchaseType === 'project_capex'">
                        <template x-for="p in capexProjects" :key="p.id">
                            <option :value="p.id" x-text="`${p.code} — ${p.name}`"></option>
                        </template>
                    </template>
                </select>
                <input type="hidden" name="project_id" :value="purchaseType === 'operational' ? '' : selectedProjectId">
                <input type="hidden" name="project_sig" :value="selectedProjectSig">
                @error('project_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Order Details --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-4">{{ __('messages.po_order_details') }}</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">

                <div>
                    <label for="supplier_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.po_supplier') }} <span class="text-red-500">*</span></label>
                    <select name="supplier_id" id="supplier_id" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">
                        <option value="">{{ __('messages.po_select_supplier') }}</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected(old('supplier_id', $order->supplier_id) == $supplier->id)>
                                {{ $supplier->name }} {{ $supplier->company ? '— ' . $supplier->company : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('supplier_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="warehouse_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.po_warehouse') }} <span class="text-red-500">*</span></label>
                    <select name="warehouse_id" id="warehouse_id" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">
                        <option value="">{{ __('messages.po_select_warehouse') }}</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" @selected(old('warehouse_id', $order->warehouse_id) == $wh->id)>
                                {{ $wh->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('warehouse_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="department_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.po_requesting_dept') }} <span class="text-red-500">*</span></label>
                    <select name="department_id" id="department_id" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">
                        <option value="">{{ __('messages.po_select_department') }}</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" @selected(old('department_id', $order->department_id) == $dept->id)>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('department_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="priority" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.po_priority') }} <span class="text-red-500">*</span></label>
                    <select name="priority" id="priority" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                        @foreach(\App\Models\PurchaseOrder::priorityOptions() as $prio)
                            <option value="{{ $prio }}" @selected(old('priority', $order->priority ?? 'normal') === $prio)>
                                {{ __('messages.po_priority_' . $prio) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="order_date" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.po_order_date') }} <span class="text-red-500">*</span></label>
                    <input type="date" name="order_date" id="order_date" required
                        value="{{ old('order_date', $order->order_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500" />
                    @error('order_date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="expected_date" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.po_expected_delivery') }}</label>
                    <input type="date" name="expected_date" id="expected_date"
                        value="{{ old('expected_date', $order->expected_date?->format('Y-m-d')) }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500" />
                    @error('expected_date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Justification --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-1">{{ __('messages.po_justification') }}</h3>
            <p class="text-sm text-gray-500 mb-3">{{ __('messages.po_justification_hint') }}</p>
            <textarea name="justification" rows="3"
                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500"
                placeholder="{{ __('messages.po_justification_placeholder') }}">{{ old('justification', $order->justification) }}</textarea>
            @error('justification') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Order Items --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900">{{ __('messages.po_order_items') }}</h3>
                <button type="button" @click="addItem()"
                    class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-medium text-primary-700 bg-primary-50 hover:bg-primary-100 transition">
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    {{ __('messages.po_add_item') }}
                </button>
            </div>

            @error('items') <p class="mb-3 text-xs text-red-600">{{ $message }}</p> @enderror

            <div class="space-y-3">
                <template x-for="(item, index) in items" :key="index">
                    <div class="rounded-xl border border-gray-100 bg-gray-50/50 p-4">
                        <div class="flex flex-col sm:flex-row gap-3 items-start">
                            <div class="flex-1 min-w-0">
                                <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('messages.po_product') }} *</label>
                                <select :name="`items[${index}][product_id]`" x-model="item.product_id" required
                                    @change="onProductChange(index)"
                                    class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 transition">
                                    <option value="">{{ __('messages.po_select_product') }}</option>
                                    @foreach($products as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->sku }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="w-28">
                                <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('messages.po_qty') }} *</label>
                                <input type="number" :name="`items[${index}][quantity]`" x-model="item.quantity" step="0.01" min="0.01" required
                                    class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 transition"
                                    placeholder="0">
                            </div>
                            <div class="w-44">
                                <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('messages.po_unit_price') }} ({{ currency_symbol() }}) *</label>
                                <input type="text" x-currency
                                    :data-currency-name="`items[${index}][unit_price]`"
                                    :value="item.unit_price"
                                    @currency-change.window="handleCurrencyChange($event, index)"
                                    class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 transition"
                                    placeholder="0">
                            </div>
                            <div class="pt-5">
                                <button type="button" @click="removeItem(index)"
                                    class="rounded-lg p-2 text-red-500 hover:bg-red-50 transition" :title="'{{ __('messages.po_remove_item') }}'">
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </div>
                        </div>
                        <div class="flex items-center justify-end mt-2 px-1">
                            <span class="text-xs font-medium text-gray-600">
                                {{ __('messages.po_line_total') }}: <span x-text="formatNumber(lineTotal(index))"></span>
                            </span>
                        </div>
                    </div>
                </template>
            </div>

            <div x-show="items.length === 0" class="text-center py-8 text-sm text-gray-400">
                {{ __('messages.po_no_items_yet') }}
            </div>
        </div>

        {{-- Totals & Notes --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-base font-semibold text-gray-900 mb-4">{{ __('messages.notes') }}</h3>
                <textarea name="notes" rows="4"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500"
                    placeholder="{{ __('messages.po_notes_placeholder') }}">{{ old('notes', $order->notes) }}</textarea>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-base font-semibold text-gray-900 mb-4">{{ __('messages.po_summary') }}</h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">{{ __('messages.po_subtotal') }}</dt>
                        <dd class="font-medium text-gray-900" x-text="formatNumber(subtotal())"></dd>
                    </div>
                    <div class="flex justify-between items-center">
                        <dt class="text-gray-500">{{ __('messages.po_tax') }}</dt>
                        <dd>
                            <input type="text" name="tax_amount" x-currency
                                value="{{ old('tax_amount', $order->tax_amount ?? 0) }}"
                                class="w-32 rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-sm text-right text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                                placeholder="0">
                        </dd>
                    </div>
                    <div class="flex justify-between border-t border-gray-100 pt-3">
                        <dt class="font-semibold text-gray-900">{{ __('messages.po_grand_total') }}</dt>
                        <dd class="text-lg font-bold text-gray-900" x-text="formatNumber(grandTotal())"></dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3">
            @include('components.button', ['label' => __('messages.cancel'), 'type' => 'ghost', 'href' => route('purchasing.index')])
            @include('components.button', [
                'label' => $isEdit ? __('messages.po_update_order') : __('messages.po_create_order'),
                'type' => 'primary',
                'buttonType' => 'submit',
                'icon' => '<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>',
            ])
        </div>
    </form>
@endsection

@push('scripts')
<script>
    function poForm() {
        return {
            items: @json(old('items', $initialItems)),
            taxAmount: @json(old('tax_amount', $order->tax_amount ?? 0)),
            productData: @json($productData),
            purchaseType: @json(old('purchase_type', $order->purchase_type ?? 'operational')),
            salesProjects: @json($salesProjectData),
            capexProjects: @json($capexProjectData),
            selectedProjectId: @json(old('project_id', $order->project_id ?? '')),
            selectedProjectSig: '',

            init() {
                // Set initial HMAC sig if editing with a project
                if (this.selectedProjectId) {
                    this.updateProjectSig();
                }
            },

            onProjectChange() {
                this.updateProjectSig();
            },

            updateProjectSig() {
                const projectList = this.purchaseType === 'project_sales' ? this.salesProjects : this.capexProjects;
                const found = projectList.find(p => p.id == this.selectedProjectId);
                this.selectedProjectSig = found ? found.sig : '';
            },

            addItem() {
                this.items.push({ product_id: '', quantity: 1, unit_price: 0 });
            },
            removeItem(index) {
                this.items.splice(index, 1);
            },
            onProductChange(index) {
                const product = this.productData.find(p => p.id == this.items[index].product_id);
                if (product) {
                    this.items[index].unit_price = product.cost_price;
                }
            },
            handleCurrencyChange(event, index) {
                // Listen for currency mask custom events to update item unit_price
                const name = event.detail?.name;
                const value = event.detail?.value;
                if (name && name === `items[${index}][unit_price]` && value !== undefined) {
                    this.items[index].unit_price = parseFloat(value) || 0;
                }
            },
            lineTotal(index) {
                const item = this.items[index];
                return (parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0);
            },
            subtotal() {
                return this.items.reduce((sum, item, i) => sum + this.lineTotal(i), 0);
            },
            grandTotal() {
                return this.subtotal() + (parseFloat(this.taxAmount) || 0);
            },
            formatNumber(val) {
                return ManERP.formatCurrency(val);
            }
        }
    }
</script>
@endpush
