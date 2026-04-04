<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('messages.license_expired') }} — ManERP</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center p-4">

    <div class="w-full max-w-md text-center">
        <div class="rounded-2xl bg-white p-8 shadow-sm ring-1 ring-gray-100">
            {{-- Icon --}}
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-100">
                <svg class="h-8 w-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
            </div>

            <h1 class="mt-4 text-xl font-bold text-gray-900">{{ __('messages.license_expired') }}</h1>
            <p class="mt-2 text-sm text-gray-500">{{ __('messages.license_expired_description') }}</p>

            @if($license)
            <div class="mt-4 rounded-xl bg-gray-50 p-4 text-left text-sm">
                <div class="flex justify-between py-1">
                    <span class="text-gray-500">{{ __('messages.license_plan') }}</span>
                    <span class="font-medium text-gray-900">{{ $license->plan_name }}</span>
                </div>
                @if($license->expires_at)
                <div class="flex justify-between py-1">
                    <span class="text-gray-500">{{ __('messages.license_expired_on') }}</span>
                    <span class="font-medium text-red-600">{{ $license->expires_at->format('M d, Y') }}</span>
                </div>
                @endif
            </div>
            @endif

            <div class="mt-6 space-y-3">
                <a href="mailto:support@manerp.com"
                   class="block w-full rounded-xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-700 transition">
                    {{ __('messages.license_contact_support') }}
                </a>

                @auth
                    @if(auth()->user()->isAdmin())
                    <a href="{{ route('license.activate') }}"
                       class="block w-full rounded-xl bg-gray-100 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-200 transition">
                        {{ __('messages.license_activate') }}
                    </a>
                    @endif
                @endauth

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-sm text-gray-400 hover:text-gray-600 transition">
                        {{ __('messages.logout') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

</body>
</html>
