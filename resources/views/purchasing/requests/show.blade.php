@extends('layouts.app')

@section('title', $purchaseRequest->number)

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">{{ __('messages.dashboard') }}</a>
    <span class="mx-1">/</span>
    <a href="{{ route('purchase-requests.index') }}" class="hover:text-gray-700">{{ __('messages.purchase_requests_title') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ $purchaseRequest->number }}</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $purchaseRequest->number }}</h1>
        <p class="mt-1 text-sm text-gray-500">
            @php $sColors = ['draft'=>'gray','pending'=>'amber','approved'=>'green','rejected'=>'red','converted'=>'blue']; $sc = $sColors[$purchaseRequest->status] ?? 'gray'; @endphp
            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium bg-{{ $sc }}-50 text-{{ $sc }}-700 ring-1 ring-{{ $sc }}-600/20">
                {{ __('messages.pr_status_' . $purchaseRequest->status) }}
            </span>
        </p>
    </div>
    <div class="flex gap-2">
        @if($purchaseRequest->status === 'draft')
            <form action="{{ route('purchase-requests.submit', $purchaseRequest) }}" method="POST">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-amber-700 transition">
                    {{ __('messages.submit_for_approval_btn') }}
                </button>
            </form>
            <a href="{{ route('purchase-requests.edit', $purchaseRequest) }}"
               class="inline-flex items-center gap-2 rounded-xl bg-gray-100 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-200 transition">
                {{ __('messages.edit_btn') }}
            </a>
        @endif

        @if($purchaseRequest->status === 'pending')
            <form action="{{ route('purchase-requests.approve', $purchaseRequest) }}" method="POST">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-green-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-green-700 transition">
                    {{ __('messages.approve_btn') }}
                </button>
            </form>
            <button type="button" onclick="document.getElementById('rejectModal').classList.remove('hidden')"
                class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-700 transition">
                {{ __('messages.reject_btn') }}
            </button>
        @endif

        @if($purchaseRequest->status === 'approved')
            <a href="{{ route('purchase-requests.convert', $purchaseRequest) }}"
               class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 transition">
                {{ __('messages.convert_to_po_btn') }}
            </a>
        @endif

        @if($purchaseRequest->status === 'rejected')
            <a href="{{ route('purchase-requests.edit', $purchaseRequest) }}"
               class="inline-flex items-center gap-2 rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-amber-700 transition">
                {{ __('messages.revise_resubmit_btn') }}
            </a>
        @endif
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left: Request Info --}}
        <div class="lg:col-span-1 space-y-4">
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-base font-semibold text-gray-900 mb-4">{{ __('messages.pr_info_section') }}</h3>
                <dl class="space-y-3 text-sm">
                    <div><dt class="text-gray-500">{{ __('messages.requested_by_header') }}</dt><dd class="font-medium text-gray-900 mt-0.5">{{ $purchaseRequest->requester->name ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">{{ __('messages.priority_header') }}</dt>
                        <dd class="mt-0.5">
                            @php $pColors = ['low'=>'gray','normal'=>'blue','high'=>'amber','urgent'=>'red']; $pc = $pColors[$purchaseRequest->priority] ?? 'gray'; @endphp
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium bg-{{ $pc }}-50 text-{{ $pc }}-700 ring-1 ring-{{ $pc }}-600/20">{{ __('messages.priority_' . $purchaseRequest->priority) }}</span>
                        </dd>
                    </div>
                    <div><dt class="text-gray-500">{{ __('messages.required_date_label') }}</dt><dd class="font-medium text-gray-900 mt-0.5">{{ $purchaseRequest->required_date?->format('d M Y') ?? '—' }}</dd></div>
                    @if($purchaseRequest->project)
                        <div><dt class="text-gray-500">{{ __('messages.project_label') }}</dt><dd class="font-medium text-gray-900 mt-0.5">{{ $purchaseRequest->project->name }}</dd></div>
                    @endif
                    @if($purchaseRequest->department)
                        <div><dt class="text-gray-500">{{ __('messages.pr_department_label') }}</dt><dd class="font-medium text-gray-900 mt-0.5">{{ $purchaseRequest->department->name }}</dd></div>
                    @endif
                    @if($purchaseRequest->purchase_type)
                        <div><dt class="text-gray-500">{{ __('messages.pr_purchase_type_label') }}</dt>
                            <dd class="mt-0.5">
                                @php $ptColors = ['operational'=>'gray','project_sales'=>'blue','project_capex'=>'violet']; $ptc = $ptColors[$purchaseRequest->purchase_type] ?? 'gray'; @endphp
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium bg-{{ $ptc }}-50 text-{{ $ptc }}-700 ring-1 ring-{{ $ptc }}-600/20">{{ __('messages.po_purchase_type_' . $purchaseRequest->purchase_type) }}</span>
                            </dd>
                        </div>
                    @endif
                    @if($purchaseRequest->approved_by)
                        <div><dt class="text-gray-500">{{ __('messages.approved_by_label') }}</dt><dd class="font-medium text-gray-900 mt-0.5">{{ $purchaseRequest->approver->name ?? '—' }} ({{ $purchaseRequest->approved_at?->format('d M Y H:i') }})</dd></div>
                    @endif
                    <div><dt class="text-gray-500">{{ __('messages.estimated_total_header') }}</dt><dd class="font-semibold text-lg text-gray-900 mt-0.5">{{ format_currency($purchaseRequest->getEstimatedTotal()) }}</dd></div>
                </dl>
            </div>

            @if($purchaseRequest->reason)
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <h3 class="text-base font-semibold text-gray-900 mb-2">{{ __('messages.pr_reason_label') }}</h3>
                    <p class="text-sm text-gray-600">{{ $purchaseRequest->reason }}</p>
                </div>
            @endif

            @if($purchaseRequest->rejection_reason)
                <div class="rounded-2xl bg-red-50 p-6 shadow-sm ring-1 ring-red-100">
                    <h3 class="text-base font-semibold text-red-900 mb-2">{{ __('messages.rejection_reason_label') }}</h3>
                    <p class="text-sm text-red-700">{{ $purchaseRequest->rejection_reason }}</p>
                </div>
            @endif

            @if($purchaseRequest->purchaseOrder)
                <div class="rounded-2xl bg-blue-50 p-6 shadow-sm ring-1 ring-blue-100">
                    <h3 class="text-base font-semibold text-blue-900 mb-2">{{ __('messages.linked_po') }}</h3>
                    <a href="{{ route('purchasing.show', $purchaseRequest->purchaseOrder) }}" class="text-sm font-medium text-blue-700 hover:underline">
                        {{ $purchaseRequest->purchaseOrder->number }}
                    </a>
                </div>
            @endif
        </div>

        {{-- Right: Items Table --}}
        <div class="lg:col-span-2">
            <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-900">{{ __('messages.pr_items_section') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50/60">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">#</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">{{ __('messages.product_label') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">{{ __('messages.quantity_header') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">{{ __('messages.est_price_label') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">{{ __('messages.total_header') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">{{ __('messages.specification_label') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($purchaseRequest->items as $item)
                                <tr class="hover:bg-gray-50/50">
                                    <td class="px-6 py-3 text-sm text-gray-400">{{ $loop->iteration }}</td>
                                    <td class="px-6 py-3 text-sm">
                                        <p class="font-medium text-gray-900">{{ $item->product->name ?? '—' }}</p>
                                        <p class="text-xs text-gray-400">{{ $item->product->sku ?? '' }}</p>
                                    </td>
                                    <td class="px-6 py-3 text-sm text-right text-gray-600">{{ number_format($item->quantity, 2) }}</td>
                                    <td class="px-6 py-3 text-sm text-right text-gray-600">{{ format_currency($item->estimated_price) }}</td>
                                    <td class="px-6 py-3 text-sm text-right font-semibold text-gray-900">{{ format_currency($item->total) }}</td>
                                    <td class="px-6 py-3 text-sm text-gray-500">{{ $item->specification ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50/60">
                            <tr>
                                <td colspan="4" class="px-6 py-3 text-right text-sm font-semibold text-gray-900">{{ __('messages.total_header') }}</td>
                                <td class="px-6 py-3 text-right text-sm font-bold text-gray-900">{{ format_currency($purchaseRequest->getEstimatedTotal()) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Reject Modal --}}
    @if($purchaseRequest->status === 'pending')
        <div id="rejectModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-white rounded-2xl p-6 shadow-xl max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('messages.reject_pr_title') }}</h3>
                <form action="{{ route('purchase-requests.reject', $purchaseRequest) }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.rejection_reason_label') }} <span class="text-red-500">*</span></label>
                        <textarea name="rejection_reason" rows="3" required
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                            placeholder="{{ __('messages.rejection_reason_placeholder') }}"></textarea>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" onclick="document.getElementById('rejectModal').classList.add('hidden')"
                            class="rounded-xl px-4 py-2.5 text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 transition">
                            {{ __('messages.cancel_btn') }}
                        </button>
                        <button type="submit"
                            class="rounded-xl px-4 py-2.5 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 transition">
                            {{ __('messages.confirm_reject_btn') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection
