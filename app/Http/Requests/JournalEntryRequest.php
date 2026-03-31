<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JournalEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => 'required|date',
            'description' => 'required|string|max:255',
            'items' => 'required|array|min:2',
            'items.*.account_id' => 'required|exists:chart_of_accounts,id',
            'items.*.debit' => 'required|numeric|min:0',
            'items.*.credit' => 'required|numeric|min:0',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $items = $this->input('items', []);
            $totalDebit  = round(array_sum(array_column($items, 'debit')), 2);
            $totalCredit = round(array_sum(array_column($items, 'credit')), 2);

            if (abs($totalDebit - $totalCredit) > 0.01) {
                $validator->errors()->add('items', "Total debit ({$totalDebit}) must equal total credit ({$totalCredit}).");
            }

            foreach ($items as $i => $item) {
                $d = (float) ($item['debit'] ?? 0);
                $c = (float) ($item['credit'] ?? 0);
                if ($d == 0 && $c == 0) {
                    $validator->errors()->add("items.{$i}", "Line " . ($i + 1) . " must have either a debit or credit amount.");
                }
            }
        });
    }
}
