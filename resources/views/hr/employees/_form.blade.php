@php $e = $employee ?? null; @endphp

<div class="space-y-6">
    {{-- Link to User Account --}}
    @if(isset($availableUsers))
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
        <h3 class="text-lg font-semibold text-gray-900 mb-1">{{ __('messages.link_user_account') }}</h3>
        <p class="text-xs text-gray-500 mb-4">{{ __('messages.link_user_desc') }}</p>
        <div class="max-w-md">
            <select name="user_id"
                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                <option value="">— {{ __('messages.no_linked_user') }} —</option>
                @foreach($availableUsers as $u)
                    <option value="{{ $u->id }}" @selected(old('user_id', $e?->user_id) == $u->id)>
                        {{ $u->name }} ({{ $u->email }}) — {{ ucfirst($u->role) }}
                    </option>
                @endforeach
            </select>
            @error('user_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        @if($e?->user)
            <div class="mt-3 flex items-center gap-2 rounded-lg bg-green-50 px-3 py-2 ring-1 ring-green-200">
                <svg class="h-4 w-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                <span class="text-xs text-green-800">{{ __('messages.currently_linked_to') }}: <strong>{{ $e->user->name }}</strong> ({{ $e->user->email }})</span>
            </div>
        @endif
    </div>
    @endif

    {{-- Personal Data --}}
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('messages.personal_data') }}</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">NIK <span class="text-red-500">*</span></label>
                <input type="text" name="nik" value="{{ old('nik', $e?->nik) }}" required maxlength="20"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">
                @error('nik') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.name') }} <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $e?->name) }}" required
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">
                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.position') }}</label>
                <input type="text" name="position" value="{{ old('position', $e?->position) }}"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.department') }}</label>
                <input type="text" name="department" value="{{ old('department', $e?->department) }}"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.join_date') }} <span class="text-red-500">*</span></label>
                <input type="date" name="join_date" value="{{ old('join_date', $e?->join_date?->format('Y-m-d')) }}" required
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">
                @error('join_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                <select name="status" required
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                    @foreach(\App\Models\Employee::statusOptions() as $s)
                        <option value="{{ $s }}" @selected(old('status', $e?->status ?? 'active') === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Tax & BPJS --}}
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('messages.tax_bpjs_info') }}</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">NPWP</label>
                <input type="text" name="npwp" value="{{ old('npwp', $e?->npwp) }}" maxlength="30"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">PTKP <span class="text-red-500">*</span></label>
                <select name="ptkp_status" required
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                    @foreach(\App\Models\Employee::PTKP_OPTIONS as $code => $label)
                        <option value="{{ $code }}" @selected(old('ptkp_status', $e?->ptkp_status) === $code)>{{ $code }} — {{ $label }}</option>
                    @endforeach
                </select>
                @error('ptkp_status') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.ter_category') }} <span class="text-red-500">*</span></label>
                <select name="ter_category" required
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                    <option value="A" @selected(old('ter_category', $e?->ter_category) === 'A')>A — TK/0, TK/1</option>
                    <option value="B" @selected(old('ter_category', $e?->ter_category) === 'B')>B — K/0, K/1</option>
                    <option value="C" @selected(old('ter_category', $e?->ter_category) === 'C')>C — K/2, K/3</option>
                </select>
                @error('ter_category') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">No. BPJS Ketenagakerjaan</label>
                <input type="text" name="bpjs_tk_number" value="{{ old('bpjs_tk_number', $e?->bpjs_tk_number) }}" maxlength="30"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">No. BPJS Kesehatan</label>
                <input type="text" name="bpjs_kes_number" value="{{ old('bpjs_kes_number', $e?->bpjs_kes_number) }}" maxlength="30"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">
            </div>
        </div>
    </div>

    {{-- Bank Account --}}
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('messages.bank_info') }}</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.bank_name') }}</label>
                <input type="text" name="bank_name" value="{{ old('bank_name', $e?->bank_name) }}"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.account_number') }}</label>
                <input type="text" name="bank_account_number" value="{{ old('bank_account_number', $e?->bank_account_number) }}" maxlength="30"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.account_name') }}</label>
                <input type="text" name="bank_account_name" value="{{ old('bank_account_name', $e?->bank_account_name) }}"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">
            </div>
        </div>
    </div>

    {{-- Actions --}}
    <div class="flex items-center gap-3 justify-end">
        @include('components.button', ['label' => __('messages.cancel'), 'type' => 'ghost', 'href' => route('hr.employees.index')])
        @include('components.button', ['label' => __('messages.save'), 'type' => 'primary', 'buttonType' => 'submit'])
    </div>
</div>
