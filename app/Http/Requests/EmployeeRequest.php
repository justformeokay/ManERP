<?php

namespace App\Http\Requests;

use App\Models\Employee;
use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $employeeId = $this->route('employee')?->id;

        return [
            'nik'                 => ['required', 'string', 'min:' . (int) Setting::get('nik_min_length', 1), 'max:' . (int) Setting::get('nik_max_length', 20), Rule::unique('employees')->ignore($employeeId)],
            'name'                => ['required', 'string', 'max:255'],
            'position'            => ['nullable', 'string', 'max:255'],
            'department'          => ['nullable', 'string', 'max:255'],
            'join_date'           => ['required', 'date'],
            'resign_date'         => ['nullable', 'date', 'after:join_date'],
            'npwp'                => ['nullable', 'string', 'max:30'],
            'bpjs_tk_number'      => ['nullable', 'string', 'max:30'],
            'bpjs_kes_number'     => ['nullable', 'string', 'max:30'],
            'ptkp_status'         => ['required', Rule::in(Employee::ptkpOptions())],
            'ter_category'        => ['nullable', Rule::in(Employee::TER_CATEGORIES)],
            'bank_id'             => ['nullable', 'integer', Rule::exists('banks', 'id')],
            'bank_name'           => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'min:' . (int) Setting::get('bank_account_min_length', 1), 'max:' . (int) Setting::get('bank_account_max_length', 30)],
            'bank_account_name'   => ['nullable', 'string', 'max:255'],
            'status'              => ['required', Rule::in(Employee::statusOptions())],
            'user_id'             => ['nullable', 'integer', Rule::exists('users', 'id'), Rule::unique('employees', 'user_id')->ignore($employeeId)],
            'shift_id'            => ['nullable', 'integer', Rule::exists('shifts', 'id')],
        ];
    }
}
