@extends('layouts.app')

@section('title', isset($supplier) ? 'Edit Supplier' : 'Add Supplier')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-1">/</span>
    <a href="{{ route('suppliers.index') }}" class="hover:text-gray-700">Suppliers</a>
    <span class="mx-1">/</span>
    <span class="text-gray-900 font-medium">{{ isset($supplier) ? 'Edit' : 'Create' }}</span>
@endsection

@section('page-header')
    <h1 class="text-2xl font-bold text-gray-900">{{ isset($supplier) ? 'Edit Supplier' : 'New Supplier' }}</h1>
    <p class="mt-1 text-sm text-gray-500">{{ isset($supplier) ? 'Update supplier information.' : 'Add a new supplier to the system.' }}</p>
@endsection

@section('content')
    <form method="POST"
          action="{{ isset($supplier) ? route('suppliers.update', $supplier) : route('suppliers.store') }}"
          class="space-y-6">
        @csrf
        @isset($supplier)
            @method('PUT')
        @endisset

        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-5">Basic Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                {{-- Name --}}
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" required
                           value="{{ old('name', $supplier->name ?? '') }}"
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition @error('name') border-red-300 ring-1 ring-red-200 @enderror"
                           placeholder="Supplier name" />
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Company --}}
                <div>
                    <label for="company" class="block text-sm font-medium text-gray-700 mb-1.5">Company</label>
                    <input type="text" name="company" id="company"
                           value="{{ old('company', $supplier->company ?? '') }}"
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition @error('company') border-red-300 ring-1 ring-red-200 @enderror"
                           placeholder="Company name" />
                    @error('company') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                    <input type="email" name="email" id="email"
                           value="{{ old('email', $supplier->email ?? '') }}"
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition @error('email') border-red-300 ring-1 ring-red-200 @enderror"
                           placeholder="supplier@example.com" />
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Phone --}}
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1.5">Phone</label>
                    <input type="text" name="phone" id="phone"
                           value="{{ old('phone', $supplier->phone ?? '') }}"
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition @error('phone') border-red-300 ring-1 ring-red-200 @enderror"
                           placeholder="+62 812 3456 7890" />
                    @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Tax ID --}}
                <div>
                    <label for="tax_id" class="block text-sm font-medium text-gray-700 mb-1.5">Tax ID / NPWP</label>
                    <input type="text" name="tax_id" id="tax_id"
                           value="{{ old('tax_id', $supplier->tax_id ?? '') }}"
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition @error('tax_id') border-red-300 ring-1 ring-red-200 @enderror"
                           placeholder="00.000.000.0-000.000" />
                    @error('tax_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Status --}}
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1.5">Status</label>
                    <select name="status" id="status"
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition @error('status') border-red-300 ring-1 ring-red-200 @enderror">
                        <option value="active" @selected(old('status', $supplier->status ?? 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $supplier->status ?? 'active') === 'inactive')>Inactive</option>
                    </select>
                    @error('status') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-5">Address</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                {{-- Address --}}
                <div class="md:col-span-2">
                    <label for="address" class="block text-sm font-medium text-gray-700 mb-1.5">Street Address</label>
                    <textarea name="address" id="address" rows="3"
                              class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition @error('address') border-red-300 ring-1 ring-red-200 @enderror"
                              placeholder="Full street address">{{ old('address', $supplier->address ?? '') }}</textarea>
                    @error('address') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- City --}}
                <div>
                    <label for="city" class="block text-sm font-medium text-gray-700 mb-1.5">City</label>
                    <input type="text" name="city" id="city"
                           value="{{ old('city', $supplier->city ?? '') }}"
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition @error('city') border-red-300 ring-1 ring-red-200 @enderror"
                           placeholder="City" />
                    @error('city') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Country --}}
                <div>
                    <label for="country" class="block text-sm font-medium text-gray-700 mb-1.5">Country</label>
                    <input type="text" name="country" id="country"
                           value="{{ old('country', $supplier->country ?? 'Indonesia') }}"
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition @error('country') border-red-300 ring-1 ring-red-200 @enderror"
                           placeholder="Country" />
                    @error('country') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Notes --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-5">Additional Notes</h3>
            <textarea name="notes" id="notes" rows="3"
                      class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition @error('notes') border-red-300 ring-1 ring-red-200 @enderror"
                      placeholder="Any notes about this supplier...">{{ old('notes', $supplier->notes ?? '') }}</textarea>
            @error('notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Submit --}}
        <div class="flex items-center justify-end gap-3">
            @include('components.button', ['label' => 'Cancel', 'type' => 'secondary', 'href' => route('suppliers.index')])
            @include('components.button', ['label' => isset($supplier) ? 'Update Supplier' : 'Create Supplier', 'type' => 'primary', 'buttonType' => 'submit'])
        </div>
    </form>
@endsection
