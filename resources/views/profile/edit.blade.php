@extends('layouts.app')

@section('title', __('messages.profile_title'))

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- Page Header --}}
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.profile_title') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('messages.profile_subtitle') }}</p>
    </div>

    {{-- Flash Messages --}}
    @if(session('status') === 'profile-updated')
        <div class="flex items-center gap-3 rounded-xl bg-green-50 p-4 ring-1 ring-green-200" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition>
            <svg class="h-5 w-5 text-green-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="text-sm font-medium text-green-800">{{ __('messages.profile_updated_success') }}</span>
        </div>
    @endif
    @if(session('status') === 'password-updated')
        <div class="flex items-center gap-3 rounded-xl bg-green-50 p-4 ring-1 ring-green-200" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition>
            <svg class="h-5 w-5 text-green-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="text-sm font-medium text-green-800">{{ __('messages.profile_password_changed') }}</span>
        </div>
    @endif
    @if(in_array(session('status'), ['document-uploaded', 'document-deleted', 'change-requested', 'change-approved', 'change-rejected']))
        <div class="flex items-center gap-3 rounded-xl bg-green-50 p-4 ring-1 ring-green-200" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition>
            <svg class="h-5 w-5 text-green-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="text-sm font-medium text-green-800">{{ __('messages.ess_' . session('status')) }}</span>
        </div>
    @endif
    @if($errors->has('ess'))
        <div class="flex items-center gap-3 rounded-xl bg-red-50 p-4 ring-1 ring-red-200" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition>
            <svg class="h-5 w-5 text-red-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <span class="text-sm font-medium text-red-800">{{ $errors->first('ess') }}</span>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════
         SECTION 1: USER IDENTITY CARD
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5">
            {{-- Avatar --}}
            <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 text-3xl font-bold text-white shadow-lg">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>

            {{-- Info --}}
            <div class="flex-1 min-w-0">
                <h2 class="text-xl font-bold text-gray-900 truncate">{{ $user->name }}</h2>
                <p class="text-sm text-gray-500 truncate">{{ $user->email }}</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    {{-- Role Badge --}}
                    <span class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-semibold
                        {{ $user->role === 'admin' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-700' }}">
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        {{ ucfirst($user->role) }}
                    </span>
                    {{-- Status Badge --}}
                    <span class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-semibold
                        {{ $user->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ $user->status === 'active' ? 'bg-green-500' : 'bg-red-500' }}"></span>
                        {{ ucfirst($user->status) }}
                    </span>
                    {{-- 2FA Badge --}}
                    @if($user->two_factor_confirmed_at)
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            2FA {{ __('messages.profile_2fa_enabled') }}
                        </span>
                    @else
                        <a href="{{ route('two-factor.setup') }}" class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700 hover:bg-amber-200 transition">
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            2FA {{ __('messages.profile_2fa_disabled') }}
                        </a>
                    @endif
                </div>
            </div>

            {{-- Phone --}}
            @if($user->phone)
            <div class="text-right">
                <p class="text-xs text-gray-400 mb-0.5">{{ __('messages.phone') }}</p>
                <p class="text-sm font-medium text-gray-700">{{ $user->phone }}</p>
            </div>
            @endif
        </div>

        {{-- Professional Details from Employee --}}
        @if($user->employee)
        <div class="mt-5 pt-5 border-t border-gray-100">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-3">{{ __('messages.profile_professional_details') }}</p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <p class="text-xs text-gray-400">{{ __('messages.profile_position') }}</p>
                    <p class="text-sm font-medium text-gray-800">{{ $user->employee->position ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">{{ __('messages.profile_department') }}</p>
                    <p class="text-sm font-medium text-gray-800">{{ $user->employee->department ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">{{ __('messages.profile_join_date') }}</p>
                    <p class="text-sm font-medium text-gray-800">{{ $user->employee->join_date?->format('d M Y') ?? '—' }}</p>
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         SECTION 1B: MY DOCUMENTS (ESS)
    ═══════════════════════════════════════════════════════════════ --}}
    @if($user->employee)
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
        <div class="flex items-center gap-2 mb-5">
            <svg class="h-5 w-5 text-teal-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <h3 class="text-base font-semibold text-gray-900">{{ __('messages.ess_my_documents') }}</h3>
        </div>

        {{-- Existing Documents --}}
        @php $documents = $user->employee->documents->groupBy('type'); @endphp
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-5">
            @foreach(\App\Models\EmployeeDocument::ALLOWED_TYPES as $type)
                @php $doc = $documents->get($type)?->sortByDesc('created_at')->first(); @endphp
                <div class="rounded-xl p-4 ring-1 {{ $doc ? ($doc->status === 'verified' ? 'bg-green-50 ring-green-200' : ($doc->status === 'pending' ? 'bg-amber-50 ring-amber-200' : 'bg-red-50 ring-red-200')) : 'bg-gray-50 ring-gray-200' }}">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider {{ $doc ? ($doc->status === 'verified' ? 'text-green-700' : ($doc->status === 'pending' ? 'text-amber-700' : 'text-red-700')) : 'text-gray-500' }}">
                            {{ \App\Models\EmployeeDocument::TYPE_LABELS[$type] ?? strtoupper($type) }}
                        </span>
                        @if($doc)
                            <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold
                                {{ $doc->status === 'verified' ? 'bg-green-100 text-green-700' : ($doc->status === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $doc->status === 'verified' ? 'bg-green-500' : ($doc->status === 'pending' ? 'bg-amber-500' : 'bg-red-500') }}"></span>
                                {{ __('messages.ess_status_' . $doc->status) }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-bold text-gray-500">
                                {{ __('messages.ess_not_uploaded') }}
                            </span>
                        @endif
                    </div>

                    @if($doc)
                        <p class="text-xs text-gray-500 truncate mb-1" title="{{ $doc->original_name }}">{{ $doc->original_name }}</p>
                        <p class="text-[10px] text-gray-400 mb-2">{{ $doc->created_at->format('d M Y H:i') }} &middot; {{ number_format($doc->file_size / 1024, 0) }} KB</p>
                        @if($doc->status === 'rejected' && $doc->rejection_reason)
                            <p class="text-[10px] text-red-600 mb-2">{{ __('messages.ess_rejection_reason') }}: {{ $doc->rejection_reason }}</p>
                        @endif
                        <div class="flex gap-2">
                            <a href="{{ $doc->getTemporaryUrl() }}" target="_blank" class="text-[10px] font-semibold text-teal-700 hover:text-teal-900">{{ __('messages.ess_view_file') }}</a>
                            @unless(session('impersonator_id'))
                            <form method="POST" action="{{ route('profile.ess.document.delete', $doc) }}" onsubmit="return confirm('{{ __('messages.ess_delete_confirm') }}')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-[10px] font-semibold text-red-600 hover:text-red-800">{{ __('messages.delete') }}</button>
                            </form>
                            @endunless
                        </div>
                    @else
                        <p class="text-xs text-gray-400">{{ __('messages.ess_no_file_hint') }}</p>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Upload Form --}}
        @unless(session('impersonator_id'))
        <div class="rounded-xl bg-gray-50 p-4 ring-1 ring-gray-200">
            <p class="text-xs font-semibold text-gray-700 mb-3">{{ __('messages.ess_upload_document') }}</p>
            <form method="POST" action="{{ route('profile.ess.document.upload') }}" enctype="multipart/form-data">
                @csrf
                <div class="flex flex-col sm:flex-row items-start sm:items-end gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('messages.ess_document_type') }}</label>
                        <select name="document_type" required class="rounded-lg border-gray-200 bg-white px-3 py-2 text-xs text-gray-700 focus:border-teal-500 focus:ring-1 focus:ring-teal-500">
                            @foreach(\App\Models\EmployeeDocument::ALLOWED_TYPES as $t)
                                <option value="{{ $t }}">{{ \App\Models\EmployeeDocument::TYPE_LABELS[$t] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('messages.ess_select_file') }}</label>
                        <input type="file" name="document_file" required accept=".pdf,.jpg,.jpeg,.png"
                            class="block w-full text-xs text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-teal-50 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-teal-700 hover:file:bg-teal-100">
                    </div>
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-teal-600 px-4 py-2 text-xs font-semibold text-white hover:bg-teal-700 transition shadow-sm">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        {{ __('messages.ess_upload') }}
                    </button>
                </div>
                <p class="mt-2 text-[10px] text-gray-400">{{ __('messages.ess_upload_hint') }}</p>
                @error('document_file') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                @error('document_type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </form>
        </div>
        @endunless
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════
         SECTION 1C: PERSONAL DATA (ESS — Approval Required)
    ═══════════════════════════════════════════════════════════════ --}}
    @if($user->employee)
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
        <div class="flex items-center gap-2 mb-1">
            <svg class="h-5 w-5 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
            <h3 class="text-base font-semibold text-gray-900">{{ __('messages.ess_personal_data') }}</h3>
        </div>
        <p class="text-xs text-gray-500 mb-5">{{ __('messages.ess_personal_data_hint') }}</p>

        {{-- Pending Change Banner --}}
        @if($pendingChange)
        <div class="mb-4 rounded-xl bg-amber-50 p-4 ring-1 ring-amber-200">
            <div class="flex items-center gap-2 mb-2">
                <svg class="h-4 w-4 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-xs font-semibold text-amber-800">{{ __('messages.ess_pending_change_notice') }}</span>
            </div>
            <div class="space-y-1">
                @foreach($pendingChange->requested_changes as $field => $value)
                <div class="flex items-center gap-2 text-xs">
                    <span class="font-medium text-amber-700">{{ \App\Models\EmployeeDataChange::FIELD_LABELS[$field] ?? $field }}:</span>
                    <span class="text-gray-500 line-through">{{ $pendingChange->original_data[$field] ?? '—' }}</span>
                    <svg class="h-3 w-3 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    <span class="font-semibold text-amber-900">{{ $value }}</span>
                </div>
                @endforeach
            </div>
            <p class="mt-2 text-[10px] text-amber-600">{{ __('messages.ess_submitted') }}: {{ $pendingChange->created_at->diffForHumans() }}</p>
        </div>
        @endif

        {{-- Data Change Form --}}
        @unless(session('impersonator_id') || $pendingChange)
        <form method="POST" action="{{ route('profile.ess.data-change') }}">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('messages.phone') }}</label>
                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-orange-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-orange-500 transition @error('phone') border-red-300 @enderror">
                    @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('messages.ess_bank_name') }}</label>
                    <input type="text" name="bank_name" value="{{ old('bank_name', $user->employee->bank_name) }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-orange-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-orange-500 transition @error('bank_name') border-red-300 @enderror">
                    @error('bank_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('messages.ess_bank_account_number') }}</label>
                    <input type="text" name="bank_account_number" value="{{ old('bank_account_number', $user->employee->bank_account_number) }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-orange-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-orange-500 transition @error('bank_account_number') border-red-300 @enderror"
                        inputmode="numeric" pattern="[0-9]*">
                    @error('bank_account_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('messages.ess_bank_account_name') }}</label>
                    <input type="text" name="bank_account_name" value="{{ old('bank_account_name', $user->employee->bank_account_name) }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-orange-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-orange-500 transition @error('bank_account_name') border-red-300 @enderror">
                    @error('bank_account_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('messages.ess_bpjs_tk') }}</label>
                    <input type="text" name="bpjs_tk_number" value="{{ old('bpjs_tk_number', $user->employee->bpjs_tk_number) }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-orange-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-orange-500 transition">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('messages.ess_bpjs_kes') }}</label>
                    <input type="text" name="bpjs_kes_number" value="{{ old('bpjs_kes_number', $user->employee->bpjs_kes_number) }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-orange-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-orange-500 transition">
                </div>
            </div>

            <div class="mt-4 flex items-center justify-between">
                <p class="text-[10px] text-gray-400">{{ __('messages.ess_approval_notice') }}</p>
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-orange-700 transition">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    {{ __('messages.ess_submit_changes') }}
                </button>
            </div>
        </form>
        @endunless

        {{-- Change History --}}
        @if($changeHistory->isNotEmpty())
        <div class="mt-5 pt-5 border-t border-gray-100">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-3">{{ __('messages.ess_change_history') }}</p>
            <div class="space-y-2">
                @foreach($changeHistory as $change)
                <div class="flex items-center gap-3 rounded-lg p-2.5 text-xs
                    {{ $change->status === 'approved' ? 'bg-green-50' : ($change->status === 'pending' ? 'bg-amber-50' : 'bg-red-50') }}">
                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold
                        {{ $change->status === 'approved' ? 'bg-green-100 text-green-700' : ($change->status === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ $change->status === 'approved' ? 'bg-green-500' : ($change->status === 'pending' ? 'bg-amber-500' : 'bg-red-500') }}"></span>
                        {{ __('messages.ess_status_' . $change->status) }}
                    </span>
                    <span class="text-gray-600">
                        {{ collect($change->requested_changes)->keys()->map(fn($k) => \App\Models\EmployeeDataChange::FIELD_LABELS[$k] ?? $k)->implode(', ') }}
                    </span>
                    <span class="ml-auto text-[10px] text-gray-400">{{ $change->created_at->diffForHumans() }}</span>
                    @if($change->status === 'rejected' && $change->rejection_reason)
                        <span class="text-[10px] text-red-600" title="{{ $change->rejection_reason }}">{{ Str::limit($change->rejection_reason, 40) }}</span>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════
         SECTION 2: MY PERMISSIONS (Read-only)
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
        <div class="flex items-center gap-2 mb-5">
            <svg class="h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            <h3 class="text-base font-semibold text-gray-900">{{ __('messages.profile_my_permissions') }}</h3>
        </div>

        @if($user->isAdmin())
            <div class="flex items-center gap-2 rounded-xl bg-indigo-50 p-4 ring-1 ring-indigo-100">
                <svg class="h-5 w-5 text-indigo-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                <span class="text-sm font-medium text-indigo-800">{{ __('messages.profile_admin_full_access') }}</span>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach(\App\Models\User::PERMISSION_MODULES as $module => $label)
                    @php
                        $canView   = $user->hasPermission("{$module}.view");
                        $canCreate = $user->hasPermission("{$module}.create");
                        $canEdit   = $user->hasPermission("{$module}.edit");
                        $canDelete = $user->hasPermission("{$module}.delete");
                        $hasAny    = $canView || $canCreate || $canEdit || $canDelete;
                    @endphp
                    <div class="rounded-xl p-3 transition {{ $hasAny ? 'bg-white ring-1 ring-gray-200' : 'bg-gray-50 ring-1 ring-gray-100 opacity-50' }}">
                        <div class="flex items-center gap-2 mb-2">
                            @if($hasAny)
                                <div class="h-5 w-5 shrink-0 rounded-full bg-green-100 flex items-center justify-center">
                                    <svg class="h-3 w-3 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </div>
                            @else
                                <div class="h-5 w-5 shrink-0 rounded-full bg-gray-200 flex items-center justify-center">
                                    <svg class="h-3 w-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </div>
                            @endif
                            <span class="text-xs font-semibold {{ $hasAny ? 'text-gray-800' : 'text-gray-400' }}">{{ $label }}</span>
                        </div>
                        @if($hasAny)
                        <div class="flex flex-wrap gap-1 pl-7">
                            @foreach(['view' => $canView, 'create' => $canCreate, 'edit' => $canEdit, 'delete' => $canDelete] as $action => $allowed)
                                @if($allowed)
                                <span class="rounded-md px-1.5 py-0.5 text-[10px] font-semibold leading-none
                                    @if($action === 'view')    bg-blue-50 text-blue-600
                                    @elseif($action === 'create') bg-green-50 text-green-600
                                    @elseif($action === 'edit')   bg-amber-50 text-amber-600
                                    @else                         bg-red-50 text-red-500
                                    @endif">{{ $action }}</span>
                                @endif
                            @endforeach
                        </div>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Special Permissions --}}
            @php
                /** @var \App\Models\User $user */
                $specialPerms = collect(\App\Models\User::SPECIAL_PERMISSIONS)->filter(fn($label, $perm) => $user->hasPermission($perm));
            @endphp
            @if($specialPerms->isNotEmpty())
            <div class="mt-4 pt-4 border-t border-gray-100">
                <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-2">{{ __('messages.profile_special_permissions') }}</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($specialPerms as $perm => $label)
                        <span class="inline-flex items-center gap-1 rounded-full bg-purple-50 px-3 py-1 text-xs font-medium text-purple-700 ring-1 ring-purple-100">
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            {{ $label }}
                        </span>
                    @endforeach
                </div>
            </div>
            @endif
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         SECTION 3: UPDATE PROFILE FORM
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
        <div class="flex items-center gap-2 mb-5">
            <svg class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            <h3 class="text-base font-semibold text-gray-900">{{ __('messages.profile_update_info') }}</h3>
        </div>

        <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            @method('PATCH')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.name') }} <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500 transition @error('name') border-red-300 @enderror">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.email') }} <span class="text-red-500">*</span></label>
                    <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500 transition @error('email') border-red-300 @enderror">
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-5 flex justify-end">
                <button type="submit"
                    class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    {{ __('messages.profile_save_changes') }}
                </button>
            </div>
        </form>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         SECTION 4: SECURITY CENTER — Change Password
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
        <div class="flex items-center gap-2 mb-1">
            <svg class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            <h3 class="text-base font-semibold text-gray-900">{{ __('messages.profile_security_center') }}</h3>
        </div>
        <p class="text-xs text-gray-500 mb-5">{{ __('messages.profile_security_hint') }}</p>

        @if($user->password_changed_at)
        <div class="mb-4 flex items-center gap-2 rounded-xl bg-gray-50 px-4 py-2.5 ring-1 ring-gray-100 text-xs text-gray-500">
            <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ __('messages.profile_last_password_change') }}: <span class="font-medium text-gray-700">{{ $user->password_changed_at->diffForHumans() }}</span>
        </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.profile_current_password') }} <span class="text-red-500">*</span></label>
                    <input type="password" id="current_password" name="current_password" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500 transition @error('current_password', 'updatePassword') border-red-300 @enderror"
                        placeholder="••••••••">
                    @error('current_password', 'updatePassword') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.profile_new_password') }} <span class="text-red-500">*</span></label>
                    <input type="password" id="password" name="password" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500 transition @error('password', 'updatePassword') border-red-300 @enderror"
                        placeholder="{{ __('messages.profile_min_8_chars') }}">
                    @error('password', 'updatePassword') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.profile_confirm_new_password') }}</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500 transition"
                        placeholder="{{ __('messages.profile_re_enter') }}">
                </div>
            </div>

            <div class="mt-5 flex justify-end">
                <button type="submit"
                    class="inline-flex items-center gap-2 rounded-xl bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-gray-800 transition">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    {{ __('messages.profile_change_password') }}
                </button>
            </div>
        </form>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         SECTION 5: TWO-FACTOR AUTHENTICATION
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
        <div class="flex items-center gap-2 mb-1">
            <svg class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            <h3 class="text-base font-semibold text-gray-900">{{ __('messages.profile_2fa_title') }}</h3>
        </div>
        <p class="text-xs text-gray-500 mb-4">{{ __('messages.profile_2fa_desc') }}</p>

        @if($user->two_factor_confirmed_at)
            <div class="flex items-center justify-between rounded-xl bg-green-50 p-4 ring-1 ring-green-100">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                        <svg class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-green-800">{{ __('messages.profile_2fa_active') }}</p>
                        <p class="text-xs text-green-600">{{ __('messages.profile_2fa_active_desc') }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('two-factor.disable') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" onclick="return confirm('{{ __('messages.profile_2fa_disable_confirm') }}')"
                        class="rounded-lg bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-100 transition ring-1 ring-red-200">
                        {{ __('messages.profile_2fa_disable_btn') }}
                    </button>
                </form>
            </div>
        @else
            <div class="flex items-center justify-between rounded-xl bg-amber-50 p-4 ring-1 ring-amber-100">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-full bg-amber-100 flex items-center justify-center">
                        <svg class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-amber-800">{{ __('messages.profile_2fa_inactive') }}</p>
                        <p class="text-xs text-amber-600">{{ __('messages.profile_2fa_inactive_desc') }}</p>
                    </div>
                </div>
                <a href="{{ route('two-factor.setup') }}"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700 transition shadow-sm">
                    {{ __('messages.profile_2fa_enable_btn') }}
                </a>
            </div>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         SECTION 6: RECENT ACTIVITY LOG
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
        <div class="flex items-center gap-2 mb-5">
            <svg class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <h3 class="text-base font-semibold text-gray-900">{{ __('messages.profile_recent_activity') }}</h3>
        </div>

        @if($recentActivity->isEmpty())
            <div class="text-center py-8">
                <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <p class="mt-2 text-sm text-gray-400">{{ __('messages.profile_no_activity') }}</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach($recentActivity as $activity)
                <div class="flex items-start gap-3 rounded-xl bg-gray-50 p-3 ring-1 ring-gray-100">
                    {{-- Action Icon --}}
                    <div class="mt-0.5 h-8 w-8 shrink-0 rounded-lg flex items-center justify-center
                        @if($activity->action === 'create')    bg-green-100 text-green-600
                        @elseif($activity->action === 'update') bg-blue-100 text-blue-600
                        @elseif($activity->action === 'delete') bg-red-100 text-red-600
                        @else                                    bg-gray-100 text-gray-500
                        @endif">
                        @if($activity->action === 'create')
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        @elseif($activity->action === 'update')
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        @elseif($activity->action === 'delete')
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        @else
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @endif
                    </div>

                    {{-- Details --}}
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-700 leading-snug">{{ $activity->description }}</p>
                        <div class="mt-1 flex flex-wrap items-center gap-2">
                            <span class="rounded-md bg-gray-200 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-gray-600">{{ $activity->module }}</span>
                            <span class="rounded-md px-1.5 py-0.5 text-[10px] font-semibold uppercase
                                @if($activity->action === 'create')    bg-green-100 text-green-700
                                @elseif($activity->action === 'update') bg-blue-100 text-blue-700
                                @elseif($activity->action === 'delete') bg-red-100 text-red-700
                                @else                                    bg-gray-100 text-gray-600
                                @endif">{{ $activity->action }}</span>
                            <span class="text-[10px] text-gray-400">{{ $activity->ip_address }}</span>
                        </div>
                    </div>

                    {{-- Time --}}
                    <div class="shrink-0 text-right">
                        <p class="text-xs text-gray-400">{{ $activity->created_at->diffForHumans() }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         SECTION 7: DANGER ZONE
    ═══════════════════════════════════════════════════════════════ --}}
    @unless(session('impersonator_id'))
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-red-100" x-data="{ confirmDelete: false }">
        <div class="flex items-center gap-2 mb-1">
            <svg class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <h3 class="text-base font-semibold text-red-700">{{ __('messages.profile_danger_zone') }}</h3>
        </div>
        <p class="text-xs text-gray-500 mb-4">{{ __('messages.profile_danger_desc') }}</p>

        <div x-show="!confirmDelete">
            <button type="button" @click="confirmDelete = true"
                class="inline-flex items-center gap-2 rounded-xl bg-red-50 px-5 py-2.5 text-sm font-medium text-red-700 hover:bg-red-100 transition ring-1 ring-red-200">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                {{ __('messages.profile_delete_account') }}
            </button>
        </div>

        <div x-show="confirmDelete" x-cloak x-transition>
            <form method="POST" action="{{ route('profile.destroy') }}" class="rounded-xl bg-red-50 p-4 ring-1 ring-red-200">
                @csrf
                @method('DELETE')
                <p class="text-sm font-medium text-red-800 mb-3">{{ __('messages.profile_delete_confirm') }}</p>
                <div class="mb-3">
                    <label class="block text-xs font-medium text-red-700 mb-1">{{ __('messages.profile_current_password') }}</label>
                    <input type="password" name="password" required
                        class="w-full max-w-xs rounded-xl border border-red-200 bg-white px-4 py-2.5 text-sm text-gray-700 focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500 transition">
                    @error('password', 'userDeletion') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex gap-2">
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 transition">
                        {{ __('messages.profile_delete_confirm_btn') }}
                    </button>
                    <button type="button" @click="confirmDelete = false"
                        class="rounded-xl bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition ring-1 ring-gray-200">
                        {{ __('messages.cancel') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endunless

</div>
@endsection
