@extends('layouts.app')

@section('title', 'Chart of Accounts')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">Chart of Accounts</span>
@endsection

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Chart of Accounts</h1>
            <p class="mt-1 text-sm text-gray-500">Manage your account structure</p>
        </div>
        @include('components.button', ['label' => 'Add Account', 'type' => 'primary', 'href' => route('accounting.coa.create')])
    </div>
@endsection

@section('content')
    {{-- Filters --}}
    <div class="mb-6 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by code or name..."
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">
            </div>
            <select name="type" class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                <option value="">All Types</option>
                @foreach(\App\Models\ChartOfAccount::typeOptions() as $t)
                    <option value="{{ $t }}" @selected(request('type') === $t)>{{ ucfirst($t) }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                @include('components.button', ['label' => 'Filter', 'type' => 'secondary', 'buttonType' => 'submit'])
                @if(request()->hasAny(['search', 'type']))
                    @include('components.button', ['label' => 'Clear', 'type' => 'ghost', 'href' => route('accounting.coa.index')])
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
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Code</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Parent</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($accounts as $account)
                        @php $typeColors = \App\Models\ChartOfAccount::typeColors(); @endphp
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <span class="text-sm font-mono font-semibold text-gray-900">{{ $account->code }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm font-medium text-gray-900">{{ $account->name }}</span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $typeColors[$account->type] ?? '' }}">
                                    {{ ucfirst($account->type) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ $account->parent ? $account->parent->code . ' — ' . $account->parent->name : '—' }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-center">
                                @if($account->is_active)
                                    <span class="inline-flex rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700 ring-1 ring-green-300">Active</span>
                                @else
                                    <span class="inline-flex rounded-full bg-gray-50 px-2.5 py-0.5 text-xs font-medium text-gray-500 ring-1 ring-gray-300">Inactive</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('accounting.coa.edit', $account) }}"
                                       class="inline-flex items-center rounded-lg px-2.5 py-1.5 text-xs font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 transition">
                                        Edit
                                    </a>
                                    <form method="POST" action="{{ route('accounting.coa.destroy', $account) }}"
                                          onsubmit="return confirm('Delete account {{ $account->code }}?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="inline-flex items-center rounded-lg px-2.5 py-1.5 text-xs font-medium text-red-600 bg-red-50 hover:bg-red-100 transition">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <svg class="h-12 w-12 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-500">No accounts found.</p>
                                    <a href="{{ route('accounting.coa.create') }}" class="mt-3 text-sm font-medium text-primary-600 hover:text-primary-700">
                                        + Add your first account
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($accounts->hasPages())
            <div class="border-t border-gray-100 px-6 py-4">
                {{ $accounts->links() }}
            </div>
        @endif
    </div>
@endsection
