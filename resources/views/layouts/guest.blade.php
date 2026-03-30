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
    <div class="min-h-screen flex flex-col lg:flex-row">
        {{-- Left Side - Branding --}}
        <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-blue-600 to-blue-800 px-12 py-12 flex-col justify-center">
            <div class="max-w-md">
                <div class="flex items-center gap-3 mb-8">
                    <div class="h-12 w-12 rounded-xl bg-white/20 flex items-center justify-center">
                        <svg class="h-7 w-7 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <span class="text-2xl font-bold text-white">ManERP</span>
                </div>
                <h1 class="text-4xl font-bold text-white mb-4">
                    Enterprise Resource Planning
                </h1>
                <p class="text-blue-100 text-lg leading-relaxed mb-8">
                    Kelola seluruh operasional bisnis Anda di satu tempat. Dari inventaris dan manufaktur hingga penjualan dan pembelian.
                </p>
                <div class="space-y-3 text-sm">
                    <div class="flex items-center gap-3 text-blue-100">
                        <svg class="h-5 w-5 text-blue-300" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                        <span>Manajemen Inventaris</span>
                    </div>
                    <div class="flex items-center gap-3 text-blue-100">
                        <svg class="h-5 w-5 text-blue-300" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                        <span>Produksi & Manufaktur</span>
                    </div>
                    <div class="flex items-center gap-3 text-blue-100">
                        <svg class="h-5 w-5 text-blue-300" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                        <span>Penjualan & Pembelian</span>
                    </div>
                    <div class="flex items-center gap-3 text-blue-100">
                        <svg class="h-5 w-5 text-blue-300" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                        <span>Laporan & Analitik</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Side - Auth Form --}}
        <div class="flex-1 flex flex-col justify-center items-center px-6 py-12 sm:px-8 lg:px-12">
            <div class="w-full max-w-sm">
                {{-- Mobile Logo --}}
                <div class="lg:hidden flex items-center justify-center gap-2 mb-8">
                    <div class="h-10 w-10 rounded-xl bg-blue-600 flex items-center justify-center">
                        <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <span class="text-2xl font-bold text-gray-900">ManERP</span>
                </div>

                {{ $slot }}
            </div>
        </div>
    </div>
</body>
</html>
