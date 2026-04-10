<?php

namespace App\Http\Requests;

use App\Models\PurchaseOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isProject = in_array($this->input('purchase_type'), ['project_sales', 'project_capex']);

        return [
            'purchase_type'      => ['required', Rule::in(PurchaseOrder::purchaseTypeOptions())],
            'supplier_id'        => ['required', 'exists:suppliers,id'],
            'warehouse_id'       => ['required', 'exists:warehouses,id'],
            'department_id'      => ['required', 'exists:departments,id'],
            'project_id'         => [$isProject ? 'required' : 'nullable', 'nullable', 'exists:projects,id'],
            'project_sig'        => [$isProject ? 'required' : 'nullable', 'nullable', 'string'],
            'priority'           => ['required', Rule::in(PurchaseOrder::priorityOptions())],
            'order_date'         => ['required', 'date'],
            'expected_date'      => ['nullable', 'date', 'after_or_equal:order_date'],
            'tax_amount'         => ['nullable', 'numeric', 'min:0'],
            'justification'      => ['nullable', 'string', 'max:2000'],
            'notes'              => ['nullable', 'string', 'max:2000'],

            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity'   => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Clear project_id for operational purchases
        if ($this->input('purchase_type') === 'operational') {
            $this->merge(['project_id' => null, 'project_sig' => null]);
        }
    }

    public function messages(): array
    {
        return [
            'items.required' => 'At least one item is required.',
            'items.min'      => 'At least one item is required.',
        ];
    }
}
