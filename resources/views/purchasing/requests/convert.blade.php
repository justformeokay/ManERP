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
    <form method="POST" action="{{ route('purchase-requests.store-conversion', $purchaseRequest) }}" class="space-y-6"
          x-data="convertForm()">
        @csrf
        {{-- HMAC integrity signature (F-14) --}}
        <input type="hidden" name="conversion_sig" value="{{ $conversionSig }}">

        {{-- PR Summary Banner --}}
        <div class="rounded-2xl bg-primary-50 p-5 shadow-sm ring-1 ring-primary-100">
            <div class="flex items-start gap-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-100 text-primary-600">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <div class="flex-1">
                    <h4 class="text-sm font-semibold text-primary-900">{{ __('messages.convert_pr_source_label') }}: {{ $purchaseRequest->number }}</h4>
                    <div class="mt-1 grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs text-primary-700">
                        <div>{{ __('messages.priority_header') }}: <span class="font-medium">{{ __('messages.priority_' . $purchaseRequest->priority) }}</span></div>
                        <div>{{ __('messages.pr_purchase_type_label') }}: <span class="font-medium">{{ __('messages.po_purchase_type_' . ($purchaseRequest->purchase_type ?? 'operational')) }}</span></div>
                        @if($purchaseRequest->department)
                            <div>{{ __('messages.pr_department_label') }}: <span class="font-medium">{{ $purchaseRequest->department->name }}</span></div>
                        @endif
                        <div>{{ __('messages.estimated_total_header') }}: <span class="font-semibold">{{ format_currency($purchaseRequest->getEstimatedTotal()) }}</span></div>
                    </div>
                </div>
            </div>
        </div>

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
                    <label for="department_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.pr_department_label') }} <span class="text-red-500">*</span></label>
                    <select id="department_id" name="department_id" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 transition @error('department_id') border-red-300 @enderror">
                        <option value="">{{ __('messages.pr_select_department') }}</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" @selected(old('department_id', $purchaseRequest->department_id) == $dept->id)>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                    @error('department_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="payment_terms" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.convert_payment_terms') }} <span class="text-red-500">*</span></label>
                    <select id="payment_terms" name="payment_terms" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 transition @error('payment_terms') border-red-300 @enderror">
                        @foreach(\App\Models\PurchaseOrder::paymentTermsOptions() as $pt)
                            <option value="{{ $pt }}" @selected(old('payment_terms', 'net_30') === $pt)>{{ __('messages.payment_term_' . $pt) }}</option>
                        @endforeach
                    </select>
                    @error('payment_terms') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="expected_date" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.expected_date_label') }}</label>
                    <input type="date" id="expected_date" name="expected_date"
                        value="{{ old('expected_date', $purchaseRequest->required_date?->format('Y-m-d')) }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition">
                </div>
                <div>
                    <label for="shipping_address" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.convert_shipping_address') }}</label>
                    <textarea id="shipping_address" name="shipping_address" rows="2"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition"
                        placeholder="{{ __('messages.convert_shipping_placeholder') }}">{{ old('shipping_address') }}</textarea>
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

        {{-- Budget Override Warning --}}
        @if($purchaseRequest->getEstimatedTotal() > 0)
            <div x-show="showBudgetWarning" x-cloak
                class="rounded-2xl bg-amber-50 p-5 shadow-sm ring-1 ring-amber-200">
                <div class="flex items-start gap-3">
                    <svg class="h-5 w-5 text-amber-500 mt-0.5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                    <div>
                        <h4 class="text-sm font-semibold text-amber-800">{{ __('messages.convert_budget_warning_title') }}</h4>
                        <p class="text-xs text-amber-700 mt-1">{{ __('messages.convert_budget_warning_desc', ['amount' => format_currency($purchaseRequest->getEstimatedTotal())]) }}</p>
                        <label class="mt-3 inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="budget_override" value="1" x-model="budgetOverride"
                                class="rounded border-amber-300 text-amber-600 focus:ring-amber-500">
                            <span class="text-xs font-medium text-amber-800">{{ __('messages.convert_budget_override_confirm') }}</span>
                        </label>
                    </div>
                </div>
            </div>
        @endif

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

    @push('scripts')
    <script>
        function convertForm() {
            return {
                budgetOverride: false,
                showBudgetWarning: true,
            }
        }
    </script>
    @endpush
@endsection
