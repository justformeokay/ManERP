<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PayrollGenerateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'month' => ['required', 'integer', 'between:1,12'],
            'year'  => ['required', 'integer', 'min:2020', 'max:2099'],
            // Per-employee overrides
            'overrides'                         => ['nullable', 'array'],
            'overrides.*.overtime_hours'         => ['nullable', 'numeric', 'min:0'],
            'overrides.*.loan_deduction'         => ['nullable', 'numeric', 'min:0'],
            'overrides.*.absence_deduction'      => ['nullable', 'numeric', 'min:0'],
            'overrides.*.other_deductions'       => ['nullable', 'numeric', 'min:0'],
            'overrides.*.other_earnings'         => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
