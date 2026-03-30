<x-guest-layout>
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Reset password</h2>
        <p class="mt-2 text-sm text-gray-600">
            Enter your email address and we'll send you a link to reset your password.
        </p>
    </div>

    {{-- Session Status --}}
    @if(session('status'))
        <div class="mt-4 rounded-xl bg-green-50 border border-green-200 p-4 text-sm text-green-700">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-6">
        @csrf

        {{-- Email --}}
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email address</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700 placeholder-gray-400 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500/20 transition @error('email') border-red-300 bg-red-50 @enderror"
                placeholder="you@company.com">
            @error('email')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Submit Button --}}
        <button type="submit" class="w-full rounded-xl bg-primary-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition">
            Send reset link
        </button>
    </form>

    {{-- Back to Login --}}
    <p class="mt-8 text-center text-sm text-gray-500">
        <a href="{{ route('login') }}" class="font-medium text-primary-600 hover:text-primary-500">
            &larr; Back to login
        </a>
    </p>
</x-guest-layout>
