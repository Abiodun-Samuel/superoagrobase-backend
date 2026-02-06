<?php

namespace App\Http\Requests\VendorProduct;

use App\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVendorProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole(RoleEnum::VENDOR->value);
    }

    public function rules(): array
    {
        return [
            'product_id' => [
                'required',
                'exists:products,id',
                Rule::unique('vendor_products')->where(
                    fn($query) =>
                    $query->where('vendor_id', $this->user()->id)
                ),
            ],
            'price' => 'required|numeric|min:0|max:99999999.99',
            'stock' => 'required|integer|min:0|max:999999',
            'is_available' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.unique' => 'You have already added this product.',
        ];
    }
}
