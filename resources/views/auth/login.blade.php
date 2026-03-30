<x-guest-layout>
    <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-900">Selamat datang kembali</h2>
        <p class="mt-2 text-gray-600">Masuk ke akun Anda untuk melanjutkan</p>
    </div>

    {{-- Error Messages --}}
    @if(session('error'))
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 p-4 text-sm text-red-700">
            <p class="font-medium">{{ session('error') }}</p>
        </div>
    @endif

    {{-- Session Status --}}
    @if(session('status'))
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 p-4 text-sm text-green-700">
            <p class="font-medium">{{ session('status') }}</p>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        {{-- Email --}}
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition @error('email') border-red-500 bg-red-50 @enderror"
                placeholder="nama@perusahaan.com">
            @error('email')
                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        {{-- Password --}}
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
            <input id="password" name="password" type="password" required autocomplete="current-password"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition @error('password') border-red-500 bg-red-50 @enderror"
                placeholder="Masukkan password Anda">
            @error('password')
                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        {{-- Remember Me & Forgot Password --}}
        <div class="flex items-center justify-between">
            <label for="remember_me" class="flex items-center cursor-pointer gap-2">
                <input id="remember_me" name="remember" type="checkbox" 
                    class="w-4 h-4 border-gray-300 rounded text-blue-600 focus:ring-blue-500">
                <span class="text-sm text-gray-700">Ingat saya</span>
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-sm font-medium text-blue-600 hover:text-blue-500">
                    Lupa password?
                </a>
            @endif
        </div>

        {{-- Submit Button --}}
        <button type="submit" class="w-full px-4 py-2.5 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
            Masuk
        </button>
    </form>

    {{-- Footer --}}
    <p class="mt-8 text-center text-sm text-gray-600">
        Perlu akses? Hubungi administrator sistem Anda.
    </p>
</x-guest-layout>
