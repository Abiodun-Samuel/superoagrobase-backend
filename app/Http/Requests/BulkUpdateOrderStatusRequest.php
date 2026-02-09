<?php

namespace App\Http\Requests;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkUpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole(['admin', 'super_admin']);
    }

    public function rules(): array
    {
        return [
            'order_references' => [
                'required',
                'array',
                'min:1',
                'max:100',
            ],
            'order_references.*' => [
                'required',
                'string',
                'exists:orders,reference',
            ],
            'status' => [
                'required',
                Rule::in(OrderStatus::values()),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'order_references.required' => 'Order references are required',
            'order_references.array' => 'Order references must be an array',
            'order_references.min' => 'At least one order reference is required',
            'order_references.max' => 'Cannot update more than 100 orders at once',
            'order_references.*.required' => 'Each order reference is required',
            'order_references.*.string' => 'Order reference must be a string',
            'order_references.*.exists' => 'One or more order references are invalid',
            'status.required' => 'Order status is required',
            'status.in' => 'Invalid order status',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('order_references') && !is_array($this->order_references)) {
            $this->merge([
                'order_references' => [$this->order_references],
            ]);
        }
    }
}
