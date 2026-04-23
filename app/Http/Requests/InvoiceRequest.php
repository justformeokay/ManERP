<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sales_order_id' => 'required|exists:sales_orders,id',
            'invoice_date' => 'nullable|date',
            'due_date' => 'required|date|after_or_equal:' . ($this->input('invoice_date') ?? 'today'),
            'notes' => 'nullable|string|max:1000',
            'include_tax' => 'nullable|boolean',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'items' => 'nullable|array',
            'items.*.quantity' => 'nullable|numeric|min:0',
        ];
    }
}
