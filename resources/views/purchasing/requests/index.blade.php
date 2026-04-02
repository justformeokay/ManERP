@extends('layouts.app')

@section('title', __('messages.purchase_requests_title'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">{{ __('messages.dashboard') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('messages.purchase_requests_title') }}</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.purchase_requests_title') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('messages.purchase_requests_subtitle') }}</p>
    </div>
    <a href="{{ route('purchase-requests.create') }}"
       class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 transition">
        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
        {{ __('messages.new_pr_btn') }}
    </a>
@endsection

@section('content')
    {{-- Filters --}}
    <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100 mb-6">
        <form action="{{ route('purchase-requests.index') }}" method="GET" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="{{ __('messages.search_pr_placeholder') }}"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
            </div>
            <div class="w-40">
                <select name="status" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                    <option value="">{{ __('messages.all_status_filter') }}</option>
                    @foreach(['draft', 'pending', 'approved', 'rejected', 'converted'] as $s)
                        <option value="{{ $s }}" @selected(request('status') === $s)>{{ __('messages.pr_status_' . $s) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-36">
                <select name="priority" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                    <option value="">{{ __('messages.all_priorities') }}</option>
                    @foreach(['low', 'normal', 'high', 'urgent'] as $p)
                        <option value="{{ $p }}" @selected(request('priority') === $p)>{{ __('messages.priority_' . $p) }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-gray-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-gray-700 transition">
                {{ __('messages.filter_btn') }}
            </button>
        </form>
    </div>

    {{-- Table --}}
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50/60">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.pr_number_header') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.requested_by_header') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.priority_header') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.required_date_header') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.estimated_total_header') }}</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.status_table_header') }}</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.actions_table_header') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($requests as $pr)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-3 text-sm font-medium text-primary-600">
                                <a href="{{ route('purchase-requests.show', $pr) }}">{{ $pr->number }}</a>
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-900">{{ $pr->requester->name ?? '—' }}</td>
                            <td class="px-6 py-3 text-sm">
                                @php $pColors = ['low'=>'gray','normal'=>'blue','high'=>'amber','urgent'=>'red']; $pc = $pColors[$pr->priority] ?? 'gray'; @endphp
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium bg-{{ $pc }}-50 text-{{ $pc }}-700 ring-1 ring-{{ $pc }}-600/20">
                                    {{ __('messages.priority_' . $pr->priority) }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-600">{{ $pr->required_date?->format('d M Y') ?? '—' }}</td>
                            <td class="px-6 py-3 text-sm text-right font-medium text-gray-900">{{ format_currency($pr->getEstimatedTotal()) }}</td>
                            <td class="px-6 py-3 text-center">
                                @php $sColors = ['draft'=>'gray','pending'=>'amber','approved'=>'green','rejected'=>'red','converted'=>'blue']; $sc = $sColors[$pr->status] ?? 'gray'; @endphp
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium bg-{{ $sc }}-50 text-{{ $sc }}-700 ring-1 ring-{{ $sc }}-600/20">
                                    {{ __('messages.pr_status_' . $pr->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <a href="{{ route('purchase-requests.show', $pr) }}" class="rounded-lg p-1.5 text-gray-400 hover:text-primary-600 hover:bg-primary-50 transition" title="{{ __('messages.view_btn') }}">
                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    </a>
                                    @if(in_array($pr->status, ['draft', 'rejected']))
                                        <a href="{{ route('purchase-requests.edit', $pr) }}" class="rounded-lg p-1.5 text-gray-400 hover:text-amber-600 hover:bg-amber-50 transition" title="{{ __('messages.edit_btn') }}">
                                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <svg class="h-10 w-10 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                    <p class="text-sm text-gray-400">{{ __('messages.no_purchase_requests') }}</p>
                                    <a href="{{ route('purchase-requests.create') }}" class="text-sm font-semibold text-primary-600 hover:text-primary-800">+ {{ __('messages.create_first_pr') }}</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($requests->hasPages())
            <div class="px-6 py-3 border-t border-gray-100">{{ $requests->links() }}</div>
        @endif
    </div>
@endsection
