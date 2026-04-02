@extends('layouts.app')

@section('title', __('messages.create_budget'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <a href="{{ route('accounting.budgets.index') }}" class="hover:text-gray-700">{{ __('messages.budgets_title') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('messages.create_budget') }}</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.create_budget') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('messages.create_budget_subtitle') }}</p>
    </div>
@endsection

@section('content')
    <form method="POST" action="{{ route('accounting.budgets.store') }}" x-data="budgetForm()">
        @csrf
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100 space-y-5 mb-6">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.name') }}</label>
                    <input type="text" name="name" id="name" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label for="fiscal_year" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.fiscal_year') }}</label>
                    <input type="number" name="fiscal_year" id="fiscal_year" value="{{ now()->year }}" min="2020" max="2099" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.description') }}</label>
                    <input type="text" name="description" id="description"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm">
                </div>
            </div>
        </div>

        {{-- Budget Lines --}}
        <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-700">{{ __('messages.budget_lines') }}</h3>
                <button type="button" @click="addLine()" class="rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100">
                    + {{ __('messages.add_line') }}
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <th class="px-4 py-3 w-56">{{ __('messages.account') }}</th>
                            @foreach(['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'] as $m)
                                <th class="px-2 py-3 text-right">{{ $m }}</th>
                            @endforeach
                            <th class="px-2 py-3 text-right">{{ __('messages.total') }}</th>
                            <th class="px-2 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(line, idx) in lines" :key="idx">
                            <tr class="border-b border-gray-50">
                                <td class="px-4 py-2">
                                    <select :name="`lines[${idx}][account_id]`" required class="w-full rounded-lg border border-gray-200 bg-gray-50 px-2 py-1.5 text-xs">
                                        <option value="">--</option>
                                        @foreach($accounts as $acc)
                                            <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                @foreach(['jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec'] as $m)
                                    <td class="px-1 py-2">
                                        <input type="number" :name="`lines[${idx}][{{ $m }}]`" x-model.number="line.{{ $m }}" step="0.01" min="0"
                                            class="w-20 rounded-lg border border-gray-200 bg-gray-50 px-2 py-1.5 text-xs text-right">
                                    </td>
                                @endforeach
                                <td class="px-2 py-2 text-right text-xs font-semibold text-gray-700" x-text="formatCurrency(lineTotal(line))"></td>
                                <td class="px-2 py-2">
                                    <button type="button" @click="lines.splice(idx, 1)" class="text-red-400 hover:text-red-600 text-xs">&times;</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex justify-end gap-2">
            @include('components.button', ['label' => __('messages.cancel'), 'type' => 'ghost', 'href' => route('accounting.budgets.index')])
            @include('components.button', ['label' => __('messages.save'), 'type' => 'primary', 'buttonType' => 'submit'])
        </div>
    </form>

    <script>
        function budgetForm() {
            return {
                lines: [{ jan:0,feb:0,mar:0,apr:0,may:0,jun:0,jul:0,aug:0,sep:0,oct:0,nov:0,dec:0 }],
                addLine() {
                    this.lines.push({ jan:0,feb:0,mar:0,apr:0,may:0,jun:0,jul:0,aug:0,sep:0,oct:0,nov:0,dec:0 });
                },
                lineTotal(line) {
                    return ['jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec']
                        .reduce((sum, m) => sum + (parseFloat(line[m]) || 0), 0);
                },
                formatCurrency(val) {
                    return new Intl.NumberFormat('id-ID', { style: 'decimal', minimumFractionDigits: 0 }).format(val);
                }
            };
        }
    </script>
@endsection
