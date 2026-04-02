@extends('layouts.app')

@section('title', __('messages.convert_pr_to_po_title'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">{{ __('messages.dashboard') }}</a>
    <span class="mx-1">/</span>
    <a href="{{ route('purchase-requests.index') }}" class="hover:text-gray-700">{{ __('messages.purchase_requests_title') }}</a>
    <span class="mx-1">/</span>
    <a href="{{ route('purchase-requests.show', $purchaseRequest) }}" class="hover:text-gray-700">{{ $purchaseRequest->number }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('messages.convert_to_po_btn') }}</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.convert_pr_to_po_title') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('messages.convert_pr_to_po_subtitle', ['number' => $purchaseRequest->number]) }}</p>
    </div>
@endsection

@section('content')
    <form method="POST" action="{{ route('purchase-requests.store-conversion', $purchaseRequest) }}" class="space-y-6">
        @csrf

        {{-- PO Settings --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-4">{{ __('messages.po_settings_section') }}</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="supplier_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.supplier_label') }} <span class="text-red-500">*</span></label>
                    <select id="supplier_id" name="supplier_id" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 transition @error('supplier_id') border-red-300 @enderror">
                        <option value="">{{ __('messages.select_supplier') }}</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                    @error('supplier_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="warehouse_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.warehouse_label') }} <span class="text-red-500">*</span></label>
                    <select id="warehouse_id" name="warehouse_id" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 transition @error('warehouse_id') border-red-300 @enderror">
                        <option value="">{{ __('messages.select_warehouse') }}</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" @selected(old('warehouse_id') == $warehouse->id)>{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                    @error('warehouse_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="expected_date" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.expected_date_label') }}</label>
                    <input type="date" id="expected_date" name="expected_date"
                        value="{{ old('expected_date', $purchaseRequest->required_date?->format('Y-m-d')) }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition">
                </div>
            </div>
        </div>

        {{-- Items Preview --}}
        <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-900">{{ __('messages.items_to_convert') }}</h3>
                <p class="text-xs text-gray-500 mt-0.5">{{ __('messages.items_to_convert_subtitle') }}</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50/60">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">#</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">{{ __('messages.product_label') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">{{ __('messages.quantity_header') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">{{ __('messages.unit_price_label') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">{{ __('messages.total_header') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($purchaseRequest->items as $item)
                            <tr>
                                <td class="px-6 py-3 text-sm text-gray-400">{{ $loop->iteration }}</td>
                                <td class="px-6 py-3 text-sm">
                                    <p class="font-medium text-gray-900">{{ $item->product->name ?? '—' }}</p>
                                    <p class="text-xs text-gray-400">{{ $item->product->sku ?? '' }}</p>
                                </td>
                                <td class="px-6 py-3 text-sm text-right text-gray-600">{{ number_format($item->quantity, 2) }}</td>
                                <td class="px-6 py-3 text-sm text-right text-gray-600">{{ format_currency($item->estimated_price) }}</td>
                                <td class="px-6 py-3 text-sm text-right font-semibold text-gray-900">{{ format_currency($item->total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50/60">
                        <tr>
                            <td colspan="4" class="px-6 py-3 text-right text-sm font-semibold text-gray-900">{{ __('messages.total_header') }}</td>
                            <td class="px-6 py-3 text-right text-sm font-bold text-gray-900">{{ format_currency($purchaseRequest->getEstimatedTotal()) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex justify-end gap-3">
            <a href="{{ route('purchase-requests.show', $purchaseRequest) }}"
                class="inline-flex items-center gap-2 rounded-xl bg-gray-100 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-200 transition">
                {{ __('messages.cancel_btn') }}
            </a>
            <button type="submit"
                class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 transition">
                {{ __('messages.create_po_from_pr_btn') }}
            </button>
        </div>
    </form>
@endsection
