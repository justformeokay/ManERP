@extends('layouts.app')

@section('title', __('messages.employees'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">{{ __('messages.dashboard') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('messages.employees') }}</span>
@endsection

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.employees') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.employees_desc') }}</p>
        </div>
        @include('components.button', ['label' => __('messages.add_employee'), 'type' => 'primary', 'href' => route('hr.employees.create')])
    </div>
@endsection

@section('content')
    {{-- Filters --}}
    <div class="mb-6 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('messages.search_employees') }}"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">
            </div>
            <select name="department" class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 pr-10 text-sm text-gray-700 appearance-none focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                <option value="">{{ __('messages.all_departments') }}</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept }}" @selected(request('department') === $dept)>{{ $dept }}</option>
                @endforeach
            </select>
            <select name="status" class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 pr-10 text-sm text-gray-700 appearance-none focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                <option value="">{{ __('messages.all_status') }}</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </select>
            <div class="flex gap-2">
                @include('components.button', ['label' => __('messages.filter'), 'type' => 'secondary', 'buttonType' => 'submit'])
                @if(request()->hasAny(['search', 'department', 'status']))
                    @include('components.button', ['label' => __('messages.clear'), 'type' => 'ghost', 'href' => route('hr.employees.index')])
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
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">NIK</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.name') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.department') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.position') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">PTKP</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($employees as $emp)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <a href="{{ route('hr.employees.show', $emp) }}" class="text-sm font-semibold text-primary-700 hover:text-primary-800">
                                    {{ $emp->nik }}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $emp->name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $emp->department ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $emp->position ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $emp->ptkp_status }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset
                                    {{ $emp->status === 'active' ? 'bg-green-50 text-green-700 ring-green-300' : 'bg-gray-100 text-gray-700 ring-gray-300' }}">
                                    {{ ucfirst($emp->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('hr.employees.show', $emp) }}" class="text-sm text-primary-600 hover:text-primary-800">{{ __('messages.view') }}</a>
                                    <a href="{{ route('hr.employees.edit', $emp) }}" class="text-sm text-gray-600 hover:text-gray-800">{{ __('messages.edit') }}</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-400">
                                {{ __('messages.no_employees') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($employees->hasPages())
            <div class="border-t border-gray-100 px-6 py-4">
                {{ $employees->links() }}
            </div>
        @endif
    </div>
@endsection
