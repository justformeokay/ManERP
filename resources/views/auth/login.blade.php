<x-guest-layout>
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Welcome back</h2>
        <p class="mt-2 text-sm text-gray-600">Sign in to your account to continue</p>
    </div>

    {{-- Error Messages --}}
    @if(session('error'))
        <div class="mt-4 rounded-xl bg-red-50 border border-red-200 p-4 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    {{-- Session Status --}}
    @if(session('status'))
        <div class="mt-4 rounded-xl bg-green-50 border border-green-200 p-4 text-sm text-green-700">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-6">
        @csrf

        {{-- Email --}}
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email address</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700 placeholder-gray-400 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500/20 transition @error('email') border-red-300 bg-red-50 @enderror"
                placeholder="you@company.com">
            @error('email')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Password --}}
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
            <input id="password" name="password" type="password" required autocomplete="current-password"
                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700 placeholder-gray-400 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500/20 transition @error('password') border-red-300 bg-red-50 @enderror"
                placeholder="Enter your password">
            @error('password')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Remember Me & Forgot Password --}}
        <div class="flex items-center justify-between">
            <label for="remember_me" class="flex items-center cursor-pointer">
                <input id="remember_me" name="remember" type="checkbox" 
                    class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                <span class="ml-2 text-sm text-gray-600">Remember me</span>
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-sm font-medium text-primary-600 hover:text-primary-500">
                    Forgot password?
                </a>
            @endif
        </div>

        {{-- Submit Button --}}
        <button type="submit" class="w-full rounded-xl bg-primary-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition">
            Sign in
        </button>
    </form>

    {{-- Footer --}}
    <p class="mt-8 text-center text-sm text-gray-500">
        Need access? Contact your system administrator.
    </p>
</x-guest-layout>
