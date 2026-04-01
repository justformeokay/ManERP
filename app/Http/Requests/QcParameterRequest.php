<?php

namespace App\Http\Requests;

use App\Models\QcParameter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QcParameterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'type'        => ['required', Rule::in(QcParameter::typeOptions())],
            'unit'        => ['nullable', 'string', 'max:50'],
            'min_value'   => ['nullable', 'numeric'],
            'max_value'   => ['nullable', 'numeric', 'gte:min_value'],
            'is_active'   => ['boolean'],
        ];
    }
}
