@extends('layouts.app')

@section('title', 'New Journal Entry')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <a href="{{ route('accounting.journals.index') }}" class="hover:text-gray-700">Journal Entries</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">New Entry</span>
@endsection

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">New Journal Entry</h1>
            <p class="mt-1 text-sm text-gray-500">Create a manual double-entry journal entry</p>
        </div>
        @include('components.button', ['label' => '← Back', 'type' => 'ghost', 'href' => route('accounting.journals.index')])
    </div>
@endsection

@section('content')
    <form method="POST" action="{{ route('accounting.journals.store') }}" x-data="journalForm()" class="space-y-6">
        @csrf

        {{-- Header --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-4">Entry Details</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Date <span class="text-red-500">*</span></label>
                    <input type="date" name="date" id="date" value="{{ old('date', now()->format('Y-m-d')) }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500"
                        required>
                    @error('date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-1">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description <span class="text-red-500">*</span></label>
                    <input type="text" name="description" id="description" value="{{ old('description') }}"
                        placeholder="e.g. Monthly rent payment"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500"
                        required>
                    @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Line Items --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900">Journal Lines</h3>
                <button type="button" @click="addLine()"
                    class="inline-flex items-center gap-1 rounded-lg bg-primary-50 px-3 py-1.5 text-xs font-medium text-primary-700 hover:bg-primary-100 transition">
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Line
                </button>
            </div>

            @error('items')
                <div class="mb-4 rounded-xl bg-red-50 p-3 text-sm text-red-700">{{ $message }}</div>
            @enderror

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th class="pb-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-500" style="min-width:250px">Account</th>
                            <th class="pb-2 text-right text-xs font-semibold uppercase tracking-wider text-gray-500" style="min-width:150px">Debit</th>
                            <th class="pb-2 text-right text-xs font-semibold uppercase tracking-wider text-gray-500" style="min-width:150px">Credit</th>
                            <th class="pb-2 w-12"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(line, index) in lines" :key="index">
                            <tr class="border-t border-gray-100">
                                <td class="py-2 pr-3">
                                    <select :name="'items['+index+'][account_id]'" x-model="line.account_id"
                                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                                        required>
                                        <option value="">Select account...</option>
                                        @foreach($accounts as $acc)
                                            <option value="{{ $acc->id }}">{{ $acc->code }} — {{ $acc->name }}</option>
                                        @endforeach
                                    </select>
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
                    <tfoot>
                        <tr class="border-t-2 border-gray-200">
                            <td class="py-3 pr-3 text-right text-sm font-semibold text-gray-900">Total</td>
                            <td class="py-3 px-3 text-right text-sm font-bold text-gray-900" x-text="totalDebit.toFixed(2)"></td>
                            <td class="py-3 px-3 text-right text-sm font-bold text-gray-900" x-text="totalCredit.toFixed(2)"></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="pt-1">
                                <div x-show="Math.abs(totalDebit - totalCredit) > 0.01" class="rounded-xl bg-red-50 p-2 text-xs text-red-700 text-center">
                                    Entry is <strong>not balanced</strong>. Difference: <span x-text="Math.abs(totalDebit - totalCredit).toFixed(2)"></span>
                                </div>
                                <div x-show="Math.abs(totalDebit - totalCredit) <= 0.01 && totalDebit > 0" class="rounded-xl bg-green-50 p-2 text-xs text-green-700 text-center">
                                    Entry is <strong>balanced</strong>.
                                </div>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            @include('components.button', ['label' => 'Cancel', 'type' => 'ghost', 'href' => route('accounting.journals.index')])
            <button type="submit" :disabled="Math.abs(totalDebit - totalCredit) > 0.01 || totalDebit === 0"
                class="inline-flex items-center rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition">
                Create Journal Entry
            </button>
        </div>
    </form>

    <script>
        function journalForm() {
            return {
                lines: [
                    { account_id: '', debit: 0, credit: 0 },
                    { account_id: '', debit: 0, credit: 0 },
                ],
                get totalDebit() {
                    return this.lines.reduce((sum, l) => sum + (parseFloat(l.debit) || 0), 0);
                },
                get totalCredit() {
                    return this.lines.reduce((sum, l) => sum + (parseFloat(l.credit) || 0), 0);
                },
                addLine() {
                    this.lines.push({ account_id: '', debit: 0, credit: 0 });
                },
                removeLine(index) {
                    if (this.lines.length > 2) {
                        this.lines.splice(index, 1);
                    }
                }
            };
        }
    </script>
@endsection
