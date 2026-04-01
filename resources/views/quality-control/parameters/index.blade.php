@extends('layouts.app')

@section('title', __('messages.qc_parameters_title'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">{{ __('messages.dashboard') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('messages.qc_parameters_title') }}</span>
@endsection

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.qc_parameters_title') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.qc_parameters_subtitle') }}</p>
        </div>
        @include('components.button', [
            'label' => __('messages.new_parameter_btn'),
            'type' => 'primary',
            'href' => route('qc.parameters.create'),
            'icon' => '<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>',
        ])
    </div>
@endsection

@section('content')
    {{-- Filters --}}
    <div class="mb-6 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
        <form method="GET" action="{{ route('qc.parameters.index') }}" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <input type="search" name="search" value="{{ request('search') }}"
                    placeholder="{{ __('messages.search_parameter_placeholder') }}"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition" />
            </div>
            <select name="type" class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                <option value="">{{ __('messages.all_types') }}</option>
                @foreach(\App\Models\QcParameter::typeOptions() as $t)
                    <option value="{{ $t }}" @selected(request('type') === $t)>{{ ucfirst($t) }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                @include('components.button', ['label' => __('messages.filter_btn'), 'type' => 'secondary', 'buttonType' => 'submit'])
                @if(request()->hasAny(['search', 'type']))
                    @include('components.button', ['label' => __('messages.clear_btn'), 'type' => 'ghost', 'href' => route('qc.parameters.index')])
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
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.parameter_name_header') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.parameter_type_header') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.parameter_unit_header') }}</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.parameter_range_header') }}</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.status_table_header') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.actions_table_header') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($parameters as $parameter)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <p class="text-sm font-medium text-gray-900">{{ $parameter->name }}</p>
                                @if($parameter->description)
                                    <p class="text-xs text-gray-500 mt-0.5 truncate max-w-xs">{{ $parameter->description }}</p>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1
                                    @if($parameter->type === 'numeric') bg-blue-50 text-blue-700 ring-blue-600/20
                                    @elseif($parameter->type === 'boolean') bg-purple-50 text-purple-700 ring-purple-600/20
                                    @else bg-gray-100 text-gray-600 ring-gray-500/20 @endif">
                                    {{ ucfirst($parameter->type) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $parameter->unit ?? '—' }}</td>
                            <td class="px-6 py-4 text-center text-sm text-gray-600">
                                @if($parameter->type === 'numeric' && ($parameter->min_value !== null || $parameter->max_value !== null))
                                    {{ $parameter->min_value ?? '—' }} ~ {{ $parameter->max_value ?? '—' }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($parameter->is_active)
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 bg-green-50 text-green-700 ring-green-600/20">{{ __('messages.qc_active') }}</span>
                                @else
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 bg-gray-100 text-gray-600 ring-gray-500/20">{{ __('messages.qc_inactive') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    @include('components.button', [
                                        'label' => __('messages.edit_btn'),
                                        'type' => 'ghost',
                                        'size' => 'sm',
                                        'href' => route('qc.parameters.edit', $parameter),
                                    ])
                                    <form action="{{ route('qc.parameters.destroy', $parameter) }}" method="POST"
                                        onsubmit="return confirm('{{ __('messages.delete_confirm') }}')">
                                        @csrf @method('DELETE')
                                        @include('components.button', [
                                            'label' => __('messages.delete_btn'),
                                            'type' => 'danger-ghost',
                                            'size' => 'sm',
                                            'buttonType' => 'submit',
                                        ])
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <svg class="h-10 w-10 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                    <p class="text-sm text-gray-500">{{ __('messages.no_parameters_found') }}</p>
                                    <a href="{{ route('qc.parameters.create') }}" class="text-sm font-semibold text-primary-600 hover:text-primary-700">+ {{ __('messages.create_first_parameter') }}</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($parameters->hasPages())
            <div class="border-t border-gray-100 px-6 py-3">
                {{ $parameters->links() }}
            </div>
        @endif
    </div>
@endsection
