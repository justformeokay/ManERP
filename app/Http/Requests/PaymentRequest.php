<?php

namespace App\Http\Requests;

use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;

class PaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $invoice = Invoice::findOrFail($this->input('invoice_id'));
        $maxAmount = round((float) $invoice->total_amount - (float) $invoice->paid_amount, 2);

        return [
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => ['required', 'numeric', 'min:0.01', "max:{$maxAmount}"],
            'payment_date' => 'required|date',
            'payment_method' => 'required|string|in:cash,bank_transfer,credit_card,check,other',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.max' => 'Payment amount cannot exceed the remaining balance.',
        ];
    }
}
