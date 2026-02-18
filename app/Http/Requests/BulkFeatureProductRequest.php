<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkFeatureProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only admins can bulk update featured status
        return $this->user()->hasRole(['super_admin', 'admin']);
    }

    public function rules(): array
    {
        return [
            'product_ids' => ['required', 'array', 'min:1', 'max:100'],
            'product_ids.*' => ['required', 'integer', 'exists:products,id'],
            'is_featured' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_ids.required' => 'Please select at least one product.',
            'product_ids.array' => 'Product IDs must be an array.',
            'product_ids.min' => 'Please select at least one product.',
            'product_ids.max' => 'You can only update up to 100 products at once.',
            'product_ids.*.exists' => 'One or more selected products do not exist.',
            'is_featured.required' => 'Featured status is required.',
            'is_featured.boolean' => 'Featured status must be true or false.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'product_ids' => 'products',
            'is_featured' => 'featured status',
        ];
    }
}
