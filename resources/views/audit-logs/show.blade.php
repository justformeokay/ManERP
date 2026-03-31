@extends('layouts.app')

@section('title', 'Audit Log Detail')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <a href="{{ route('audit-logs.index') }}" class="hover:text-gray-700">Audit Logs</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">#{{ $log->id }}</span>
@endsection

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Audit Log #{{ $log->id }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $log->description }}</p>
        </div>
        @include('components.button', [
            'label' => 'Back to Logs',
            'type' => 'ghost',
            'href' => route('audit-logs.index'),
        ])
    </div>
@endsection

@section('content')
    {{-- Info Card --}}
    <div class="mb-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Activity Information</h2>
        <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <dt class="text-xs font-medium uppercase tracking-wider text-gray-500">User</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $log->user->name ?? 'System' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wider text-gray-500">Module</dt>
                <dd class="mt-1">
                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 bg-blue-50 text-blue-700 ring-blue-600/20">
                        {{ ucfirst($log->module) }}
                    </span>
                </dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wider text-gray-500">Action</dt>
                <dd class="mt-1">
                    @php
                        $actionBadge = match($log->action) {
                            'create'  => 'bg-green-50 text-green-700 ring-green-600/20',
                            'update'  => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                            'delete'  => 'bg-red-50 text-red-700 ring-red-600/20',
                            'confirm' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                            'cancel'  => 'bg-gray-100 text-gray-600 ring-gray-500/20',
                            default   => 'bg-gray-50 text-gray-700 ring-gray-600/20',
                        };
                    @endphp
                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $actionBadge }}">
                        {{ ucfirst($log->action) }}
                    </span>
                </dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wider text-gray-500">Date & Time</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $log->created_at->format('M d, Y H:i:s') }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wider text-gray-500">IP Address</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $log->ip_address ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wider text-gray-500">User Agent</dt>
                <dd class="mt-1 text-sm text-gray-700 text-xs break-all">{{ $log->user_agent ?? '—' }}</dd>
            </div>
        </dl>
    </div>

    {{-- Data Comparison --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Old Data --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-red-100 text-red-600">
                    <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4" /></svg>
                </span>
                Previous Data
            </h2>
            @if($log->old_data)
                <div class="rounded-xl bg-gray-50 p-4 overflow-auto max-h-[500px]">
                    <table class="w-full text-sm">
                        <thead>
                            <tr>
                                <th class="text-left pb-2 text-xs font-semibold uppercase tracking-wider text-gray-500 border-b border-gray-200">Field</th>
                                <th class="text-left pb-2 text-xs font-semibold uppercase tracking-wider text-gray-500 border-b border-gray-200 pl-4">Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($log->old_data as $key => $value)
                                <tr class="border-b border-gray-100 last:border-0">
                                    <td class="py-2 text-gray-600 font-medium align-top">{{ $key }}</td>
                                    <td class="py-2 text-gray-800 pl-4 break-all {{ isset($log->new_data[$key]) && $log->new_data[$key] !== $value ? 'bg-red-50 rounded px-1' : '' }}">
                                        @if(is_array($value) || is_object($value))
                                            <pre class="text-xs whitespace-pre-wrap">{{ json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        @else
                                            {{ $value ?? '—' }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-sm text-gray-400 italic">No previous data available.</p>
            @endif
        </div>

        {{-- New Data --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-green-100 text-green-600">
                    <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                </span>
                New Data
            </h2>
            @if($log->new_data)
                <div class="rounded-xl bg-gray-50 p-4 overflow-auto max-h-[500px]">
                    <table class="w-full text-sm">
                        <thead>
                            <tr>
                                <th class="text-left pb-2 text-xs font-semibold uppercase tracking-wider text-gray-500 border-b border-gray-200">Field</th>
                                <th class="text-left pb-2 text-xs font-semibold uppercase tracking-wider text-gray-500 border-b border-gray-200 pl-4">Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($log->new_data as $key => $value)
                                <tr class="border-b border-gray-100 last:border-0">
                                    <td class="py-2 text-gray-600 font-medium align-top">{{ $key }}</td>
                                    <td class="py-2 text-gray-800 pl-4 break-all {{ isset($log->old_data[$key]) && $log->old_data[$key] !== $value ? 'bg-green-50 rounded px-1' : '' }}">
                                        @if(is_array($value) || is_object($value))
                                            <pre class="text-xs whitespace-pre-wrap">{{ json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        @else
                                            {{ $value ?? '—' }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-sm text-gray-400 italic">No new data available.</p>
            @endif
        </div>
    </div>
@endsection
