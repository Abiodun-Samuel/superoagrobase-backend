<?php

namespace App\Http\Requests\VendorProduct;

use App\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreVendorProductRequest extends FormRequest
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
            'products.*.vendor_id' => 'required|integer|exists:users,id',
            'products.*.product_id' => 'required|integer|exists:products,id',
            'products.*.price' => 'required|numeric|min:0|max:99999999.99|decimal:0,2',
            'products.*.stock' => 'required|integer|min:0|max:999999',
            'products.*.is_available' => 'required|boolean',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'products' => 'products',
            'products.*.vendor_id' => 'vendor',
            'products.*.product_id' => 'product',
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
            'products.required' => 'At least one product is required.',
            'products.array' => 'Products must be provided as a valid list.',
            'products.min' => 'You must add at least one product.',
            'products.max' => 'You cannot add more than 100 products at once.',

            // Vendor ID
            'products.*.vendor_id.required' => 'Vendor ID is required for item :position.',
            'products.*.vendor_id.integer' => 'Vendor ID must be a valid number for item :position.',
            'products.*.vendor_id.exists' => 'The selected vendor does not exist for item :position.',

            // Product ID
            'products.*.product_id.required' => 'Product selection is required for item :position.',
            'products.*.product_id.integer' => 'Product ID must be a valid number for item :position.',
            'products.*.product_id.exists' => 'The selected product does not exist for item :position.',

            // Price
            'products.*.price.required' => 'Price is required for item :position.',
            'products.*.price.numeric' => 'Price must be a valid number for item :position.',
            'products.*.price.min' => 'Price cannot be negative for item :position.',
            'products.*.price.max' => 'Price cannot exceed 99,999,999.99 for item :position.',
            'products.*.price.decimal' => 'Price can have at most 2 decimal places for item :position.',

            // Stock
            'products.*.stock.required' => 'Stock quantity is required for item :position.',
            'products.*.stock.integer' => 'Stock quantity must be a whole number for item :position.',
            'products.*.stock.min' => 'Stock quantity cannot be negative for item :position.',
            'products.*.stock.max' => 'Stock quantity cannot exceed 999,999 units for item :position.',

            // Availability
            'products.*.is_available.required' => 'Availability status is required for item :position.',
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

                $products[] = $sanitized;
            }

            $this->merge(['products' => $products]);
        }
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
                'message' => 'You are not authorized to add products. Only vendors can perform this action.',
            ], 403)
        );
    }
}
