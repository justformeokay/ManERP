<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id'      => ['required', 'exists:products,id'],
            'name'            => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string', 'max:2000'],
            'output_quantity' => ['required', 'numeric', 'min:0.01'],
            'is_active'       => ['boolean'],
            'items'           => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity'   => ['required', 'numeric', 'min:0.0001'],
            'items.*.notes'      => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'At least one material/component is required.',
            'items.min'      => 'At least one material/component is required.',
        ];
    }
}
