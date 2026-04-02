@extends('layouts.app')

@section('title', __('messages.create_template'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <a href="{{ route('accounting.journals.templates') }}" class="hover:text-gray-700">{{ __('messages.journal_templates_title') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('messages.create_template') }}</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.create_template') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('messages.create_template_subtitle') }}</p>
    </div>
@endsection

@section('content')
    <form method="POST" action="{{ route('accounting.journals.templates.store') }}" x-data="templateForm()" class="max-w-3xl space-y-6">
        @csrf

        {{-- Template Info --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-4">{{ __('messages.template_info') }}</h3>
            <div class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.template_name') }} <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                           placeholder="{{ __('messages.template_name_placeholder') }}"
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.description') }}</label>
                    <textarea name="description" id="description" rows="2"
                              class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">{{ old('description') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Template Lines --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900">{{ __('messages.template_lines') }}</h3>
                <button type="button" @click="addLine()"
                    class="inline-flex items-center gap-1 rounded-lg bg-primary-50 px-3 py-1.5 text-xs font-medium text-primary-700 hover:bg-primary-100 transition">
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ __('messages.add_line') }}
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th class="pb-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-500" style="min-width:250px">{{ __('messages.account') }}</th>
                            <th class="pb-2 text-right text-xs font-semibold uppercase tracking-wider text-gray-500" style="min-width:130px">{{ __('messages.debit') }}</th>
                            <th class="pb-2 text-right text-xs font-semibold uppercase tracking-wider text-gray-500" style="min-width:130px">{{ __('messages.credit') }}</th>
                            <th class="pb-2 w-12"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(line, index) in lines" :key="index">
                            <tr class="border-t border-gray-100">
                                <td class="py-2 pr-3">
                                    <select :name="'items['+index+'][account_id]'" x-model="line.account_id"
                                        @change="updateAccountInfo(index)"
                                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                                        required>
                                        <option value="">{{ __('messages.select_account') }}...</option>
                                        @foreach($accounts as $acc)
                                            <option value="{{ $acc->id }}" data-code="{{ $acc->code }}" data-name="{{ $acc->name }}">{{ $acc->code }} — {{ $acc->name }}</option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" :name="'items['+index+'][account_code]'" x-model="line.account_code">
                                    <input type="hidden" :name="'items['+index+'][account_name]'" x-model="line.account_name">
                                </td>
                                <td class="py-2 px-3">
                                    <input type="number" :name="'items['+index+'][debit]'" x-model.number="line.debit"
                                        step="0.01" min="0" placeholder="0.00"
                                        @input="if(line.debit > 0) line.credit = 0"
                                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-right text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                                </td>
                                <td class="py-2 px-3">
                                    <input type="number" :name="'items['+index+'][credit]'" x-model.number="line.credit"
                                        step="0.01" min="0" placeholder="0.00"
                                        @input="if(line.credit > 0) line.debit = 0"
                                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-right text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                                </td>
                                <td class="py-2 pl-2">
                                    <button type="button" @click="removeLine(index)" x-show="lines.length > 2"
                                        class="text-red-400 hover:text-red-600 transition">
                                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            @include('components.button', ['label' => __('messages.cancel'), 'type' => 'ghost', 'href' => route('accounting.journals.templates')])
            <button type="submit"
                class="inline-flex items-center rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition">
                {{ __('messages.save_template') }}
            </button>
        </div>
    </form>

    <script>
        function templateForm() {
            return {
                lines: [
                    { account_id: '', account_code: '', account_name: '', debit: 0, credit: 0 },
                    { account_id: '', account_code: '', account_name: '', debit: 0, credit: 0 },
                ],
                addLine() {
                    this.lines.push({ account_id: '', account_code: '', account_name: '', debit: 0, credit: 0 });
                },
                removeLine(index) {
                    if (this.lines.length > 2) this.lines.splice(index, 1);
                },
                updateAccountInfo(index) {
                    const select = document.querySelector(`[name="items[${index}][account_id]"]`);
                    const opt = select.options[select.selectedIndex];
                    this.lines[index].account_code = opt.dataset.code || '';
                    this.lines[index].account_name = opt.dataset.name || '';
                }
            };
        }
    </script>
@endsection
