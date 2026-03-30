<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $suppliers = Supplier::query()
            ->search($request->input('search'))
            ->when($request->input('status'), fn($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('suppliers.form', [
            'supplier' => new Supplier(['status' => 'active']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => ['nullable', 'email', 'max:255', Rule::unique('suppliers')],
            'phone'   => 'nullable|string|max:30',
            'company' => 'nullable|string|max:255',
            'tax_id'  => 'nullable|string|max:50',
            'address' => 'nullable|string|max:1000',
            'city'    => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'status'  => ['required', Rule::in(['active', 'inactive'])],
            'notes'   => 'nullable|string|max:2000',
        ]);

        Supplier::create($validated);

        return redirect()->route('suppliers.index')->with('success', 'Supplier created successfully.');
    }

    public function edit(Supplier $supplier)
    {
        return view('suppliers.form', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => ['nullable', 'email', 'max:255', Rule::unique('suppliers')->ignore($supplier->id)],
            'phone'   => 'nullable|string|max:30',
            'company' => 'nullable|string|max:255',
            'tax_id'  => 'nullable|string|max:50',
            'address' => 'nullable|string|max:1000',
            'city'    => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'status'  => ['required', Rule::in(['active', 'inactive'])],
            'notes'   => 'nullable|string|max:2000',
        ]);

        $supplier->update($validated);

        return redirect()->route('suppliers.index')->with('success', 'Supplier updated successfully.');
    }

    public function destroy(Supplier $supplier)
    {
        if ($supplier->purchaseOrders()->exists()) {
            return back()->with('error', 'Cannot delete supplier that has purchase orders.');
        }

        $supplier->delete();

        return redirect()->route('suppliers.index')->with('success', 'Supplier deleted successfully.');
    }
}
