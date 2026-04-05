@extends('layouts.app')

@section('title', 'Access Restricted')

@section('content')
<div class="flex flex-col items-center justify-center px-4 py-20 min-h-full">

    {{-- Shield Illustration --}}
    <div class="relative mb-8 select-none">
        <div class="h-32 w-32 rounded-full bg-amber-50 flex items-center justify-center ring-8 ring-amber-100/70">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6
                       1.5 7.016 1.5 9.269V12c0 5.523 4.477 9 9 9s9-3.477 9-9V9.27c0-2.253-.8-4.32-2.1-5.954
                       A11.959 11.959 0 0112 2.714z" />
            </svg>
        </div>
        {{-- 403 badge --}}
        <div class="absolute -bottom-1 -right-1 h-10 w-10 rounded-full bg-amber-500 flex items-center justify-center shadow-lg ring-4 ring-white">
            <span class="text-white text-[11px] font-extrabold tracking-tight leading-none">403</span>
        </div>
    </div>

    {{-- Heading --}}
    <h1 class="text-2xl font-bold text-gray-900 mb-2 text-center">Akses Terbatas</h1>
    <p class="text-gray-500 text-center max-w-lg text-sm leading-relaxed mb-1">
        Halaman yang Anda coba buka <span class="font-semibold text-gray-700">memerlukan izin khusus</span> yang belum diberikan kepada akun Anda.
    </p>
    <p class="text-gray-400 text-center max-w-md text-xs mb-8">
        Ini bukan error aplikasi — akses Anda sudah dikonfigurasi sesuai kebijakan keamanan sistem oleh administrator.
    </p>

    {{-- User Identity Card --}}
    @auth
    <div class="mb-8 flex items-center gap-3 rounded-2xl bg-white px-5 py-3 shadow-sm ring-1 ring-gray-100">
        {{-- Avatar --}}
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-indigo-500 text-sm font-bold text-white">
            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
        </div>
        <div class="leading-tight">
            <p class="text-sm font-semibold text-gray-800">{{ auth()->user()->name }}</p>
            <p class="text-xs text-gray-400">{{ auth()->user()->email }}</p>
        </div>
        <div class="ml-2 flex items-center gap-1.5 rounded-full px-3 py-1
            {{ auth()->user()->role === 'admin' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-600' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            <span class="text-xs font-semibold capitalize">{{ auth()->user()->role }}</span>
        </div>
    </div>

    {{-- Permission Map (staff only) --}}
    @if(auth()->user()->role === 'staff')
    <div class="w-full max-w-2xl mb-10">
        <p class="text-center text-[11px] font-semibold uppercase tracking-widest text-gray-400 mb-4">
            Modul yang dapat Anda akses
        </p>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2">
            @foreach(\App\Models\User::PERMISSION_MODULES as $module => $label)
                @php
                    $user       = auth()->user();
                    $canView    = $user->hasPermission("{$module}.view");
                    $canCreate  = $user->hasPermission("{$module}.create");
                    $canEdit    = $user->hasPermission("{$module}.edit");
                    $canDelete  = $user->hasPermission("{$module}.delete");
                    $hasAny     = $canView || $canCreate || $canEdit || $canDelete;
                @endphp
                <div class="rounded-xl p-3 transition
                    {{ $hasAny
                        ? 'bg-white ring-1 ring-gray-200 shadow-sm'
                        : 'bg-gray-50 ring-1 ring-gray-100 opacity-50' }}">
                    <div class="flex items-start gap-2 mb-2">
                        @if($hasAny)
                            <div class="mt-0.5 h-4 w-4 shrink-0 rounded-full bg-green-100 flex items-center justify-center">
                                <svg class="h-2.5 w-2.5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                        @else
                            <div class="mt-0.5 h-4 w-4 shrink-0 rounded-full bg-gray-100 flex items-center justify-center">
                                <svg class="h-2.5 w-2.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </div>
                        @endif
                        <span class="text-xs font-medium leading-tight {{ $hasAny ? 'text-gray-800' : 'text-gray-400' }}">
                            {{ $label }}
                        </span>
                    </div>
                    @if($hasAny)
                    <div class="flex flex-wrap gap-1 pl-6">
                        @foreach(['view' => $canView, 'create' => $canCreate, 'edit' => $canEdit, 'delete' => $canDelete] as $action => $allowed)
                            @if($allowed)
                            <span class="rounded-md px-1.5 py-0.5 text-[10px] font-medium leading-none
                                @if($action === 'view')    bg-blue-50   text-blue-600
                                @elseif($action === 'create') bg-green-50  text-green-600
                                @elseif($action === 'edit')   bg-yellow-50 text-yellow-600
                                @else                         bg-red-50    text-red-500
                                @endif">
                                {{ $action }}
                            </span>
                            @endif
                        @endforeach
                    </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
    @endif

    @endauth

    {{-- Action Buttons --}}
    <div class="flex items-center gap-3 flex-wrap justify-center">
        <a href="javascript:history.back()"
            class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Kembali
        </a>
        <a href="{{ route('dashboard') }}"
            class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            Ke Dashboard
        </a>
    </div>

    {{-- Contact Admin Hint --}}
    <div class="mt-8 flex items-center gap-2 rounded-xl bg-blue-50 px-5 py-3 text-sm text-blue-700 ring-1 ring-blue-100">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span>Butuh akses lebih? Hubungi <span class="font-semibold">administrator sistem</span> untuk menyesuaikan permission akun Anda.</span>
    </div>

</div>
@endsection
