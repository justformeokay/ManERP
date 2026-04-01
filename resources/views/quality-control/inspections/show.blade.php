@extends('layouts.app')

@section('title', $inspection->number)

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">{{ __('messages.dashboard') }}</a>
    <span class="mx-1">/</span>
    <a href="{{ route('qc.inspections.index') }}" class="hover:text-gray-700">{{ __('messages.qc_inspections_title') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ $inspection->number }}</span>
@endsection

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $inspection->number }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.qc_inspection_details_subtitle') }}</p>
        </div>
        <div class="flex items-center gap-2">
            @if($inspection->status !== 'completed')
                @include('components.button', [
                    'label' => __('messages.edit_btn'),
                    'type' => 'secondary',
                    'href' => route('qc.inspections.edit', $inspection),
                ])
            @endif
            @include('components.button', [
                'label' => __('messages.back_to_list_btn'),
                'type' => 'ghost',
                'href' => route('qc.inspections.index'),
            ])
        </div>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left: Inspection Info --}}
        <div class="lg:col-span-1 space-y-6">
            {{-- Info Card --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-base font-semibold text-gray-900 mb-4">{{ __('messages.inspection_info_section') }}</h3>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-gray-500">{{ __('messages.inspection_type_label') }}</dt>
                        <dd class="font-medium text-gray-900 mt-0.5">
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1
                                @if($inspection->inspection_type === 'incoming') bg-blue-50 text-blue-700 ring-blue-600/20
                                @elseif($inspection->inspection_type === 'in_process') bg-amber-50 text-amber-700 ring-amber-600/20
                                @else bg-purple-50 text-purple-700 ring-purple-600/20 @endif">
                                {{ __('messages.qc_type_' . $inspection->inspection_type) }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('messages.product_label') }}</dt>
                        <dd class="font-medium text-gray-900 mt-0.5">{{ $inspection->product->name ?? '—' }}</dd>
                        <dd class="text-xs text-gray-400">{{ $inspection->product->sku ?? '' }}</dd>
                    </div>
                    @if($inspection->warehouse)
                        <div>
                            <dt class="text-gray-500">{{ __('messages.warehouse_label') }}</dt>
                            <dd class="font-medium text-gray-900 mt-0.5">{{ $inspection->warehouse->name }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-gray-500">{{ __('messages.inspector_label') }}</dt>
                        <dd class="font-medium text-gray-900 mt-0.5">{{ $inspection->inspector->name ?? '—' }}</dd>
                    </div>
                    @if($inspection->inspected_at)
                        <div>
                            <dt class="text-gray-500">{{ __('messages.inspected_at_label') }}</dt>
                            <dd class="font-medium text-gray-900 mt-0.5">{{ $inspection->inspected_at->format('d M Y H:i') }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            {{-- Result Summary --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-base font-semibold text-gray-900 mb-4">{{ __('messages.qc_result_summary') }}</h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">{{ __('messages.status_label') }}</dt>
                        <dd>
                            @php $sColors = \App\Models\QcInspection::statusColors(); @endphp
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $sColors[$inspection->status] ?? '' }}">
                                {{ __('messages.qc_status_' . $inspection->status) }}
                            </span>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">{{ __('messages.qc_result_header') }}</dt>
                        <dd>
                            @php $rColors = \App\Models\QcInspection::resultColors(); @endphp
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $rColors[$inspection->result] ?? '' }}">
                                {{ __('messages.qc_result_' . $inspection->result) }}
                            </span>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">{{ __('messages.inspected_qty_label') }}</dt>
                        <dd class="font-medium text-gray-900">{{ number_format($inspection->inspected_quantity, 0) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">{{ __('messages.passed_qty_label') }}</dt>
                        <dd class="font-semibold text-green-600">{{ number_format($inspection->passed_quantity, 0) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">{{ __('messages.failed_qty_label') }}</dt>
                        <dd class="font-semibold text-red-600">{{ number_format($inspection->failed_quantity, 0) }}</dd>
                    </div>
                    @if($inspection->status === 'completed')
                        <div class="pt-2 border-t border-gray-100">
                            <div class="flex justify-between">
                                <dt class="text-gray-500">{{ __('messages.pass_rate_header') }}</dt>
                                <dd class="font-bold text-lg {{ $inspection->passRate() >= 80 ? 'text-green-600' : ($inspection->passRate() >= 50 ? 'text-amber-600' : 'text-red-600') }}">
                                    {{ $inspection->passRate() }}%
                                </dd>
                            </div>
                        </div>
                    @endif
                </dl>
            </div>

            @if($inspection->notes)
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <h3 class="text-base font-semibold text-gray-900 mb-2">{{ __('messages.notes_label') }}</h3>
                    <p class="text-sm text-gray-600">{{ $inspection->notes }}</p>
                </div>
            @endif
        </div>

        {{-- Right: Parameter Checks & Record Results --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Parameter Check Results --}}
            <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-900">{{ __('messages.qc_check_parameters_section') }}</h3>
                </div>

                @if($inspection->status !== 'completed')
                    {{-- Record Results Form --}}
                    <form action="{{ route('qc.inspections.record-results', $inspection) }}" method="POST" class="p-6 space-y-4">
                        @csrf
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-100">
                                <thead class="bg-gray-50/50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.parameter_label') }}</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.acceptable_range') }}</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.measured_value_label') }}</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.qc_result_header') }}</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.notes_label') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    @foreach($inspection->items as $idx => $item)
                                        <tr>
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                                {{ $item->parameter->name ?? '—' }}
                                                <input type="hidden" name="results[{{ $idx }}][item_id]" value="{{ $item->id }}">
                                            </td>
                                            <td class="px-4 py-3 text-center text-sm text-gray-500">
                                                @if($item->min_value !== null || $item->max_value !== null)
                                                    {{ $item->min_value ?? '—' }} ~ {{ $item->max_value ?? '—' }}
                                                    @if($item->parameter->unit) <span class="text-xs text-gray-400">{{ $item->parameter->unit }}</span> @endif
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td class="px-4 py-3">
                                                <input type="text" name="results[{{ $idx }}][measured_value]" value="{{ old("results.{$idx}.measured_value", $item->measured_value) }}"
                                                    class="w-full max-w-[120px] mx-auto rounded-lg border border-gray-200 px-3 py-1.5 text-center text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500" />
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <select name="results[{{ $idx }}][result]" required
                                                    class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                                                    <option value="pass" @selected(old("results.{$idx}.result", $item->result) === 'pass')>{{ __('messages.qc_item_pass') }}</option>
                                                    <option value="fail" @selected(old("results.{$idx}.result", $item->result) === 'fail')>{{ __('messages.qc_item_fail') }}</option>
                                                </select>
                                            </td>
                                            <td class="px-4 py-3">
                                                <input type="text" name="results[{{ $idx }}][notes]" value="{{ old("results.{$idx}.notes", $item->notes) }}"
                                                    class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500" />
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Quantity Results --}}
                        <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-100">
                            <div>
                                <label for="passed_quantity" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.passed_qty_label') }}</label>
                                <input type="number" id="passed_quantity" name="passed_quantity" step="any" min="0" required
                                    value="{{ old('passed_quantity', $inspection->passed_quantity) }}"
                                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500" />
                            </div>
                            <div>
                                <label for="failed_quantity" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.failed_qty_label') }}</label>
                                <input type="number" id="failed_quantity" name="failed_quantity" step="any" min="0" required
                                    value="{{ old('failed_quantity', $inspection->failed_quantity) }}"
                                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500" />
                            </div>
                        </div>

                        <div class="flex justify-end pt-2">
                            @include('components.button', [
                                'label' => __('messages.record_results_btn'),
                                'type' => 'primary',
                                'buttonType' => 'submit',
                            ])
                        </div>
                    </form>
                @else
                    {{-- Completed Results (Read-only) --}}
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead class="bg-gray-50/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">#</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.parameter_label') }}</th>
                                    <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.acceptable_range') }}</th>
                                    <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.measured_value_label') }}</th>
                                    <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.qc_result_header') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.notes_label') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($inspection->items as $item)
                                    <tr class="hover:bg-gray-50/50">
                                        <td class="px-6 py-3 text-sm text-gray-400">{{ $loop->iteration }}</td>
                                        <td class="px-6 py-3 text-sm font-medium text-gray-900">{{ $item->parameter->name ?? '—' }}</td>
                                        <td class="px-6 py-3 text-center text-sm text-gray-500">
                                            @if($item->min_value !== null || $item->max_value !== null)
                                                {{ $item->min_value ?? '—' }} ~ {{ $item->max_value ?? '—' }}
                                            @else — @endif
                                        </td>
                                        <td class="px-6 py-3 text-center text-sm font-medium text-gray-900">{{ $item->measured_value ?? '—' }}</td>
                                        <td class="px-6 py-3 text-center">
                                            @if($item->result === 'pass')
                                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 bg-green-50 text-green-700 ring-green-600/20">{{ __('messages.qc_item_pass') }}</span>
                                            @elseif($item->result === 'fail')
                                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 bg-red-50 text-red-700 ring-red-600/20">{{ __('messages.qc_item_fail') }}</span>
                                            @else
                                                <span class="text-gray-400">{{ __('messages.qc_result_pending') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-3 text-sm text-gray-500">{{ $item->notes ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
