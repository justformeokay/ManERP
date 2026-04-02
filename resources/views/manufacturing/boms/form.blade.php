@extends('layouts.app')

@php
    $isEdit = $bom->exists;
    $pageTitle = $isEdit ? __('messages.edit_bom_title') : __('messages.new_bom_title');
    $pageSubtitle = $isEdit ? __('messages.update_bom_subtitle') : __('messages.create_bom_subtitle');
@endphp

@section('title', $pageTitle)

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">{{ __('messages.dashboard') }}</a>
    <span class="mx-1">/</span>
    <a href="{{ route('manufacturing.boms.index') }}" class="hover:text-gray-700">{{ __('messages.bill_of_materials_title') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ $pageTitle }}</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $pageTitle }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ $pageSubtitle }}</p>
    </div>
@endsection

@section('content')
    @php
        $initialItems = [];
        if ($isEdit && $bom->items) {
            foreach ($bom->items as $item) {
                $initialItems[] = [
                    'product_id' => $item->product_id,
                    'sub_bom_id' => $item->sub_bom_id,
                    'quantity' => $item->quantity,
                    'notes' => $item->notes
                ];
            }
        }
    @endphp

    <form method="POST"
          action="{{ $isEdit ? route('manufacturing.boms.update', $bom) : route('manufacturing.boms.store') }}"
          class="space-y-6"
          x-data="bomForm()">
        @csrf
        @if($isEdit) @method('PUT') @endif

        {{-- BOM Details --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-4">{{ __('messages.bom_details_section') }}</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.bom_name_label') }} <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name', $bom->name) }}" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition @error('name') border-red-300 @enderror"
                        placeholder="{{ __('messages.bom_name_placeholder') }}">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="product_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.output_product_label') }} <span class="text-red-500">*</span></label>
                    <select id="product_id" name="product_id" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 transition @error('product_id') border-red-300 @enderror">
                        <option value="">{{ __('messages.select_finished_product') }}</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" @selected(old('product_id', $bom->product_id) == $p->id)>
                                {{ $p->name }} ({{ $p->sku }})
                            </option>
                        @endforeach
                    </select>
                    @error('product_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="output_quantity" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.output_quantity_label') }} <span class="text-red-500">*</span></label>
                    <input type="number" id="output_quantity" name="output_quantity" step="0.01" min="0.01" required
                        value="{{ old('output_quantity', $bom->output_quantity) }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition"
                        placeholder="1">
                </div>

                <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.bom_description_label') }}</label>
                    <textarea id="description" name="description" rows="2"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition"
                        placeholder="{{ __('messages.bom_description_placeholder') }}">{{ old('description', $bom->description) }}</textarea>
                </div>

                <div>
                    <input type="hidden" name="is_active" value="0">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1"
                            class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                            @checked(old('is_active', $bom->is_active))>
                        <span class="text-sm font-medium text-gray-700">{{ __('messages.bom_active_checkbox') }}</span>
                    </label>
                </div>
            </div>
        </div>

        {{-- Materials / Components --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900">{{ __('messages.materials_components_section') }}</h3>
                <button type="button" @click="addItem()"
                    class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-medium text-primary-700 bg-primary-50 hover:bg-primary-100 transition">
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    {{ __('messages.add_material_btn') }}
                </button>
            </div>

            @error('items') <p class="mb-3 text-xs text-red-600">{{ $message }}</p> @enderror

            <div class="space-y-3">
                <template x-for="(item, index) in items" :key="index">
                    <div class="flex flex-col sm:flex-row gap-3 items-start rounded-xl border border-gray-100 bg-gray-50/50 p-4">
                        <div class="flex-1 min-w-0">
                            <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('messages.material_label') }} <span class="text-red-500">*</span></label>
                            <select :name="`items[${index}][product_id]`" x-model="item.product_id" required
                                class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 transition">
                                <option value="">{{ __('messages.select_material_placeholder') }}</option>
                                @foreach($products as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->sku }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="w-32">
                            <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('messages.bom_qty_label') }} <span class="text-red-500">*</span></label>
                            <input type="number" :name="`items[${index}][quantity]`" x-model="item.quantity" step="0.0001" min="0.0001" required
                                class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 transition"
                                placeholder="0">
                        </div>
                        <div class="w-44">
                            <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('messages.sub_bom_label') }}</label>
                            <select :name="`items[${index}][sub_bom_id]`" x-model="item.sub_bom_id"
                                class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 transition">
                                <option value="">{{ __('messages.none') }}</option>
                                @foreach($bomList ?? [] as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex-1 min-w-0">
                            <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('messages.material_notes_label') }}</label>
                            <input type="text" :name="`items[${index}][notes]`" x-model="item.notes"
                                class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 transition"
                                placeholder="{{ __('messages.notes_placeholder') }}">
                        </div>
                        <div class="pt-5">
                            <button type="button" @click="removeItem(index)"
                                class="rounded-lg p-2 text-red-500 hover:bg-red-50 transition" title="Remove">
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <div x-show="items.length === 0" class="text-center py-8 text-sm text-gray-400">
                {{ __('messages.no_materials_added') }}
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3">
            @include('components.button', ['label' => 'Cancel', 'type' => 'secondary', 'href' => route('manufacturing.boms.index')])
            @include('components.button', [
                'label' => $isEdit ? 'Update BOM' : 'Create BOM',
                'type' => 'primary',
                'buttonType' => 'submit',
                'icon' => '<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>',
            ])
        </div>
    </form>
@endsection

@push('scripts')
<script>
    function bomForm() {
        return {
            items: @json(old('items', $initialItems)),
            addItem() {
                this.items.push({ product_id: '', sub_bom_id: '', quantity: '', notes: '' });
            },
            removeItem(index) {
                this.items.splice(index, 1);
            }
        }
    }
</script>
@endpush
