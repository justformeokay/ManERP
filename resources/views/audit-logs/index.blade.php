@extends('layouts.app')

@section('title', 'Audit Logs')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">Audit Logs</span>
@endsection

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Audit Logs</h1>
            <p class="mt-1 text-sm text-gray-500">View all system activity and change history.</p>
        </div>
        <div x-data="integrityChecker()" class="flex-shrink-0">
            <button @click="verify()" :disabled="loading"
                class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50 transition">
                <svg x-show="!loading" class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                </svg>
                <svg x-show="loading" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span x-text="loading ? 'Verifying...' : 'Verify Integrity'"></span>
            </button>

            {{-- Results Panel --}}
            <template x-if="result">
                <div class="mt-3 rounded-xl border p-4 text-sm" :class="result.tampered.length ? 'border-red-200 bg-red-50' : 'border-green-200 bg-green-50'">
                    <div class="flex items-center gap-2 font-semibold" :class="result.tampered.length ? 'text-red-800' : 'text-green-800'">
                        <template x-if="!result.tampered.length">
                            <svg class="h-5 w-5 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </template>
                        <template x-if="result.tampered.length">
                            <svg class="h-5 w-5 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z" /></svg>
                        </template>
                        <span x-text="result.tampered.length ? 'Integrity Issues Detected' : 'All Records Verified'"></span>
                    </div>
                    <div class="mt-2 grid grid-cols-3 gap-3 text-xs">
                        <div><span class="text-gray-500">Total:</span> <span class="font-medium" x-text="result.total"></span></div>
                        <div><span class="text-gray-500">Verified:</span> <span class="font-medium text-green-700" x-text="result.verified"></span></div>
                        <div><span class="text-gray-500">Legacy:</span> <span class="font-medium text-gray-500" x-text="result.legacy"></span></div>
                    </div>
                    <template x-if="result.tampered.length">
                        <div class="mt-3 border-t border-red-200 pt-3">
                            <p class="text-xs font-medium text-red-700 mb-2">Tampered Records (<span x-text="result.tampered.length"></span>):</p>
                            <div class="max-h-40 overflow-y-auto space-y-1">
                                <template x-for="r in result.tampered" :key="r.id">
                                    <div class="flex items-center gap-2 text-xs text-red-600">
                                        <span class="font-mono">#<span x-text="r.id"></span></span>
                                        <span x-text="r.module + ' / ' + r.action"></span>
                                        <span class="text-red-400 truncate" x-text="r.description"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>
@endsection

@section('content')
    {{-- Filters --}}
    <div class="mb-6 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
        <form method="GET" action="{{ route('audit-logs.index') }}" class="flex flex-col sm:flex-row gap-3 flex-wrap">
            <div class="flex-1 min-w-[200px]">
                <input
                    type="search"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search description, module, or user..."
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition"
                />
            </div>
            <select name="module" class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                <option value="">All Modules</option>
                @foreach(\App\Models\ActivityLog::modules() as $mod)
                    <option value="{{ $mod }}" @selected(request('module') === $mod)>{{ ucfirst($mod) }}</option>
                @endforeach
            </select>
            <select name="action" class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                <option value="">All Actions</option>
                @foreach(\App\Models\ActivityLog::actions() as $act)
                    <option value="{{ $act }}" @selected(request('action') === $act)>{{ ucfirst($act) }}</option>
                @endforeach
            </select>
            <select name="user_id" class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                <option value="">All Users</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
            <input type="date" name="date_from" value="{{ request('date_from') }}" placeholder="From"
                class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500" />
            <input type="date" name="date_to" value="{{ request('date_to') }}" placeholder="To"
                class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500" />
            <div class="flex gap-2">
                @include('components.button', ['label' => 'Filter', 'type' => 'secondary', 'buttonType' => 'submit'])
                @if(request()->hasAny(['search', 'module', 'action', 'user_id', 'date_from', 'date_to']))
                    @include('components.button', ['label' => 'Clear', 'type' => 'ghost', 'href' => route('audit-logs.index')])
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
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">User</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Module</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Action</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">IP Address</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($logs as $log)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                {{ $log->created_at->format('M d, Y H:i') }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-700 font-semibold text-xs">
                                        {{ $log->user ? strtoupper(substr($log->user->name, 0, 2)) : 'SY' }}
                                    </div>
                                    <span class="text-sm text-gray-700">{{ $log->user->name ?? 'System' }}</span>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 bg-blue-50 text-blue-700 ring-blue-600/20">
                                    {{ ucfirst($log->module) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @php
                                    $actionBadge = match($log->action) {
                                        'create'  => 'bg-green-50 text-green-700 ring-green-600/20',
                                        'update'  => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                                        'delete'  => 'bg-red-50 text-red-700 ring-red-600/20',
                                        'confirm' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                                        'cancel'  => 'bg-gray-100 text-gray-600 ring-gray-500/20',
                                        'deliver', 'receive', 'produce' => 'bg-purple-50 text-purple-700 ring-purple-600/20',
                                        default   => 'bg-gray-50 text-gray-700 ring-gray-600/20',
                                    };
                                @endphp
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $actionBadge }}">
                                    {{ ucfirst($log->action) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700 max-w-xs truncate">
                                {{ $log->description }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                {{ $log->ip_address ?? '—' }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                <a href="{{ route('audit-logs.show', $log) }}"
                                   class="inline-flex items-center rounded-lg px-2.5 py-1.5 text-xs font-medium text-primary-700 bg-primary-50 hover:bg-primary-100 transition">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <svg class="h-10 w-10 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />
                                    </svg>
                                    <p class="text-sm text-gray-500">No audit logs found.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($logs->hasPages())
            <div class="border-t border-gray-100 px-6 py-4">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

@push('scripts')
<script>
function integrityChecker() {
    return {
        loading: false,
        result: null,
        async verify() {
            this.loading = true;
            this.result = null;
            try {
                const res = await fetch('{{ route("audit-logs.verify-integrity") }}', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                this.result = await res.json();
            } catch (e) {
                alert('Integrity check failed. Please try again.');
            }
            this.loading = false;
        }
    };
}
</script>
@endpush
@endsection
