<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">Two-Factor Authentication</h2>
        <p class="mt-1 text-sm text-gray-600">
            Add additional security to your account using time-based one-time passwords (TOTP).
        </p>
    </header>

    @if(session('status') === 'two-factor-enabled')
        <div class="mt-4 rounded-lg bg-green-50 border border-green-200 p-4">
            <p class="text-sm font-medium text-green-800">Two-factor authentication has been enabled.</p>
            @if(session('recovery_codes'))
                <p class="mt-2 text-sm text-green-700">
                    Store these recovery codes in a secure location. They can be used to access your account if you lose your authenticator device.
                </p>
                <div class="mt-3 grid grid-cols-2 gap-1 font-mono text-sm bg-white rounded-lg p-3 border border-green-200">
                    @foreach(session('recovery_codes') as $code)
                        <div class="text-gray-800">{{ $code }}</div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    @if(session('status') === 'two-factor-disabled')
        <div class="mt-4 rounded-lg bg-yellow-50 border border-yellow-200 p-4 text-sm text-yellow-800">
            Two-factor authentication has been disabled.
        </div>
    @endif

    <div class="mt-4">
        @if(auth()->user()->two_factor_confirmed_at)
            {{-- MFA is enabled — show disable form --}}
            <div class="flex items-center gap-3 mb-4">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-3 py-1 text-xs font-semibold text-green-700 ring-1 ring-green-600/20">
                    <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" /></svg>
                    Enabled
                </span>
                <span class="text-sm text-gray-500">since {{ auth()->user()->two_factor_confirmed_at->format('M d, Y') }}</span>
            </div>

            <form method="POST" action="{{ route('two-factor.disable') }}" x-data="{ showConfirm: false }">
                @csrf
                @method('DELETE')
                <div x-show="!showConfirm">
                    <button type="button" @click="showConfirm = true"
                        class="inline-flex items-center rounded-lg px-4 py-2 text-sm font-medium text-red-700 bg-red-50 hover:bg-red-100 ring-1 ring-red-600/20 transition">
                        Disable Two-Factor
                    </button>
                </div>
                <div x-show="showConfirm" x-cloak class="space-y-3">
                    <div>
                        <label for="disable_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm your password</label>
                        <input id="disable_password" name="password" type="password" required
                            class="w-full max-w-xs px-4 py-2 border border-gray-300 rounded-lg text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 @error('password') border-red-500 @enderror">
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex gap-2">
                        @include('components.button', ['label' => 'Confirm Disable', 'type' => 'danger', 'buttonType' => 'submit'])
                        <button type="button" @click="showConfirm = false"
                            class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition">Cancel</button>
                    </div>
                </div>
            </form>
        @else
            {{-- MFA not enabled — show setup link --}}
            <a href="{{ route('two-factor.setup') }}"
                class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 transition">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                Enable Two-Factor Authentication
            </a>
        @endif
    </div>
</section>
