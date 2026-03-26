<?php

namespace App\Http\Requests;

use App\Models\StockMovement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id'     => ['required', 'exists:products,id'],
            'warehouse_id'   => ['required', 'exists:warehouses,id'],
            'type'           => ['required', Rule::in(StockMovement::typeOptions())],
            'quantity'       => ['required', 'numeric', 'min:0.01'],
            'reference_type' => ['nullable', 'string', 'max:50'],
            'notes'          => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.min' => 'Quantity must be greater than zero.',
        ];
    }
}
