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
                @if(isset($positions) && $positions->isNotEmpty())
                <select name="position"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                    <option value="">— {{ __('messages.select_position') }} —</option>
                    @foreach($positions as $pos)
                        <option value="{{ $pos->name }}" @selected(old('position', $e?->position) === $pos->name)>{{ $pos->name }}</option>
                    @endforeach
                </select>
                @else
                <input type="text" name="position" value="{{ old('position', $e?->position) }}"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">
                @endif
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.department') }}</label>
                @if(isset($departments) && $departments->isNotEmpty())
                <select name="department"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                    <option value="">— {{ __('messages.select_department') }} —</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->name }}" @selected(old('department', $e?->department) === $dept->name)>{{ $dept->name }}</option>
                    @endforeach
                </select>
                @else
                <input type="text" name="department" value="{{ old('department', $e?->department) }}"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">
                @endif
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
            @if(isset($shifts) && $shifts->isNotEmpty())
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.default_shift') }}</label>
                <select name="shift_id"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                    <option value="">— {{ __('messages.no_shift') }} —</option>
                    @foreach($shifts as $shift)
                        <option value="{{ $shift->id }}" @selected(old('shift_id', $e?->shift_id) == $shift->id)>
                            {{ $shift->name }} ({{ $shift->start_time }}–{{ $shift->end_time }})
                        </option>
                    @endforeach
                </select>
                @error('shift_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            @endif
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
            <div x-data="{
                open: false,
                search: '',
                selectedId: {{ old('bank_id', $e?->bank_id) ?? 'null' }},
                selectedLabel: '{{ old('bank_id', $e?->bank_id) ? ($banks->firstWhere('id', old('bank_id', $e?->bank_id))?->name . ' (' . $banks->firstWhere('id', old('bank_id', $e?->bank_id))?->code . ')') : '' }}',
                get filtered() {
                    if (this.search === '') return {{ Js::from($banks->map(fn($b) => ['id' => $b->id, 'label' => $b->name . ' (' . $b->code . ')'])) }};
                    const q = this.search.toLowerCase();
                    return {{ Js::from($banks->map(fn($b) => ['id' => $b->id, 'label' => $b->name . ' (' . $b->code . ')'])) }}.filter(b => b.label.toLowerCase().includes(q));
                },
                select(bank) { this.selectedId = bank.id; this.selectedLabel = bank.label; this.open = false; this.search = ''; },
                clear() { this.selectedId = null; this.selectedLabel = ''; this.search = ''; }
            }" @click.away="open = false" class="relative">
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.bank_name') }}</label>
                <input type="hidden" name="bank_id" :value="selectedId">
                <div class="relative">
                    <input type="text" readonly :value="selectedLabel" @click="open = !open"
                        placeholder="{{ __('messages.select_bank') }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm cursor-pointer focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">
                    <button type="button" x-show="selectedId" @click.stop="clear()" class="absolute right-8 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">&times;</button>
                    <svg class="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
                <div x-show="open" x-transition class="absolute z-50 mt-1 w-full rounded-xl bg-white shadow-lg ring-1 ring-gray-200 max-h-60 overflow-hidden">
                    <div class="p-2 border-b border-gray-100">
                        <input type="text" x-model="search" placeholder="{{ __('messages.search_bank') }}" @click.stop
                            class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                    </div>
                    <ul class="overflow-y-auto max-h-48">
                        <template x-for="bank in filtered" :key="bank.id">
                            <li @click="select(bank)" :class="{'bg-primary-50 text-primary-700': selectedId === bank.id}"
                                class="cursor-pointer px-3 py-2 text-sm hover:bg-gray-50" x-text="bank.label"></li>
                        </template>
                        <li x-show="filtered.length === 0" class="px-3 py-2 text-sm text-gray-400">{{ __('messages.no_bank_found') }}</li>
                    </ul>
                </div>
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
