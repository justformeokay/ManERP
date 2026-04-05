@extends('layouts.app')

@php
    $isEdit = $user->exists;
    $pageTitle = $isEdit ? 'Edit User' : 'New User';
@endphp

@section('title', $pageTitle)

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <a href="{{ route('settings.index') }}" class="hover:text-gray-700">Settings</a>
    <span class="mx-1">/</span>
    <a href="{{ route('settings.users.index') }}" class="hover:text-gray-700">Users</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ $pageTitle }}</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $pageTitle }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ $isEdit ? 'Update user account details.' : 'Create a new user account.' }}</p>
    </div>
@endsection

@section('content')
    <form method="POST"
          action="{{ $isEdit ? route('settings.users.update', $user) : route('settings.users.store') }}"
          class="space-y-6 max-w-2xl"
          x-data="userForm()">
        @csrf
        @if($isEdit) @method('PUT') @endif

        {{-- Account Details --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-4">Account Details</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition @error('name') border-red-300 @enderror"
                        placeholder="John Doe">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                    <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition @error('email') border-red-300 @enderror"
                        placeholder="user@company.com">
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone', $user->phone) }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition @error('phone') border-red-300 @enderror"
                        placeholder="+62 812 3456 7890">
                    @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Password --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center justify-between mb-1">
                <h3 class="text-base font-semibold text-gray-900">Password</h3>
                <button type="button" @click="generatePassword()"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-primary-50 px-3 py-1.5 text-xs font-medium text-primary-700 hover:bg-primary-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Auto Generate
                </button>
            </div>
            @if($isEdit)
                <p class="text-xs text-gray-500 mb-4">Leave blank to keep the current password.</p>
            @endif

            {{-- Generated password hint --}}
            <div x-show="generatedPassword" x-cloak class="mb-3 flex items-center gap-2 rounded-lg bg-amber-50 px-3 py-2 ring-1 ring-amber-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                </svg>
                <span class="text-xs text-amber-800">Generated:&nbsp;</span>
                <code class="flex-1 font-mono text-xs font-semibold text-amber-900" x-text="generatedPassword"></code>
                <button type="button" @click="copyPassword()" title="Copy"
                    class="ml-1 rounded p-1 text-amber-600 hover:bg-amber-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                </button>
                <span x-show="copied" x-cloak class="text-xs font-medium text-green-600">Copied!</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                        Password {{ $isEdit ? '' : '*' }}
                        @unless($isEdit) <span class="text-red-500">*</span> @endunless
                    </label>
                    <div class="relative">
                        <input :type="showPassword ? 'text' : 'password'" id="password" name="password" {{ $isEdit ? '' : 'required' }}
                            x-ref="passwordInput"
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 pr-10 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition @error('password') border-red-300 @enderror"
                            placeholder="{{ $isEdit ? '••••••••' : 'Minimum 8 characters' }}">
                        <button type="button" @click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600">
                            <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg x-show="showPassword" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 4.411m0 0L21 21" />
                            </svg>
                        </button>
                    </div>
                    @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                    <div class="relative">
                        <input :type="showPassword ? 'text' : 'password'" id="password_confirmation" name="password_confirmation"
                            x-ref="passwordConfirm"
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 pr-10 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition"
                            placeholder="{{ $isEdit ? '••••••••' : 'Re-enter password' }}">
                        <button type="button" @click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600">
                            <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg x-show="showPassword" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 4.411m0 0L21 21" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Role & Status --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-4">Role & Status</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role <span class="text-red-500">*</span></label>
                    <select id="role" name="role" required x-model="role"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 transition">
                        @foreach(\App\Models\User::roleOptions() as $role)
                            <option value="{{ $role }}" @selected(old('role', $user->role) === $role)>{{ ucfirst($role) }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Admin has full access. Staff has limited access based on permissions below.</p>
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                    <select id="status" name="status" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 transition">
                        @foreach(\App\Models\User::statusOptions() as $status)
                            <option value="{{ $status }}" @selected(old('status', $user->status) === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Inactive users cannot log in.</p>
                </div>
            </div>
        </div>

        {{-- Permissions (only for staff) --}}
        <div x-show="role === 'staff'" x-cloak class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base font-semibold text-gray-900">Permissions</h3>
                    <p class="mt-0.5 text-xs text-gray-500">Select which modules this staff member can access.</p>
                </div>
                <div class="flex gap-2">
                    <button type="button" @click="selectAllPerms()" class="text-xs text-blue-600 hover:text-blue-800 font-medium">Select All</button>
                    <span class="text-gray-300">|</span>
                    <button type="button" @click="clearAllPerms()" class="text-xs text-gray-500 hover:text-gray-700 font-medium">Clear All</button>
                </div>
            </div>

            @php
                $userPerms = old('permissions', $user->permissions ?? []);
                $actions = \App\Models\User::PERMISSION_ACTIONS;
            @endphp

            <div class="space-y-4">
                @foreach(\App\Models\User::PERMISSION_MODULES as $module => $label)
                    <div class="rounded-xl border border-gray-100 p-4">
                        <div class="flex items-center justify-between mb-2.5">
                            <h4 class="text-sm font-medium text-gray-800">{{ $label }}</h4>
                            <button type="button" @click="toggleModule('{{ $module }}')" class="text-xs text-blue-600 hover:text-blue-800">Toggle</button>
                        </div>
                        <div class="flex flex-wrap gap-x-6 gap-y-2">
                            @foreach($actions as $action)
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox"
                                           name="permissions[]"
                                           value="{{ $module }}.{{ $action }}"
                                           {{ in_array("{$module}.{$action}", $userPerms) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 h-4 w-4">
                                    <span class="text-sm text-gray-600 capitalize">{{ $action }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div x-show="role === 'admin'" x-cloak class="rounded-2xl bg-blue-50 p-4 ring-1 ring-blue-100">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5 text-blue-500 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <p class="text-sm text-blue-700">Admin users automatically have full access to all modules. No permission configuration needed.</p>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3">
            @include('components.button', ['label' => 'Cancel', 'type' => 'secondary', 'href' => route('settings.users.index')])
            @include('components.button', [
                'label' => $isEdit ? 'Update User' : 'Create User',
                'type' => 'primary',
                'buttonType' => 'submit',
                'icon' => '<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>',
            ])
        </div>
    </form>

@push('scripts')
<script>
    function userForm() {
        const actions = @json(\App\Models\User::PERMISSION_ACTIONS);
        const modules = @json(array_keys(\App\Models\User::PERMISSION_MODULES));
        return {
            role: '{{ old('role', $user->role) }}',
            showPassword: false,
            generatedPassword: '',
            copied: false,
            generatePassword() {
                const upper  = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                const lower  = 'abcdefghijklmnopqrstuvwxyz';
                const digits = '0123456789';
                const syms   = '!@#$%^&*()-_=+';
                const all    = upper + lower + digits + syms;
                // Guarantee at least one of each required class
                let pwd = [
                    upper[Math.floor(Math.random() * upper.length)],
                    lower[Math.floor(Math.random() * lower.length)],
                    digits[Math.floor(Math.random() * digits.length)],
                    syms[Math.floor(Math.random() * syms.length)],
                ];
                for (let i = pwd.length; i < 12; i++) {
                    pwd.push(all[Math.floor(Math.random() * all.length)]);
                }
                // Fisher-Yates shuffle
                for (let i = pwd.length - 1; i > 0; i--) {
                    const j = Math.floor(Math.random() * (i + 1));
                    [pwd[i], pwd[j]] = [pwd[j], pwd[i]];
                }
                const result = pwd.join('');
                this.generatedPassword = result;
                this.showPassword = true;
                this.copied = false;
                this.$refs.passwordInput.value = result;
                this.$refs.passwordConfirm.value = result;
            },
            copyPassword() {
                if (!this.generatedPassword) return;
                navigator.clipboard.writeText(this.generatedPassword).then(() => {
                    this.copied = true;
                    setTimeout(() => { this.copied = false; }, 2000);
                });
            },
            toggleModule(mod) {
                const boxes = this.$el.querySelectorAll(`input[value^="${mod}."]`);
                const allChecked = [...boxes].every(b => b.checked);
                boxes.forEach(b => b.checked = !allChecked);
            },
            selectAllPerms() {
                this.$el.querySelectorAll('input[name="permissions[]"]').forEach(b => b.checked = true);
            },
            clearAllPerms() {
                this.$el.querySelectorAll('input[name="permissions[]"]').forEach(b => b.checked = false);
            }
        };
    }
</script>
@endpush
@endsection
