<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChartOfAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $uniqueRule = 'unique:chart_of_accounts,code';
        if ($this->route('account')) {
            $uniqueRule .= ',' . $this->route('account')->id;
        }

        return [
            'code' => ['required', 'string', 'max:20', $uniqueRule],
            'name' => 'required|string|max:255',
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'parent_id' => 'nullable|exists:chart_of_accounts,id',
            'is_active' => 'boolean',
        ];
    }
}
