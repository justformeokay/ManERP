<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Login' }} — ManERP</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased bg-gray-50">
    <div class="min-h-full flex">
        {{-- Left Side - Branding --}}
        <div class="hidden lg:flex lg:flex-1 lg:flex-col lg:justify-center lg:bg-primary-600 lg:px-12 lg:py-12">
            <div class="mx-auto max-w-md">
                <div class="flex items-center gap-3 mb-8">
                    <div class="h-12 w-12 rounded-xl bg-white/20 flex items-center justify-center">
                        <svg class="h-7 w-7 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <span class="text-2xl font-bold text-white">ManERP</span>
                </div>
                <h1 class="text-3xl font-bold text-white mb-4">
                    Enterprise Resource Planning
                </h1>
                <p class="text-primary-100 text-lg leading-relaxed">
                    Manage your entire business operations in one place. From inventory and manufacturing to sales and purchasing.
                </p>
                <div class="mt-10 grid grid-cols-2 gap-4 text-sm">
                    <div class="flex items-center gap-2 text-primary-100">
                        <svg class="h-5 w-5 text-primary-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        Inventory Management
                    </div>
                    <div class="flex items-center gap-2 text-primary-100">
                        <svg class="h-5 w-5 text-primary-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        Manufacturing
                    </div>
                    <div class="flex items-center gap-2 text-primary-100">
                        <svg class="h-5 w-5 text-primary-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        Sales & Purchasing
                    </div>
                    <div class="flex items-center gap-2 text-primary-100">
                        <svg class="h-5 w-5 text-primary-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        Reports & Analytics
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Side - Auth Form --}}
        <div class="flex flex-1 flex-col justify-center px-4 py-12 sm:px-6 lg:flex-none lg:px-20 xl:px-24">
            <div class="mx-auto w-full max-w-sm lg:w-96">
                {{-- Mobile Logo --}}
                <div class="lg:hidden flex items-center justify-center gap-2 mb-8">
                    <div class="h-10 w-10 rounded-xl bg-primary-600 flex items-center justify-center">
                        <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <span class="text-xl font-bold text-gray-900">ManERP</span>
                </div>

                {{ $slot }}
            </div>
        </div>
    </div>
</body>
</html>
