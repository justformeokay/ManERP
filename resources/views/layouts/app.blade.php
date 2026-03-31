<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

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
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
