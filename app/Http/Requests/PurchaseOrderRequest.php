<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id'        => ['required', 'exists:suppliers,id'],
            'warehouse_id'       => ['required', 'exists:warehouses,id'],
            'project_id'         => ['nullable', 'exists:projects,id'],
            'order_date'         => ['required', 'date'],
            'expected_date'      => ['nullable', 'date', 'after_or_equal:order_date'],
            'tax_amount'         => ['nullable', 'numeric', 'min:0'],
            'notes'              => ['nullable', 'string', 'max:2000'],

            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity'   => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'At least one item is required.',
            'items.min'      => 'At least one item is required.',
        ];
    }
}
