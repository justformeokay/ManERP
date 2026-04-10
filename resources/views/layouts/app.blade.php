<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php $__cc = currency_config(); @endphp
    <meta name="currency-config" content='{!! json_encode(["symbol" => $__cc["symbol"], "thousandSep" => $__cc["thousand_separator"], "decimalSep" => $__cc["decimal_separator"], "decimals" => $__cc["decimal_places"]]) !!}'>

    <title>@yield('title', config('app.name', 'ManERP')) — {{ config('app.name', 'ManERP') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-50"
      x-data="{ sidebarOpen: true, mobileSidebarOpen: false }">

    <div class="flex h-screen overflow-hidden">

        {{-- Sidebar - Desktop --}}
        <aside class="hidden lg:flex lg:flex-col flex-shrink-0 transition-all duration-300"
               :class="sidebarOpen ? 'w-64' : 'w-20'">
            @include('partials.sidebar')
        </aside>

        {{-- Mobile Sidebar Overlay --}}
        <div x-show="mobileSidebarOpen" x-cloak
             class="fixed inset-0 z-40 flex lg:hidden">
            <div class="fixed inset-0 bg-gray-900/50" @click="mobileSidebarOpen = false"></div>
            <aside class="relative z-50 flex w-64 flex-col bg-gray-900">
                @include('partials.sidebar')
            </aside>
        </div>

        {{-- Main content area --}}
        <div class="flex flex-1 flex-col overflow-hidden min-w-0">

            {{-- Navbar --}}
            @include('partials.navbar')

            {{-- Impersonation Banner (Phase 7) --}}
            @if(session('impersonator_id'))
            <div class="bg-amber-500 text-white px-4 py-2 text-center text-sm font-medium flex items-center justify-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                <span>{{ __('messages.rbac_impersonating', ['name' => auth()->user()->name, 'email' => auth()->user()->email]) }}</span>
                <form method="POST" action="{{ route('impersonate.stop') }}" class="inline">
                    @csrf
                    <button type="submit" class="ml-2 rounded bg-white/20 px-3 py-1 text-xs font-bold hover:bg-white/30 transition">
                        {{ __('messages.rbac_stop_impersonation') }}
                    </button>
                </form>
            </div>
            @endif

            {{-- Page content --}}
            <main class="flex-1 overflow-y-auto">
                {{-- Breadcrumbs & Page Header --}}
                @hasSection('page-header')
                <div class="border-b border-gray-200 bg-white px-4 sm:px-6 lg:px-8 py-4">
                    @hasSection('breadcrumbs')
                    <nav class="mb-2 flex items-center text-sm text-gray-500">
                        @yield('breadcrumbs')
                    </nav>
                    @endif
                    @yield('page-header')
                </div>
                @endif

                {{-- Main Content --}}
                <div class="px-4 sm:px-6 lg:px-8 py-6">
                    {{-- Flash Messages --}}
                    @if(session('success'))
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                        x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                        class="mb-4 flex items-start gap-3 rounded-xl bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        <span>{{ session('success') }}</span>
                        <button @click="show = false" class="ml-auto text-green-500 hover:text-green-700">&times;</button>
                    </div>
                    @endif
                    @if(session('error'))
                    <div x-data="{ show: true }" x-show="show"
                        class="mb-4 flex items-start gap-3 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-5h2v2h-2v-2zm0-8h2v6h-2V5z" clip-rule="evenodd"/></svg>
                        <span>{{ session('error') }}</span>
                        <button @click="show = false" class="ml-auto text-red-500 hover:text-red-700">&times;</button>
                    </div>
                    @endif
                    @if(session('warning'))
                    <div x-data="{ show: true }" x-show="show"
                        class="mb-4 flex items-start gap-3 rounded-xl bg-yellow-50 border border-yellow-200 px-4 py-3 text-sm text-yellow-800">
                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-yellow-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span>{{ session('warning') }}</span>
                        <button @click="show = false" class="ml-auto text-yellow-500 hover:text-yellow-700">&times;</button>
                    </div>
                    @endif

                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
