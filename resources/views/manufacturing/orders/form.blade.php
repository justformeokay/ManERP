@extends('layouts.app')

@php
    $isEdit = $order->exists;
    $pageTitle = $isEdit ? 'Edit Order ' . $order->number : 'New Manufacturing Order';
@endphp

@section('title', $pageTitle)

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <a href="{{ route('manufacturing.orders.index') }}" class="hover:text-gray-700">Work Orders</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ $pageTitle }}</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $isEdit ? __('messages.edit_order_title') . ' ' . $order->number : __('messages.create_order_title') }}</h1>
        <p class="mt-1 text-sm text-gray-500">
            {{ $isEdit ? __('messages.edit_order_subtitle') : __('messages.create_order_subtitle') }}
        </p>
    </div>
@endsection

@section('content')
    <form method="POST"
          action="{{ $isEdit ? route('manufacturing.orders.update', $order) : route('manufacturing.orders.store') }}"
          class="space-y-6">
        @csrf
        @if($isEdit) @method('PUT') @endif

        {{-- Order Details --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-4">{{ __('messages.order_details_title') }}</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                {{-- BOM --}}
                <div>
                    <label for="bom_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.bom_label') }} <span class="text-red-500">*</span></label>
                    <select name="bom_id" id="bom_id" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">
                        <option value="">{{ __('messages.bom_placeholder') }}</option>
                        @foreach($boms as $bom)
                            <option value="{{ $bom->id }}" @selected(old('bom_id', $order->bom_id) == $bom->id)>
                                {{ $bom->name }} — {{ $bom->product->name ?? '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('bom_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                {{-- Warehouse --}}
                <div>
                    <label for="warehouse_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.warehouse_label') }} <span class="text-red-500">*</span></label>
                    <select name="warehouse_id" id="warehouse_id" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">
                        <option value="">{{ __('messages.warehouse_placeholder') }}</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" @selected(old('warehouse_id', $order->warehouse_id) == $wh->id)>
                                {{ $wh->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('warehouse_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                {{-- Project (optional) --}}
                <div>
                    <label for="project_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.project_label') }}</label>
                    <select name="project_id" id="project_id"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">
                        <option value="">{{ __('messages.project_placeholder') }}</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}" @selected(old('project_id', $order->project_id) == $project->id)>
                                {{ $project->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('project_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                {{-- Planned Quantity --}}
                <div>
                    <label for="planned_quantity" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.planned_quantity_label') }} <span class="text-red-500">*</span></label>
                    <input type="number" name="planned_quantity" id="planned_quantity"
                        value="{{ old('planned_quantity', $order->planned_quantity) }}" min="0.01" step="0.01" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500" />
                    @error('planned_quantity') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                {{-- Status --}}
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.status_header') }} <span class="text-red-500">*</span></label>
                    <select name="status" id="status" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">
                        @foreach(\App\Models\ManufacturingOrder::statusOptions() as $s)
                            <option value="{{ $s }}" @selected(old('status', $order->status) === $s)>
                                {{ ucwords(str_replace('_', ' ', $s)) }}
                            </option>
                        @endforeach
                    </select>
                    @error('status') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                {{-- Priority --}}
                <div>
                    <label for="priority" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.priority_label') }} <span class="text-red-500">*</span></label>
                    <select name="priority" id="priority" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500">
                        @foreach(\App\Models\ManufacturingOrder::priorityOptions() as $p)
                            <option value="{{ $p }}" @selected(old('priority', $order->priority) === $p)>
                                {{ ucfirst($p) }}
                            </option>
                        @endforeach
                    </select>
                    @error('priority') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Schedule --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-4">{{ __('messages.schedule_card') }}</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="planned_start" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.start_date_label') }}</label>
                    <input type="date" name="planned_start" id="planned_start"
                        value="{{ old('planned_start', $order->planned_start?->format('Y-m-d')) }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500" />
                    @error('planned_start') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="planned_end" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.due_date_label') }}</label>
                    <input type="date" name="planned_end" id="planned_end"
                        value="{{ old('planned_end', $order->planned_end?->format('Y-m-d')) }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500" />
                    @error('planned_end') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Notes --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-4">{{ __('messages.notes_label') }}</h3>
            <textarea name="notes" id="notes" rows="3"
                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500"
                placeholder="{{ __('messages.notes_label') }}...">{{ old('notes', $order->notes) }}</textarea>
            @error('notes') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3">
            @include('components.button', ['label' => __('messages.cancel_btn'), 'type' => 'ghost', 'href' => route('manufacturing.orders.index')])
            @include('components.button', ['label' => $isEdit ? __('messages.update_order_btn') : __('messages.create_order_btn'), 'type' => 'primary', 'buttonType' => 'submit'])
        </div>
    </form>
@endsection
