<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — ManERP</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased" x-data="{ sidebarOpen: true, mobileSidebarOpen: false }">

    <div class="min-h-full">
        {{-- Mobile sidebar overlay --}}
        <div x-show="mobileSidebarOpen" x-cloak class="fixed inset-0 z-40 lg:hidden">
            <div class="fixed inset-0 bg-gray-900/50" @click="mobileSidebarOpen = false"></div>
            <div class="fixed inset-y-0 left-0 z-50 w-64">
                @include('partials.sidebar')
            </div>
        </div>

        {{-- Desktop sidebar --}}
        <aside
            class="fixed inset-y-0 left-0 z-30 hidden lg:flex transition-all duration-300"
            x-bind:class="sidebarOpen ? 'w-64' : 'w-20'"
        >
            @include('partials.sidebar')
        </aside>

        {{-- Main content --}}
        <div class="transition-all duration-300 lg:flex lg:flex-col lg:min-h-screen" x-bind:class="sidebarOpen ? 'lg:pl-64' : 'lg:pl-20'">
            @include('partials.navbar')

            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                {{-- Breadcrumbs --}}
                @hasSection('breadcrumbs')
                    <nav class="mb-4 text-sm text-gray-500">
                        @yield('breadcrumbs')
                    </nav>
                @endif

                {{-- Page header --}}
                @hasSection('page-header')
                    <div class="mb-6">
                        @yield('page-header')
                    </div>
                @endif

                {{-- Flash messages --}}
                @if(session('success'))
                    <div class="mb-4 rounded-xl bg-green-50 border border-green-200 p-4 text-sm text-green-700" x-data="{ show: true }" x-show="show">
                        <div class="flex items-center justify-between">
                            <span>{{ session('success') }}</span>
                            <button @click="show = false" class="text-green-500 hover:text-green-700">&times;</button>
                        </div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-4 rounded-xl bg-red-50 border border-red-200 p-4 text-sm text-red-700" x-data="{ show: true }" x-show="show">
                        <div class="flex items-center justify-between">
                            <span>{{ session('error') }}</span>
                            <button @click="show = false" class="text-red-500 hover:text-red-700">&times;</button>
                        </div>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    {{-- Modal container --}}
    <div id="modal-container"></div>

    @stack('scripts')
</body>
</html>
