@extends('layouts.app')

@section('title', __('maintenance.system_maintenance'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">{{ __('messages.dashboard') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('maintenance.system_maintenance') }}</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('maintenance.system_maintenance') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('maintenance.description') }}</p>
    </div>
@endsection

@section('content')
<div class="space-y-6">

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="rounded-2xl bg-green-50 p-4 ring-1 ring-green-200">
        <div class="flex items-center gap-2">
            <svg class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
        </div>
    </div>
    @endif
    @if(session('error'))
    <div class="rounded-2xl bg-red-50 p-4 ring-1 ring-red-200">
        <div class="flex items-center gap-2">
            <svg class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z" />
            </svg>
            <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
        </div>
    </div>
    @endif

    {{-- Row 1: Disk Usage + Quick Actions --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Disk Usage --}}
        <div class="lg:col-span-2 bg-white rounded-2xl p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="font-semibold text-gray-900 mb-4">{{ __('maintenance.disk_usage') }}</h3>
            <div class="flex items-center gap-6">
                <div class="flex-1">
                    <div class="h-4 w-full rounded-full bg-gray-200 overflow-hidden">
                        <div class="h-4 rounded-full transition-all {{ $diskUsagePercent >= 90 ? 'bg-red-500' : ($diskUsagePercent >= 70 ? 'bg-amber-500' : 'bg-emerald-500') }}"
                             style="width: {{ min($diskUsagePercent, 100) }}%"></div>
                    </div>
                    <div class="flex items-center justify-between mt-2 text-sm text-gray-500">
                        <span>{{ __('maintenance.used') }}: {{ formatBytes($diskUsedBytes) }}</span>
                        <span>{{ __('maintenance.free') }}: {{ formatBytes($diskFreeBytes) }}</span>
                        <span>{{ __('maintenance.total') }}: {{ formatBytes($diskTotalBytes) }}</span>
                    </div>
                </div>
                <div class="text-right flex-shrink-0">
                    <p class="text-3xl font-bold {{ $diskUsagePercent >= 90 ? 'text-red-600' : 'text-gray-900' }}">{{ $diskUsagePercent }}%</p>
                    <p class="text-xs text-gray-400">{{ __('maintenance.capacity_used') }}</p>
                </div>
            </div>
            @if($diskUsagePercent >= 90)
            <div class="mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-700 flex items-center gap-2">
                <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z" />
                </svg>
                {{ __('maintenance.disk_warning') }}
            </div>
            @endif

            {{-- Backup total size --}}
            <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between text-sm">
                <span class="text-gray-500">{{ __('maintenance.total_backup_size') }}</span>
                <span class="font-semibold text-gray-900">{{ formatBytes($totalBackupSize) }}</span>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="bg-white rounded-2xl p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="font-semibold text-gray-900 mb-4">{{ __('maintenance.actions') }}</h3>
            <div class="space-y-3">
                <form method="POST" action="{{ route('maintenance.backup') }}">
                    @csrf
                    <input type="hidden" name="type" value="full">
                    <button type="submit" class="w-full flex items-center gap-3 rounded-xl bg-blue-50 hover:bg-blue-100 px-4 py-3 text-left transition group"
                            onclick="this.disabled=true; this.form.submit();">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-500 text-white group-hover:bg-blue-600 transition">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-900">{{ __('maintenance.run_full_backup') }}</p>
                            <p class="text-xs text-gray-500">{{ __('maintenance.full_backup_desc') }}</p>
                        </div>
                    </button>
                </form>

                <form method="POST" action="{{ route('maintenance.backup') }}">
                    @csrf
                    <input type="hidden" name="type" value="db-only">
                    <button type="submit" class="w-full flex items-center gap-3 rounded-xl bg-emerald-50 hover:bg-emerald-100 px-4 py-3 text-left transition group"
                            onclick="this.disabled=true; this.form.submit();">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-500 text-white group-hover:bg-emerald-600 transition">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-900">{{ __('maintenance.run_db_backup') }}</p>
                            <p class="text-xs text-gray-500">{{ __('maintenance.db_backup_desc') }}</p>
                        </div>
                    </button>
                </form>

                <form method="POST" action="{{ route('maintenance.archive') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-3 rounded-xl bg-amber-50 hover:bg-amber-100 px-4 py-3 text-left transition group"
                            onclick="this.disabled=true; this.form.submit();">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-500 text-white group-hover:bg-amber-600 transition">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-900">{{ __('maintenance.run_log_archive') }}</p>
                            <p class="text-xs text-gray-500">{{ __('maintenance.log_archive_desc') }}</p>
                        </div>
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Row 2: Recent Backups --}}
    <div class="bg-white rounded-2xl shadow-sm ring-1 ring-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                </svg>
                <h3 class="font-semibold text-gray-900">{{ __('maintenance.recent_backups') }}</h3>
                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-blue-100 text-blue-700">{{ $filesMeta->count() }} {{ __('maintenance.files') }}</span>
            </div>
        </div>

        @if($filesMeta->isEmpty())
            <div class="px-6 py-12 text-center text-gray-500">
                <svg class="mx-auto h-10 w-10 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3" />
                </svg>
                <p class="text-sm">{{ __('maintenance.no_backups') }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ __('maintenance.no_backups_hint') }}</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('maintenance.filename') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">{{ __('maintenance.size') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ __('maintenance.created') }}</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">{{ __('maintenance.action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($filesMeta as $index => $file)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    @if($index === 0)
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-green-100 text-green-700">{{ __('maintenance.latest') }}</span>
                                    @endif
                                    <span class="text-sm font-medium text-gray-900">{{ $file['name'] }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-right text-gray-600">
                                {{ number_format($file['size'] / 1048576, 2) }} MB
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ \Carbon\Carbon::createFromTimestamp($file['modified'])->format('d M Y H:i') }}
                                <span class="text-xs text-gray-400 block">{{ \Carbon\Carbon::createFromTimestamp($file['modified'])->diffForHumans() }}</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <a href="{{ route('maintenance.download', ['file' => $file['path']]) }}"
                                   class="inline-flex items-center gap-1 rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100 transition">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                    </svg>
                                    {{ __('maintenance.download') }}
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Row 3: Log & Archive Stats --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Activity Log Stats --}}
        <div class="bg-white rounded-2xl p-6 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center gap-2 mb-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-purple-100">
                    <svg class="h-4 w-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h4 class="font-semibold text-gray-900 text-sm">{{ __('maintenance.active_logs') }}</h4>
            </div>
            <p class="text-3xl font-bold text-gray-900">{{ number_format($mainLogCount) }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ __('maintenance.records_in_activity_logs') }}</p>
        </div>

        {{-- Archived Logs --}}
        <div class="bg-white rounded-2xl p-6 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center gap-2 mb-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100">
                    <svg class="h-4 w-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                    </svg>
                </div>
                <h4 class="font-semibold text-gray-900 text-sm">{{ __('maintenance.archived_logs') }}</h4>
            </div>
            <p class="text-3xl font-bold text-gray-900">{{ number_format($archiveCount) }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ __('maintenance.records_in_archive') }}</p>
        </div>

        {{-- Schedule Info --}}
        <div class="bg-white rounded-2xl p-6 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center gap-2 mb-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100">
                    <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                    </svg>
                </div>
                <h4 class="font-semibold text-gray-900 text-sm">{{ __('maintenance.schedule') }}</h4>
            </div>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">{{ __('maintenance.full_backup') }}</span>
                    <span class="font-medium text-gray-900">{{ __('maintenance.daily_at', ['time' => '02:00']) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">{{ __('maintenance.db_backup') }}</span>
                    <span class="font-medium text-gray-900">{{ __('maintenance.every_6h') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">{{ __('maintenance.cleanup') }}</span>
                    <span class="font-medium text-gray-900">{{ __('maintenance.daily_at', ['time' => '03:00']) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">{{ __('maintenance.log_archive') }}</span>
                    <span class="font-medium text-gray-900">{{ __('maintenance.weekly_sunday') }}</span>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t border-gray-100 space-y-1.5 text-xs text-gray-400">
                <p>{{ __('maintenance.retention_daily', ['days' => 14]) }}</p>
                <p>{{ __('maintenance.retention_weekly', ['weeks' => 4]) }}</p>
                <p>{{ __('maintenance.retention_monthly', ['months' => 6]) }}</p>
            </div>
        </div>
    </div>
</div>

@endsection
