<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        return [
            'name'        => ['required', 'string', 'max:255'],
            'sku'         => ['nullable', 'string', 'max:50', Rule::unique('products')->ignore($productId)],
            'description' => ['nullable', 'string', 'max:2000'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'type'        => ['required', Rule::in(Product::typeOptions())],
            'unit'        => ['required', 'string', 'max:20'],
            'cost_price'  => ['nullable', 'numeric', 'min:0'],
            'sell_price'  => ['nullable', 'numeric', 'min:0'],
            'min_stock'   => ['nullable', 'integer', 'min:0'],
            'is_active'   => ['boolean'],
        ];
    }
}
