<?php

namespace App\Http\Requests;

use App\Models\QcInspection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QcInspectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'inspection_type'    => ['required', Rule::in(QcInspection::inspectionTypeOptions())],
            'reference_type'     => ['nullable', 'string', Rule::in(['App\\Models\\ManufacturingOrder', 'App\\Models\\PurchaseOrder', 'App\\Models\\SalesOrder'])],
            'reference_id'       => ['nullable', 'integer'],
            'product_id'         => ['required', 'exists:products,id'],
            'warehouse_id'       => ['nullable', 'exists:warehouses,id'],
            'inspected_quantity' => ['required', 'numeric', 'min:0.01'],
            'notes'              => ['nullable', 'string', 'max:2000'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.qc_parameter_id' => ['required', 'exists:qc_parameters,id'],
            'items.*.min_value'       => ['nullable', 'numeric'],
            'items.*.max_value'       => ['nullable', 'numeric'],
        ];
    }
}
