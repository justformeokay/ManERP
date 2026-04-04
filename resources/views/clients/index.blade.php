@extends('layouts.app')

@section('title', __('messages.crm_clients_title'))

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">{{ __('messages.dashboard') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ __('messages.clients_heading') }}</span>
@endsection

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.clients_heading') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.clients_subtitle') }}</p>
        </div>
        @include('components.button', [
            'label' => __('messages.add_client'),
            'type' => 'primary',
            'href' => route('clients.create'),
            'icon' => '<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>',
        ])
    </div>
@endsection

@section('content')
    {{-- Filters --}}
    <div class="mb-6 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
        <form method="GET" action="{{ route('clients.index') }}" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <input
                    type="search"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="{{ __('messages.search_clients_placeholder') }}"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 transition"
                />
            </div>
            <select name="status" class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                <option value="">{{ __('messages.all_status') }}</option>
                <option value="active" @selected(request('status') === 'active')>{{ __('messages.active') }}</option>
                <option value="inactive" @selected(request('status') === 'inactive')>{{ __('messages.inactive') }}</option>
            </select>
            <select name="type" class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                <option value="">{{ __('messages.all_types') }}</option>
                <option value="customer" @selected(request('type') === 'customer')>{{ __('messages.customer') }}</option>
                <option value="lead" @selected(request('type') === 'lead')>{{ __('messages.lead') }}</option>
                <option value="prospect" @selected(request('type') === 'prospect')>{{ __('messages.prospect') }}</option>
            </select>
            <div class="flex gap-2">
                @include('components.button', ['label' => __('messages.filter'), 'type' => 'secondary', 'buttonType' => 'submit'])
                @if(request()->hasAny(['search', 'status', 'type']))
                    @include('components.button', ['label' => __('messages.clear'), 'type' => 'ghost', 'href' => route('clients.index')])
                @endif
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.client_column') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.contact') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.type') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.credit_limit') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.status') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.created') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($clients as $client)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-700 font-semibold text-sm">
                                        {{ strtoupper(substr($client->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ $client->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $client->company ?? $client->code }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm text-gray-700">{{ $client->email ?? '—' }}</p>
                                <p class="text-xs text-gray-500">{{ $client->phone ?? '' }}</p>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @php
                                    $typeBadge = match($client->type) {
                                        'customer' => 'bg-primary-50 text-primary-700 ring-primary-600/20',
                                        'lead'     => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                                        'prospect' => 'bg-purple-50 text-purple-700 ring-purple-600/20',
                                        default    => 'bg-gray-50 text-gray-700 ring-gray-600/20',
                                    };
                                @endphp
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $typeBadge }}">
                                    {{ __('messages.' . $client->type) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                @if($client->is_credit_blocked)
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium bg-red-50 text-red-700 ring-1 ring-red-600/20">{{ __('messages.blocked') }}</span>
                                @elseif((float) $client->credit_limit > 0)
                                    <span class="text-sm font-medium text-gray-900">{{ number_format((float) $client->credit_limit, 0, ',', '.') }}</span>
                                @else
                                    <span class="text-xs text-gray-400">{{ __('messages.unlimited') }}</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1
                                    {{ $client->status === 'active'
                                        ? 'bg-green-50 text-green-700 ring-green-600/20'
                                        : 'bg-gray-100 text-gray-600 ring-gray-500/20' }}">
                                    {{ $client->status === 'active' ? __('messages.active') : __('messages.inactive') }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                {{ $client->created_at->format('M d, Y') }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm space-x-1">
                                <a href="{{ route('clients.edit', $client) }}"
                                   class="inline-flex items-center rounded-lg px-2.5 py-1.5 text-xs font-medium text-primary-700 bg-primary-50 hover:bg-primary-100 transition">
                                    {{ __('messages.edit') }}
                                </a>
                                <form method="POST" action="{{ route('clients.destroy', $client) }}" class="inline" onsubmit="return confirm('{{ __('messages.delete_client_confirm', ['name' => $client->name]) }}')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="inline-flex items-center rounded-lg px-2.5 py-1.5 text-xs font-medium text-red-700 bg-red-50 hover:bg-red-100 transition">
                                        {{ __('messages.delete') }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <svg class="h-12 w-12 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-500">{{ __('messages.no_clients_found') }}</p>
                                    <a href="{{ route('clients.create') }}" class="mt-3 text-sm font-medium text-primary-600 hover:text-primary-700">
                                        {{ __('messages.add_your_first_client') }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($clients->hasPages())
            <div class="border-t border-gray-100 px-6 py-4">
                {{ $clients->links() }}
            </div>
        @endif
    </div>
@endsection
