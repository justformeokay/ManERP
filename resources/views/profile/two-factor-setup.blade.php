@extends('layouts.app')

@section('title', 'Setup Two-Factor Authentication')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <a href="{{ route('profile.edit') }}" class="hover:text-gray-700">Profile</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">Two-Factor Setup</span>
@endsection

@section('page-header')
    <h1 class="text-2xl font-bold text-gray-900">Setup Two-Factor Authentication</h1>
    <p class="mt-1 text-sm text-gray-500">Scan the QR code with your authenticator app (Google Authenticator, Authy, etc.)</p>
@endsection

@section('content')
    <div class="max-w-lg mx-auto">
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            {{-- QR Code --}}
            <div class="flex flex-col items-center gap-4 mb-6">
                <div class="p-4 bg-white rounded-xl border border-gray-200">
                    {!! $qrSvg !!}
                </div>
                <div class="text-center">
                    <p class="text-xs text-gray-500 mb-1">Or enter this key manually:</p>
                    <code class="px-3 py-1.5 bg-gray-100 rounded-lg text-sm font-mono text-gray-800 select-all">{{ $secret }}</code>
                </div>
            </div>

            {{-- Verify Code Form --}}
            <form method="POST" action="{{ route('two-factor.enable') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700 mb-2">
                        Enter the 6-digit code from your app
                    </label>
                    <input id="code" name="code" type="text" inputmode="numeric" pattern="[0-9]*"
                        maxlength="6" autofocus autocomplete="one-time-code" required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-center text-lg tracking-[0.3em] font-mono text-gray-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-200 focus:outline-none @error('code') border-red-500 bg-red-50 @enderror"
                        placeholder="000000">
                    @error('code')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex gap-3">
                    @include('components.button', ['label' => 'Verify & Enable', 'type' => 'primary', 'buttonType' => 'submit'])
                    @include('components.button', ['label' => 'Cancel', 'type' => 'ghost', 'href' => route('profile.edit')])
                </div>
            </form>
        </div>
    </div>
@endsection
