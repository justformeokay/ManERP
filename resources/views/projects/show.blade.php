@extends('layouts.app')

@section('title', $project->name)

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">{{ __('messages.dashboard') }}</a>
    <span class="mx-1">/</span>
    <a href="{{ route('projects.index') }}" class="hover:text-gray-700">{{ __('messages.projects_heading') }}</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ $project->code }}</span>
@endsection

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-gray-900">{{ $project->name }}</h1>
                @php
                    $colors = \App\Models\Project::statusColors();
                    $typeColors = \App\Models\Project::typeColors();
                @endphp
                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $typeColors[$project->type] ?? $typeColors['sales'] }}">
                    {{ $project->typeLabel() }}
                </span>
                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $colors[$project->status] ?? $colors['draft'] }}">
                    {{ ucwords(str_replace('_', ' ', $project->status)) }}
                </span>
            </div>
            <p class="mt-1 text-sm text-gray-500">{{ $project->code }}</p>
        </div>
        <div class="flex gap-2">
            @include('components.button', [
                'label' => __('messages.edit'),
                'type' => 'secondary',
                'href' => route('projects.edit', $project),
                'icon' => '<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>',
            ])
            @include('components.button', ['label' => __('messages.back'), 'type' => 'ghost', 'href' => route('projects.index')])
        </div>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        {{-- Main Info --}}
        <div class="xl:col-span-2 space-y-6">
            {{-- Description --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-base font-semibold text-gray-900 mb-3">{{ __('messages.description') }}</h3>
                <p class="text-sm text-gray-700 leading-relaxed">
                    {{ $project->description ?: __('messages.no_description') }}
                </p>
            </div>

            {{-- Timeline --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-base font-semibold text-gray-900 mb-4">{{ __('messages.timeline_and_budget') }}</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.start_date') }}</p>
                        <p class="mt-1 text-sm font-medium text-gray-900">{{ $project->start_date?->format('M d, Y') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.end_date') }}</p>
                        <p class="mt-1 text-sm font-medium text-gray-900">{{ $project->end_date?->format('M d, Y') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.budget') }}</p>
                        <p class="mt-1 text-sm font-medium text-gray-900">{{ $project->budget ? format_currency($project->budget) : '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.estimated_budget') }}</p>
                        <p class="mt-1 text-sm font-medium text-gray-900">{{ $project->estimated_budget ? format_currency($project->estimated_budget) : '—' }}</p>
                    </div>
                </div>

                @if($project->start_date && $project->end_date)
                    @php
                        $totalDays = $project->start_date->diffInDays($project->end_date);
                        $elapsed = $project->start_date->diffInDays(now());
                        $progress = $totalDays > 0 ? min(100, round(($elapsed / $totalDays) * 100)) : 0;
                    @endphp
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <div class="flex items-center justify-between text-xs text-gray-500 mb-2">
                            <span>{{ __('messages.timeline_progress') }}</span>
                            <span>{{ $progress }}%</span>
                        </div>
                        <div class="h-2 rounded-full bg-gray-100">
                            <div class="h-2 rounded-full {{ $progress >= 100 ? 'bg-red-500' : 'bg-primary-500' }}" style="width: {{ $progress }}%"></div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Notes --}}
            @if($project->notes)
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <h3 class="text-base font-semibold text-gray-900 mb-3">{{ __('messages.notes') }}</h3>
                    <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-line">{{ $project->notes }}</p>
                </div>
            @endif

            {{-- Purchasing / Biaya Pengadaan --}}
            <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">{{ __('messages.po_purchasing_tab') }}</h3>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $purchaseOrders->count() }} {{ __('messages.po_linked_orders') }}</p>
                        </div>
                        @if($project->budget && $project->budget > 0)
                            @php
                                $utilization = min(100, round(($totalPurchased / $project->budget) * 100, 1));
                                $utilizationColor = $utilization > 90 ? 'red' : ($utilization > 70 ? 'amber' : 'green');
                            @endphp
                            <div class="text-right">
                                <p class="text-xs text-gray-500">{{ __('messages.po_budget_utilization') }}</p>
                                <p class="text-lg font-bold text-{{ $utilizationColor }}-600">{{ $utilization }}%</p>
                            </div>
                        @endif
                    </div>

                    {{-- Summary Cards --}}
                    <div class="mt-4 grid grid-cols-2 gap-4">
                        <div class="rounded-xl bg-gray-50 p-3">
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.po_total_spent') }}</p>
                            <p class="mt-1 text-lg font-bold text-gray-900">{{ format_currency($totalPurchased) }}</p>
                        </div>
                        <div class="rounded-xl bg-gray-50 p-3">
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.budget') }}</p>
                            <p class="mt-1 text-lg font-bold text-gray-900">{{ $project->budget ? format_currency($project->budget) : '—' }}</p>
                        </div>
                    </div>

                    @if($project->budget && $project->budget > 0)
                        <div class="mt-3">
                            <div class="h-2 rounded-full bg-gray-100">
                                <div class="h-2 rounded-full bg-{{ $utilizationColor }}-500" style="width: {{ min($utilization, 100) }}%"></div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- PO Table --}}
                @if($purchaseOrders->isNotEmpty())
                    @php $poStatusColors = \App\Models\PurchaseOrder::statusColors(); @endphp
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead class="bg-gray-50/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.po_number_header') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.supplier_header') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.date_header') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.status') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('messages.total_header') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($purchaseOrders as $po)
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="px-6 py-3">
                                            <a href="{{ route('purchasing.show', $po) }}" class="text-sm font-semibold text-primary-700 hover:text-primary-800">
                                                {{ $po->number }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-3 text-sm text-gray-700">{{ $po->supplier->name ?? '—' }}</td>
                                        <td class="px-6 py-3 text-sm text-gray-700">{{ $po->order_date->format('M d, Y') }}</td>
                                        <td class="px-6 py-3">
                                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $poStatusColors[$po->status] ?? '' }}">
                                                {{ __('messages.po_status_' . $po->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-3 text-right text-sm font-semibold text-gray-900">{{ format_currency($po->total) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="px-6 py-10 text-center">
                        <svg class="mx-auto h-10 w-10 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" />
                        </svg>
                        <p class="mt-2 text-sm text-gray-500">{{ __('messages.po_no_linked_orders') }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Sidebar Info --}}
        <div class="space-y-6">
            {{-- Client --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-base font-semibold text-gray-900 mb-4">{{ __('messages.client_column') }}</h3>
                @if($project->client)
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-700 font-semibold text-sm">
                            {{ strtoupper(substr($project->client->name, 0, 2)) }}
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $project->client->name }}</p>
                            <p class="text-xs text-gray-500">{{ $project->client->company ?? $project->client->code }}</p>
                        </div>
                    </div>
                    @if($project->client->email)
                        <div class="mt-3 pt-3 border-t border-gray-100">
                            <p class="text-xs text-gray-500">{{ $project->client->email }}</p>
                            @if($project->client->phone)
                                <p class="text-xs text-gray-500">{{ $project->client->phone }}</p>
                            @endif
                        </div>
                    @endif
                @elseif($project->isCapex())
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-700">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ __('messages.internal_project') }}</p>
                            <p class="text-xs text-gray-500">{{ __('messages.project_type_capex_desc') }}</p>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-gray-400">{{ __('messages.no_client_assigned') }}</p>
                @endif
            </div>

            {{-- Details --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="text-base font-semibold text-gray-900 mb-4">{{ __('messages.project_details') }}</h3>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">{{ __('messages.code') }}</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ $project->code }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">{{ __('messages.type_column') }}</dt>
                        <dd>
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $typeColors[$project->type] ?? $typeColors['sales'] }}">
                                {{ $project->typeLabel() }}
                            </span>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">{{ __('messages.status') }}</dt>
                        <dd>
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $colors[$project->status] ?? $colors['draft'] }}">
                                {{ ucwords(str_replace('_', ' ', $project->status)) }}
                            </span>
                        </dd>
                    </div>
                    @if($project->manager)
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">{{ __('messages.project_manager') }}</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ $project->manager->name }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">{{ __('messages.created_at') }}</dt>
                        <dd class="text-sm text-gray-900">{{ $project->created_at->format('M d, Y') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">{{ __('messages.updated_at') }}</dt>
                        <dd class="text-sm text-gray-900">{{ $project->updated_at->format('M d, Y') }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Danger Zone --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-red-100">
                <h3 class="text-base font-semibold text-red-600 mb-3">{{ __('messages.danger_zone') }}</h3>
                <p class="text-sm text-gray-500 mb-4">{{ __('messages.delete_project_warning') }}</p>
                <form method="POST" action="{{ route('projects.destroy', $project) }}"
                      onsubmit="return confirm('{{ __('messages.delete_project_confirm', ['name' => $project->name]) }}')">
                    @csrf @method('DELETE')
                    @include('components.button', ['label' => __('messages.delete_project'), 'type' => 'danger', 'buttonType' => 'submit'])
                </form>
            </div>
        </div>
    </div>
@endsection
