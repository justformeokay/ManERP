@extends('layouts.app')

@php
    $isEdit = $pr->exists;
    $pageTitle = $isEdit ? __('messages.edit_pr_title') : __('messages.create_pr_title');
@endphp

@section('title', $pageTitle)

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">{{ __('messages.dashboard') }}</a>
    <span class="mx-1">/</span>
    <a href="{{ route('purchase-requests.index') }}" class="hover:text-gray-700">{{ __('messages.purchase_requests_title') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ $pageTitle }}</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $pageTitle }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ $isEdit ? __('messages.edit_pr_subtitle') : __('messages.create_pr_subtitle') }}</p>
    </div>
@endsection

@section('content')
    @php
        $initialItems = [];
        if ($isEdit && $pr->items) {
            foreach ($pr->items as $item) {
                $initialItems[] = [
                    'product_id'      => $item->product_id,
                    'quantity'        => $item->quantity,
                    'estimated_price' => $item->estimated_price,
                    'specification'   => $item->specification,
                    'notes'           => $item->notes,
                ];
            }
        }
    @endphp

    <form method="POST"
          action="{{ $isEdit ? route('purchase-requests.update', $pr) : route('purchase-requests.store') }}"
          class="space-y-6"
          x-data="prForm()">
        @csrf
        @if($isEdit) @method('PUT') @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left: Request Details --}}
            <div class="lg:col-span-2 space-y-6">
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100 space-y-5">
                    <h3 class="text-base font-semibold text-gray-900">{{ __('messages.pr_details_section') }}</h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="priority" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.priority_header') }} <span class="text-red-500">*</span></label>
                            <select id="priority" name="priority" required
                                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                                @foreach(['low', 'normal', 'high', 'urgent'] as $p)
                                    <option value="{{ $p }}" @selected(old('priority', $pr->priority) === $p)>{{ __('messages.priority_' . $p) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="required_date" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.required_date_label') }}</label>
                            <input type="date" id="required_date" name="required_date"
                                value="{{ old('required_date', $pr->required_date?->format('Y-m-d')) }}"
                                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                        </div>

                        <div>
                            <label for="project_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.project_label') }}</label>
                            <select id="project_id" name="project_id"
                                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                                <option value="">—</option>
                                @foreach($projects as $proj)
                                    <option value="{{ $proj->id }}" @selected(old('project_id', $pr->project_id) == $proj->id)>{{ $proj->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="reason" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.pr_reason_label') }}</label>
                        <textarea id="reason" name="reason" rows="3"
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500"
                            placeholder="{{ __('messages.pr_reason_placeholder') }}">{{ old('reason', $pr->reason) }}</textarea>
                    </div>
                </div>

                {{-- Items --}}
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-semibold text-gray-900">{{ __('messages.pr_items_section') }}</h3>
                        <button type="button" @click="addItem()"
                            class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-medium text-primary-700 bg-primary-50 hover:bg-primary-100 transition">
                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                            {{ __('messages.add_item_btn') }}
                        </button>
                    </div>

                    @error('items') <p class="mb-3 text-xs text-red-600">{{ $message }}</p> @enderror

                    <div class="space-y-3">
                        <template x-for="(item, index) in items" :key="index">
                            <div class="rounded-xl border border-gray-100 bg-gray-50/50 p-4">
                                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                                    <div class="sm:col-span-4">
                                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('messages.product_label') }} *</label>
                                        <select :name="`items[${index}][product_id]`" x-model="item.product_id" required
                                            class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                                            <option value="">{{ __('messages.select_product') }}</option>
                                            @foreach($products as $p)
                                                <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->sku }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('messages.quantity_header') }} *</label>
                                        <input type="number" :name="`items[${index}][quantity]`" x-model="item.quantity" step="0.01" min="0.01" required
                                            class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('messages.est_price_label') }}</label>
                                        <input type="number" :name="`items[${index}][estimated_price]`" x-model="item.estimated_price" step="0.01" min="0"
                                            class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                                    </div>
                                    <div class="sm:col-span-3">
                                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('messages.specification_label') }}</label>
                                        <input type="text" :name="`items[${index}][specification]`" x-model="item.specification"
                                            class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                                    </div>
                                    <div class="sm:col-span-1 flex items-end">
                                        <button type="button" @click="removeItem(index)"
                                            class="rounded-lg p-2 text-red-500 hover:bg-red-50 transition">
                                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div x-show="items.length === 0" class="text-center py-8 text-sm text-gray-400">
                        {{ __('messages.no_items_added') }}
                    </div>
                </div>
            </div>

            {{-- Right: Summary --}}
            <div class="lg:col-span-1">
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <h3 class="text-base font-semibold text-gray-900 mb-3">{{ __('messages.pr_summary') }}</h3>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-500">{{ __('messages.total_items') }}</dt>
                            <dd class="font-medium text-gray-900" x-text="items.length">0</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">{{ __('messages.estimated_total_header') }}</dt>
                            <dd class="font-medium text-gray-900" x-text="ManERP.formatCurrency(items.reduce((sum, i) => sum + ((parseFloat(i.quantity) || 0) * (parseFloat(i.estimated_price) || 0)), 0))">{{ currency_symbol() }} 0</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        {{-- Buttons --}}
        <div class="flex items-center justify-end gap-3">
            @include('components.button', ['label' => __('messages.cancel_btn'), 'type' => 'secondary', 'href' => route('purchase-requests.index')])
            @include('components.button', [
                'label' => $isEdit ? __('messages.update_pr_btn') : __('messages.create_pr_btn'),
                'type' => 'primary',
                'buttonType' => 'submit',
            ])
        </div>
    </form>

    @push('scripts')
    <script>
        function prForm() {
            return {
                items: @json(old('items', $initialItems)),
                addItem() {
                    this.items.push({ product_id: '', quantity: '', estimated_price: '', specification: '', notes: '' });
                },
                removeItem(index) {
                    this.items.splice(index, 1);
                }
            }
        }
    </script>
    @endpush
@endsection
