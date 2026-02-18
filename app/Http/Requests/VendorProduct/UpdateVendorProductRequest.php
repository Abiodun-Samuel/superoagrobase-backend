<?php

namespace App\Http\Requests\VendorProduct;

use App\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateVendorProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasRole(RoleEnum::VENDOR->value);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'products' => 'required|array|min:1|max:100',
            'products.*.id' => 'required|integer|exists:vendor_products,id',
            'products.*.price' => 'sometimes|numeric|min:0|max:99999999.99|decimal:0,2',
            'products.*.stock' => 'sometimes|integer|min:0|max:999999',
            'products.*.is_available' => 'sometimes|boolean',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'products' => 'products',
            'products.*.id' => 'product ID',
            'products.*.price' => 'price',
            'products.*.stock' => 'stock quantity',
            'products.*.is_available' => 'availability status',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            // Products array
            'products.required' => 'At least one product is required for update.',
            'products.array' => 'Products must be provided as a valid list.',
            'products.min' => 'You must update at least one product.',
            'products.max' => 'You cannot update more than 100 products at once.',

            // Product ID
            'products.*.id.required' => 'Product ID is required for item :position.',
            'products.*.id.integer' => 'Product ID must be a valid number for item :position.',
            'products.*.id.exists' => 'The selected product does not exist in your catalog for item :position.',

            // Price
            'products.*.price.numeric' => 'Price must be a valid number for item :position.',
            'products.*.price.min' => 'Price cannot be negative for item :position.',
            'products.*.price.max' => 'Price cannot exceed 99,999,999.99 for item :position.',
            'products.*.price.decimal' => 'Price can have at most 2 decimal places for item :position.',

            // Stock
            'products.*.stock.integer' => 'Stock quantity must be a whole number for item :position.',
            'products.*.stock.min' => 'Stock quantity cannot be negative for item :position.',
            'products.*.stock.max' => 'Stock quantity cannot exceed 999,999 units for item :position.',

            // Availability
            'products.*.is_available.boolean' => 'Availability status must be true or false for item :position.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if (!$this->has('products')) {
            $this->merge(['products' => []]);
            return;
        }

        if (is_array($this->products)) {
            $products = [];

            foreach ($this->products as $product) {
                $sanitized = $product;

                // Convert string booleans to actual booleans
                if (isset($product['is_available'])) {
                    $sanitized['is_available'] = filter_var(
                        $product['is_available'],
                        FILTER_VALIDATE_BOOLEAN,
                        FILTER_NULL_ON_FAILURE
                    ) ?? $product['is_available'];
                }

                // Only include fields that are being updated
                $sanitized = array_filter($sanitized, function ($value, $key) {
                    // Always keep 'id', remove null values for other fields
                    return $key === 'id' || $value !== null;
                }, ARRAY_FILTER_USE_BOTH);

                $products[] = $sanitized;
            }

            $this->merge(['products' => $products]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            if (!$this->has('products') || !is_array($this->products)) {
                return;
            }

            // Check that at least one field is being updated for each product
            foreach ($this->products as $index => $product) {
                $updateFields = array_diff_key($product, ['id' => null]);

                if (empty($updateFields)) {
                    $position = $index + 1;
                    $validator->errors()->add(
                        "products.{$index}",
                        "No fields to update for item #{$position}. Please provide at least one field (price, stock, or is_available)."
                    );
                }
            }
        });
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors()->toArray();

        // Replace :position placeholder with actual position number
        $formattedErrors = [];
        foreach ($errors as $key => $messages) {
            $formattedMessages = array_map(function ($message) use ($key) {
                if (preg_match('/products\.(\d+)\./', $key, $matches)) {
                    $position = (int)$matches[1] + 1;
                    return str_replace(':position', $position, $message);
                }
                return $message;
            }, $messages);

            $formattedErrors[$key] = $formattedMessages;
        }

        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed. Please check the provided information.',
                'errors' => $formattedErrors,
            ], 422)
        );
    }

    /**
     * Handle a failed authorization attempt.
     */
    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'You are not authorized to update products. Only vendors can perform this action.',
            ], 403)
        );
    }
}
