@extends('layouts.app')

@section('title', __('messages.inv_create'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <a href="{{ route('finance.invoices.index') }}" class="hover:text-gray-700">{{ __('messages.invoices') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('messages.inv_create') }}</span>
@endsection

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.inv_create') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.inv_create_desc') }}</p>
        </div>
        @include('components.button', ['label' => '← Back', 'type' => 'ghost', 'href' => route('finance.invoices.index')])
    </div>
@endsection

@section('content')
    <div x-data="invoiceCreate()" class="max-w-4xl">
        <form method="POST" action="{{ route('finance.invoices.store') }}" class="space-y-6">
            @csrf

            {{-- Sales Order Selection --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-base font-semibold text-gray-900 mb-4">{{ __('messages.sales_order') }}</h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label for="sales_order_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.inv_select_so') }} <span class="text-red-500">*</span></label>
                        <select name="sales_order_id" id="sales_order_id"
                            x-model="selectedSO"
                            @change="fetchSOItems()"
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                            required>
                            <option value="">{{ __('messages.inv_choose_so') }}</option>
                            @foreach($salesOrders as $order)
                                <option value="{{ $order->id }}"
                                    @selected(old('sales_order_id', $salesOrder?->id) == $order->id)>
                                    {{ $order->number }} — {{ $order->client->name ?? 'N/A' }} — {{ format_currency($order->total) }}
                                </option>
                            @endforeach
                        </select>
                        @error('sales_order_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Client Info (auto-populated) --}}
                    <template x-if="clientName">
                        <div class="sm:col-span-2 rounded-xl bg-primary-50/50 p-4 ring-1 ring-primary-100">
                            <div class="flex items-center gap-3">
                                <svg class="h-5 w-5 text-primary-600 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                <div>
                                    <p class="text-sm font-semibold text-primary-900" x-text="clientName"></p>
                                    <p class="text-xs text-primary-700" x-text="clientCompany"></p>
                                </div>
                            </div>
                        </div>
                    </template>

                    <div>
                        <label for="invoice_date" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.inv_date') }} <span class="text-red-500">*</span></label>
                        <input type="date" name="invoice_date" id="invoice_date"
                            value="{{ old('invoice_date', now()->format('Y-m-d')) }}"
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500"
                            required>
                    </div>

                    <div>
                        <label for="due_date" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.due_date') }} <span class="text-red-500">*</span></label>
                        <input type="date" name="due_date" id="due_date"
                            value="{{ old('due_date', now()->addDays(30)->format('Y-m-d')) }}"
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500"
                            required>
                        @error('due_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- Line Items (auto-pulled from SO) --}}
            <div x-show="items.length > 0" x-transition class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-900">{{ __('messages.inv_line_items') }}</h3>
                    <span class="text-xs text-gray-500" x-text="'{{ __('messages.inv_items_from_so') }} ' + orderNumber"></span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.product') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.inv_ordered') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.inv_already_invoiced') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.inv_remaining') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 w-32">{{ __('messages.inv_invoice_qty') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.unit_price') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.total') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <template x-for="item in items" :key="item.id">
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-4 py-3">
                                        <p class="text-sm font-medium text-gray-900" x-text="item.product_name"></p>
                                        <p class="text-xs text-gray-500" x-text="item.product_sku"></p>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700" x-text="formatNumber(item.quantity)"></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-400" x-text="formatNumber(item.invoiced_quantity)"></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium" :class="item.remaining > 0 ? 'text-emerald-600' : 'text-gray-400'" x-text="formatNumber(item.remaining)"></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right">
                                        <input type="number"
                                            :name="'items[' + item.id + '][quantity]'"
                                            x-model.number="item.invoiceQty"
                                            @input="recalculate()"
                                            :max="item.remaining"
                                            min="0"
                                            step="0.01"
                                            class="w-24 rounded-lg border border-gray-200 bg-gray-50 px-2 py-1.5 text-right text-sm text-gray-700 focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700" x-text="formatCurrency(item.unit_price)"></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-gray-900" x-text="formatCurrency(item.invoiceQty * item.unit_price)"></td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot class="bg-gray-50/50">
                            <tr>
                                <td colspan="6" class="px-4 py-3 text-right text-sm font-medium text-gray-600">{{ __('messages.subtotal') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-gray-900" x-text="formatCurrency(calcSubtotal)"></td>
                            </tr>
                            <tr x-show="includeTax">
                                <td colspan="5" class="px-4 py-3 text-right text-sm font-medium text-gray-600">{{ __('messages.inv_ppn') }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <input type="number" name="tax_rate" x-model.number="taxRate" @input="recalculate()" min="0" max="100" step="0.5"
                                            class="w-16 rounded-lg border border-gray-200 bg-gray-50 px-2 py-1 text-right text-xs text-gray-700 focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                                        <span class="text-xs text-gray-500">%</span>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-gray-900" x-text="formatCurrency(calcTax)"></td>
                            </tr>
                            <tr class="border-t-2 border-gray-200">
                                <td colspan="6" class="px-4 py-3 text-right text-sm font-bold text-gray-900">{{ __('messages.grand_total') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-base font-bold text-primary-700" x-text="formatCurrency(calcTotal)"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Tax & Notes --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="flex items-center gap-3">
                            <input type="hidden" name="include_tax" value="0">
                            <input type="checkbox" name="include_tax" value="1" x-model="includeTax" @change="recalculate()"
                                class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                checked>
                            <span class="text-sm font-medium text-gray-700">{{ __('messages.inv_include_ppn') }}</span>
                        </label>
                        <p class="mt-1 ml-7 text-xs text-gray-500">{{ __('messages.inv_ppn_desc') }}</p>
                    </div>

                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.notes') }}</label>
                        <textarea name="notes" id="notes" rows="3" placeholder="{{ __('messages.inv_notes_placeholder') }}"
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">{{ old('notes') }}</textarea>
                        @error('notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                @include('components.button', ['label' => __('messages.cancel'), 'type' => 'ghost', 'href' => route('finance.invoices.index')])
                @include('components.button', ['label' => __('messages.inv_generate'), 'type' => 'primary', 'buttonType' => 'submit'])
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
function invoiceCreate() {
    return {
        selectedSO: '{{ old('sales_order_id', $salesOrder?->id ?? '') }}',
        clientName: '',
        clientCompany: '',
        orderNumber: '',
        items: [],
        includeTax: true,
        taxRate: 11,

        init() {
            if (this.selectedSO) this.fetchSOItems();
        },

        async fetchSOItems() {
            if (!this.selectedSO) {
                this.items = [];
                this.clientName = '';
                return;
            }

            try {
                const res = await fetch(`{{ route('finance.invoices.so-items') }}?sales_order_id=${this.selectedSO}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();

                this.clientName = data.client_name;
                this.clientCompany = data.client_company;
                this.orderNumber = data.order_number;
                this.items = data.items.filter(i => i.remaining > 0).map(i => ({
                    ...i,
                    invoiceQty: i.remaining,
                }));
            } catch (e) {
                console.error('Failed to fetch SO items:', e);
            }
        },

        recalculate() {
            // clamp quantities
            this.items.forEach(i => {
                if (i.invoiceQty > i.remaining) i.invoiceQty = i.remaining;
                if (i.invoiceQty < 0) i.invoiceQty = 0;
            });
        },

        get calcSubtotal() {
            return this.items.reduce((sum, i) => sum + (i.invoiceQty * i.unit_price), 0);
        },

        get calcTax() {
            return this.includeTax ? Math.round(this.calcSubtotal * this.taxRate / 100 * 100) / 100 : 0;
        },

        get calcTotal() {
            return this.calcSubtotal + this.calcTax;
        },

        formatNumber(n) {
            return Number(n).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
        },

        formatCurrency(n) {
            return 'Rp ' + Number(n).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
        },
    };
}
</script>
@endpush
