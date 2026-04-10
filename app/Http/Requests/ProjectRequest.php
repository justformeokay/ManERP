<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isSales = $this->input('type', 'sales') === 'sales';

        return [
            'type'             => ['required', Rule::in(Project::typeOptions())],
            'name'             => ['required', 'string', 'max:255'],
            'client_id'        => [$isSales ? 'required' : 'nullable', 'nullable', 'exists:clients,id'],
            'manager_id'       => ['nullable', 'exists:users,id'],
            'description'      => ['nullable', 'string', 'max:2000'],
            'start_date'       => ['required', 'date'],
            'end_date'         => ['nullable', 'date', 'after_or_equal:start_date'],
            'status'           => ['required', Rule::in(Project::statusOptions())],
            'budget'           => ['nullable', 'numeric', 'min:0', 'max:99999999999999'],
            'estimated_budget' => ['nullable', 'numeric', 'min:0', 'max:99999999999999'],
            'notes'            => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Clear client_id for internal CAPEX projects
        if ($this->input('type') === 'internal_capex') {
            $this->merge(['client_id' => null]);
        }
    }
}
