<x-guest-layout>
    <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-900">Password Expired</h2>
        <p class="mt-2 text-gray-600">Your password has expired. Please create a new password to continue.</p>
    </div>

    @if(session('warning'))
        <div class="mb-4 rounded-lg bg-yellow-50 border border-yellow-200 p-4 text-sm text-yellow-700">
            <p class="font-medium">{{ session('warning') }}</p>
        </div>
    @endif

    <form method="POST" action="{{ route('password.force-change.update') }}" class="space-y-5">
        @csrf

        <div>
            <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
            <input id="current_password" name="current_password" type="password" required autofocus autocomplete="current-password"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition @error('current_password') border-red-500 bg-red-50 @enderror">
            @error('current_password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
            <input id="password" name="password" type="password" required autocomplete="new-password"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition @error('password') border-red-500 bg-red-50 @enderror">
            @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-xs text-gray-500">Minimum 8 characters, with uppercase, lowercase, number, and symbol.</p>
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition">
        </div>

        <button type="submit"
            class="w-full flex justify-center py-2.5 px-4 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg text-sm transition focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            Update Password
        </button>
    </form>
</x-guest-layout>
