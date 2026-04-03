<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SalaryStructureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id'        => ['required', 'exists:employees,id'],
            'basic_salary'       => ['required', 'numeric', 'min:1'],
            'fixed_allowance'    => ['nullable', 'numeric', 'min:0'],
            'meal_allowance'     => ['nullable', 'numeric', 'min:0'],
            'transport_allowance'=> ['nullable', 'numeric', 'min:0'],
            'overtime_rate'      => ['nullable', 'numeric', 'min:0'],
            'effective_date'     => ['required', 'date'],
        ];
    }
}
