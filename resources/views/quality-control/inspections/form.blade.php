@extends('layouts.app')

@section('title', $inspection->exists ? __('messages.edit_inspection_title') : __('messages.create_inspection_title'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">{{ __('messages.dashboard') }}</a>
    <span class="mx-1">/</span>
    <a href="{{ route('qc.inspections.index') }}" class="hover:text-gray-700">{{ __('messages.qc_inspections_title') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ $inspection->exists ? __('messages.edit_btn') : __('messages.create_btn') }}</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-gray-900">
            {{ $inspection->exists ? __('messages.edit_inspection_title') : __('messages.create_inspection_title') }}
        </h1>
        <p class="mt-1 text-sm text-gray-500">
            {{ $inspection->exists ? __('messages.edit_inspection_subtitle') : __('messages.create_inspection_subtitle') }}
        </p>
    </div>
@endsection

@section('content')
    <form action="{{ $inspection->exists ? route('qc.inspections.update', $inspection) : route('qc.inspections.store') }}"
          method="POST" class="space-y-6" x-data="inspectionForm()">
        @csrf
        @if($inspection->exists) @method('PUT') @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left: Inspection Details --}}
            <div class="lg:col-span-2 space-y-6">
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100 space-y-5">
                    <h3 class="text-base font-semibold text-gray-900">{{ __('messages.inspection_details_section') }}</h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Inspection Type --}}
                        <div>
                            <label for="inspection_type" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.inspection_type_label') }} <span class="text-red-500">*</span></label>
                            <select id="inspection_type" name="inspection_type" required
                                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                                @foreach(\App\Models\QcInspection::inspectionTypeOptions() as $t)
                                    <option value="{{ $t }}" @selected(old('inspection_type', $inspection->inspection_type) === $t)>{{ __('messages.qc_type_' . $t) }}</option>
                                @endforeach
                            </select>
                            @error('inspection_type') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        {{-- Product --}}
                        <div>
                            <label for="product_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.product_label') }} <span class="text-red-500">*</span></label>
                            <select id="product_id" name="product_id" required
                                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                                <option value="">{{ __('messages.select_product') }}</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" @selected(old('product_id', $inspection->product_id) == $product->id)>{{ $product->name }} ({{ $product->sku }})</option>
                                @endforeach
                            </select>
                            @error('product_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        {{-- Warehouse --}}
                        <div>
                            <label for="warehouse_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.warehouse_label') }}</label>
                            <select id="warehouse_id" name="warehouse_id"
                                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                                <option value="">—</option>
                                @foreach($warehouses as $wh)
                                    <option value="{{ $wh->id }}" @selected(old('warehouse_id', $inspection->warehouse_id) == $wh->id)>{{ $wh->name }}</option>
                                @endforeach
                            </select>
                            @error('warehouse_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        {{-- Inspected Quantity --}}
                        <div>
                            <label for="inspected_quantity" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.inspected_qty_label') }} <span class="text-red-500">*</span></label>
                            <input type="number" id="inspected_quantity" name="inspected_quantity" step="any" min="0.01" required
                                value="{{ old('inspected_quantity', $inspection->inspected_quantity) }}"
                                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500" />
                            @error('inspected_quantity') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Notes --}}
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.notes_label') }}</label>
                        <textarea id="notes" name="notes" rows="3"
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">{{ old('notes', $inspection->notes) }}</textarea>
                    </div>
                </div>

                {{-- QC Check Parameters --}}
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-semibold text-gray-900">{{ __('messages.qc_check_parameters_section') }}</h3>
                        <button type="button" @click="addItem()"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-primary-50 px-3 py-1.5 text-xs font-semibold text-primary-700 hover:bg-primary-100 transition-colors">
                            <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                            {{ __('messages.add_parameter_btn') }}
                        </button>
                    </div>

                    <div class="space-y-3">
                        <template x-for="(item, index) in items" :key="index">
                            <div class="flex items-start gap-3 p-4 rounded-xl bg-gray-50 ring-1 ring-gray-100">
                                <div class="flex-1 grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    {{-- Parameter --}}
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('messages.parameter_label') }}</label>
                                        <select :name="'items[' + index + '][qc_parameter_id]'" x-model="item.qc_parameter_id" required
                                            @change="onParameterChange(index)"
                                            class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                                            <option value="">{{ __('messages.select_parameter') }}</option>
                                            @foreach($parameters as $param)
                                                <option value="{{ $param->id }}" data-type="{{ $param->type }}" data-min="{{ $param->min_value }}" data-max="{{ $param->max_value }}" data-unit="{{ $param->unit }}">{{ $param->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Min --}}
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('messages.min_value_label') }}</label>
                                        <input type="number" :name="'items[' + index + '][min_value]'" x-model="item.min_value" step="any"
                                            class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500" />
                                    </div>

                                    {{-- Max --}}
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('messages.max_value_label') }}</label>
                                        <input type="number" :name="'items[' + index + '][max_value]'" x-model="item.max_value" step="any"
                                            class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500" />
                                    </div>
                                </div>
                                <button type="button" @click="removeItem(index)"
                                    class="mt-5 shrink-0 rounded-lg p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-500 transition-colors">
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </div>
                        </template>

                        <div x-show="items.length === 0" class="text-center py-8">
                            <p class="text-sm text-gray-400">{{ __('messages.no_parameters_added') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right: Summary --}}
            <div class="lg:col-span-1 space-y-6">
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <h3 class="text-base font-semibold text-gray-900 mb-3">{{ __('messages.qc_summary_section') }}</h3>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-500">{{ __('messages.parameters_count') }}</dt>
                            <dd class="font-medium text-gray-900" x-text="items.length">0</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        {{-- Buttons --}}
        <div class="flex items-center justify-end gap-3">
            @include('components.button', [
                'label' => __('messages.cancel_btn'),
                'type' => 'secondary',
                'href' => route('qc.inspections.index'),
            ])
            @include('components.button', [
                'label' => $inspection->exists ? __('messages.update_inspection_btn') : __('messages.create_inspection_btn'),
                'type' => 'primary',
                'buttonType' => 'submit',
            ])
        </div>
    </form>

    @php
        $existingItems = $inspection->exists
            ? $inspection->items->map(fn($i) => [
                'qc_parameter_id' => $i->qc_parameter_id,
                'min_value'       => $i->min_value,
                'max_value'       => $i->max_value,
              ])->values()->toArray()
            : [];
        $initialItems = old('items', $existingItems);
    @endphp

    <script>
        function inspectionForm() {
            return {
                items: @json($initialItems),
                addItem() {
                    this.items.push({ qc_parameter_id: '', min_value: '', max_value: '' });
                },
                removeItem(index) {
                    this.items.splice(index, 1);
                },
                onParameterChange(index) {
                    const select = document.querySelector(`[name="items[${index}][qc_parameter_id]"]`);
                    if (!select) return;
                    const option = select.selectedOptions[0];
                    if (option) {
                        this.items[index].min_value = option.dataset.min || '';
                        this.items[index].max_value = option.dataset.max || '';
                    }
                }
            };
        }
    </script>
@endsection
