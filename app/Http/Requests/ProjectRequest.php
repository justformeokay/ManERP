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
        return [
            'name'        => ['required', 'string', 'max:255'],
            'client_id'   => ['required', 'exists:clients,id'],
            'description' => ['nullable', 'string', 'max:2000'],
            'start_date'  => ['required', 'date'],
            'end_date'    => ['nullable', 'date', 'after_or_equal:start_date'],
            'status'      => ['required', Rule::in(Project::statusOptions())],
            'budget'      => ['nullable', 'numeric', 'min:0', 'max:99999999999999'],
            'notes'       => ['nullable', 'string', 'max:2000'],
        ];
    }
}
