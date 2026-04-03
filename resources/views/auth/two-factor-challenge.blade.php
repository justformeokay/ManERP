<x-guest-layout>
    <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-900">Two-Factor Authentication</h2>
        <p class="mt-2 text-gray-600">Enter the 6-digit code from your authenticator app to continue.</p>
    </div>

    @if($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 p-4 text-sm text-red-700">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('two-factor.challenge.verify') }}" x-data="{ mode: 'totp' }" class="space-y-5">
        @csrf

        {{-- TOTP Code --}}
        <div x-show="mode === 'totp'">
            <label for="code" class="block text-sm font-medium text-gray-700 mb-2">Authentication Code</label>
            <input id="code" name="code" type="text" inputmode="numeric" pattern="[0-9]*"
                maxlength="6" autofocus autocomplete="one-time-code"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-center text-lg tracking-[0.3em] font-mono text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition"
                placeholder="000000">
        </div>

        {{-- Recovery Code --}}
        <div x-show="mode === 'recovery'" x-cloak>
            <label for="recovery_code" class="block text-sm font-medium text-gray-700 mb-2">Recovery Code</label>
            <input id="recovery_code" name="recovery_code" type="text"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm font-mono text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition"
                placeholder="XXXXX-XXXXX">
        </div>

        <button type="submit"
            class="w-full flex justify-center py-2.5 px-4 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg text-sm transition focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            Verify
        </button>

        <div class="text-center">
            <button type="button" x-show="mode === 'totp'" @click="mode = 'recovery'" class="text-sm text-blue-600 hover:text-blue-700">
                Use a recovery code
            </button>
            <button type="button" x-show="mode === 'recovery'" @click="mode = 'totp'" x-cloak class="text-sm text-blue-600 hover:text-blue-700">
                Use authenticator code
            </button>
        </div>
    </form>

    <div class="mt-4 text-center">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm text-gray-500 hover:text-gray-700">Sign out</button>
        </form>
    </div>
</x-guest-layout>
