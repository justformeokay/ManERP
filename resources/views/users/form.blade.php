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
            <h3 class="text-base font-semibold text-gray-900 mb-1">Password</h3>
            @if($isEdit)
                <p class="text-xs text-gray-500 mb-4">Leave blank to keep the current password.</p>
            @endif
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                        Password {{ $isEdit ? '' : '*' }}
                        @unless($isEdit) <span class="text-red-500">*</span> @endunless
                    </label>
                    <input type="password" id="password" name="password" {{ $isEdit ? '' : 'required' }}
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition @error('password') border-red-300 @enderror"
                        placeholder="{{ $isEdit ? '••••••••' : 'Minimum 8 characters' }}">
                    @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition"
                        placeholder="{{ $isEdit ? '••••••••' : 'Re-enter password' }}">
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
