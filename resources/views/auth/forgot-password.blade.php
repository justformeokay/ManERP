<x-guest-layout>
    <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-900">Reset password</h2>
        <p class="mt-2 text-gray-600">
            Masukkan email Anda dan kami akan mengirimkan link untuk reset password.
        </p>
    </div>

    {{-- Session Status --}}
    @if(session('status'))
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 p-4 text-sm text-green-700">
            <p class="font-medium">{{ session('status') }}</p>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        {{-- Email --}}
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition @error('email') border-red-500 bg-red-50 @enderror"
                placeholder="nama@perusahaan.com">
            @error('email')
                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        {{-- Submit Button --}}
        <button type="submit" class="w-full px-4 py-2.5 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
            Kirim link reset
        </button>
    </form>

    {{-- Back to Login --}}
    <p class="mt-8 text-center text-sm text-gray-600">
        <a href="{{ route('login') }}" class="font-medium text-blue-600 hover:text-blue-500">
            &larr; Kembali ke login
        </a>
    </p>
</x-guest-layout>
