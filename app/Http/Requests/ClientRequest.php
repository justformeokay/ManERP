<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $clientId = $this->route('client')?->id;

        return [
            'name'    => ['required', 'string', 'max:255'],
            'email'   => ['nullable', 'email', 'max:255', Rule::unique('clients')->ignore($clientId)],
            'phone'   => ['nullable', 'string', 'max:30'],
            'company' => ['nullable', 'string', 'max:255'],
            'tax_id'  => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'city'    => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'type'    => ['required', Rule::in(['customer', 'lead', 'prospect'])],
            'status'  => ['required', Rule::in(['active', 'inactive'])],
            'notes'   => ['nullable', 'string', 'max:2000'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'payment_terms' => ['nullable', 'integer', 'min:0', 'max:365'],
            'is_credit_blocked' => ['nullable', 'boolean'],
        ];
    }
}
