<div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
    <h3 class="text-base font-semibold text-gray-900 mb-4">Account Details</h3>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Account Code <span class="text-red-500">*</span></label>
            <input type="text" name="code" id="code" value="{{ old('code', $account->code ?? '') }}" maxlength="20"
                placeholder="e.g. 1100"
                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500"
                required>
            @error('code') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Account Name <span class="text-red-500">*</span></label>
            <input type="text" name="name" id="name" value="{{ old('name', $account->name ?? '') }}" maxlength="255"
                placeholder="e.g. Cash & Bank"
                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500"
                required>
            @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Account Type <span class="text-red-500">*</span></label>
            <select name="type" id="type"
                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                required>
                <option value="">Select type</option>
                @foreach(\App\Models\ChartOfAccount::typeOptions() as $t)
                    <option value="{{ $t }}" @selected(old('type', $account->type ?? '') === $t)>{{ ucfirst($t) }}</option>
                @endforeach
            </select>
            @error('type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="parent_id" class="block text-sm font-medium text-gray-700 mb-1">Parent Account</label>
            <select name="parent_id" id="parent_id"
                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                <option value="">None (Top-level)</option>
                @foreach($parents as $parent)
                    <option value="{{ $parent->id }}" @selected(old('parent_id', $account->parent_id ?? '') == $parent->id)>
                        {{ $parent->code }} — {{ $parent->name }}
                    </option>
                @endforeach
            </select>
            @error('parent_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="sm:col-span-2">
            <label class="inline-flex items-center gap-2">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1"
                    @checked(old('is_active', $account->is_active ?? true))
                    class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                <span class="text-sm text-gray-700">Active</span>
            </label>
        </div>
    </div>
</div>
