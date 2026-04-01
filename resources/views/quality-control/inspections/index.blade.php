@extends('layouts.app')

@section('title', __('messages.qc_inspections_title'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">{{ __('messages.dashboard') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('messages.qc_inspections_title') }}</span>
@endsection

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.qc_inspections_title') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.qc_inspections_subtitle') }}</p>
        </div>
        @include('components.button', [
            'label' => __('messages.new_inspection_btn'),
            'type' => 'primary',
            'href' => route('qc.inspections.create'),
            'icon' => '<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>',
        ])
    </div>
@endsection

@section('content')
    {{-- Filters --}}
    <div class="mb-6 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
        <form method="GET" action="{{ route('qc.inspections.index') }}" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <input type="search" name="search" value="{{ request('search') }}"
                    placeholder="{{ __('messages.search_inspection_placeholder') }}"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition" />
            </div>
            <select name="type" class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                <option value="">{{ __('messages.all_types') }}</option>
                @foreach(\App\Models\QcInspection::inspectionTypeOptions() as $t)
                    <option value="{{ $t }}" @selected(request('type') === $t)>{{ __('messages.qc_type_' . $t) }}</option>
                @endforeach
            </select>
            <select name="result" class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                <option value="">{{ __('messages.all_results') }}</option>
                @foreach(\App\Models\QcInspection::resultOptions() as $r)
                    <option value="{{ $r }}" @selected(request('result') === $r)>{{ __('messages.qc_result_' . $r) }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                @include('components.button', ['label' => __('messages.filter_btn'), 'type' => 'secondary', 'buttonType' => 'submit'])
                @if(request()->hasAny(['search', 'type', 'result']))
                    @include('components.button', ['label' => __('messages.clear_btn'), 'type' => 'ghost', 'href' => route('qc.inspections.index')])
                @endif
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.inspection_number_header') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.inspection_type_header') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.product_header') }}</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.inspected_qty_header') }}</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.pass_rate_header') }}</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.qc_result_header') }}</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.status_table_header') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.actions_table_header') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($inspections as $inspection)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <a href="{{ route('qc.inspections.show', $inspection) }}" class="text-sm font-semibold text-primary-600 hover:text-primary-700">
                                    {{ $inspection->number }}
                                </a>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1
                                    @if($inspection->inspection_type === 'incoming') bg-blue-50 text-blue-700 ring-blue-600/20
                                    @elseif($inspection->inspection_type === 'in_process') bg-amber-50 text-amber-700 ring-amber-600/20
                                    @else bg-purple-50 text-purple-700 ring-purple-600/20 @endif">
                                    {{ __('messages.qc_type_' . $inspection->inspection_type) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $inspection->product->name ?? '—' }}</td>
                            <td class="px-6 py-4 text-center text-sm text-gray-600">{{ number_format($inspection->inspected_quantity, 0) }}</td>
                            <td class="px-6 py-4 text-center">
                                @if($inspection->status === 'completed')
                                    <span class="text-sm font-semibold {{ $inspection->passRate() >= 80 ? 'text-green-600' : ($inspection->passRate() >= 50 ? 'text-amber-600' : 'text-red-600') }}">
                                        {{ $inspection->passRate() }}%
                                    </span>
                                @else
                                    <span class="text-sm text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                @php $rColors = \App\Models\QcInspection::resultColors(); @endphp
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $rColors[$inspection->result] ?? '' }}">
                                    {{ __('messages.qc_result_' . $inspection->result) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @php $sColors = \App\Models\QcInspection::statusColors(); @endphp
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $sColors[$inspection->status] ?? '' }}">
                                    {{ __('messages.qc_status_' . $inspection->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    @include('components.button', [
                                        'label' => __('messages.view_btn'),
                                        'type' => 'ghost',
                                        'size' => 'sm',
                                        'href' => route('qc.inspections.show', $inspection),
                                    ])
                                    @if($inspection->status !== 'completed')
                                        @include('components.button', [
                                            'label' => __('messages.edit_btn'),
                                            'type' => 'ghost',
                                            'size' => 'sm',
                                            'href' => route('qc.inspections.edit', $inspection),
                                        ])
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <svg class="h-10 w-10 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <p class="text-sm text-gray-500">{{ __('messages.no_inspections_found') }}</p>
                                    <a href="{{ route('qc.inspections.create') }}" class="text-sm font-semibold text-primary-600 hover:text-primary-700">+ {{ __('messages.create_first_inspection') }}</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($inspections->hasPages())
            <div class="border-t border-gray-100 px-6 py-3">
                {{ $inspections->links() }}
            </div>
        @endif
    </div>
@endsection
