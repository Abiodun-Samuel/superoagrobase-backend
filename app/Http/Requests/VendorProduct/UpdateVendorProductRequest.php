<?php

namespace App\Http\Requests\VendorProduct;

use App\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;

class UpdateVendorProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole(RoleEnum::VENDOR->value);
    }

    public function rules(): array
    {
        return [
            'price' => 'sometimes|required|numeric|min:0|max:99999999.99',
            'stock' => 'sometimes|required|integer|min:0|max:999999',
            'is_available' => 'sometimes|boolean',
        ];
    }
}
