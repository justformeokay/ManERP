<?php

namespace App\Http\Requests;

use App\Models\ManufacturingOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ManufacturingOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bom_id'           => ['required', 'exists:bill_of_materials,id'],
            'warehouse_id'     => ['required', 'exists:warehouses,id'],
            'project_id'       => ['nullable', 'exists:projects,id'],
            'planned_quantity' => ['required', 'numeric', 'min:0.01'],
            'status'           => ['required', Rule::in(ManufacturingOrder::statusOptions())],
            'priority'         => ['required', Rule::in(ManufacturingOrder::priorityOptions())],
            'planned_start'    => ['nullable', 'date'],
            'planned_end'      => ['nullable', 'date', 'after_or_equal:planned_start'],
            'notes'            => ['nullable', 'string', 'max:2000'],
        ];
    }
}
