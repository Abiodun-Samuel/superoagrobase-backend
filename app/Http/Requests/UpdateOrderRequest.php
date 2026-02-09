<?php

namespace App\Http\Requests;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole(['admin', 'super_admin']);
    }

    public function rules(): array
    {
        return [
            // Delivery details
            'delivery_details' => 'sometimes|array',
            'delivery_details.first_name' => 'sometimes|string|max:255',
            'delivery_details.last_name' => 'sometimes|string|max:255',
            'delivery_details.email' => 'sometimes|email|max:255',
            'delivery_details.phone_number' => 'sometimes|string|max:20',
            'delivery_details.whatsapp_number' => 'nullable|string|max:20',
            'delivery_details.address' => 'nullable|string|max:500',
            'delivery_details.city' => 'nullable|string|max:255',
            'delivery_details.state' => 'nullable|string|max:255',
            'delivery_details.country' => 'nullable|string|max:255',

            // Delivery & Payment
            'delivery_method' => ['sometimes', Rule::in(['pickup', 'waybill'])],
            'payment_method' => ['sometimes', Rule::in(PaymentStatus::values())],
            'payment_gateway' => 'nullable|string|max:255',

            // Status
            'status' => ['sometimes', Rule::in(OrderStatus::values())],
            'payment_status' => ['sometimes', Rule::in(PaymentStatus::values())],

            // Amounts
            'subtotal' => 'sometimes|numeric|min:0',
            'tax' => 'sometimes|numeric|min:0',
            'tax_rate' => 'sometimes|numeric|min:0|max:100',
            'shipping' => 'sometimes|numeric|min:0',
            'total' => 'sometimes|numeric|min:0',

            // Notes
            'notes' => 'nullable|string|max:2000',

            // Timestamps (manual override if needed)
            'confirmed_at' => 'nullable|date',
            'paid_at' => 'nullable|date',
            'shipped_at' => 'nullable|date',
            'delivered_at' => 'nullable|date',
            'cancelled_at' => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'delivery_method.in' => 'Delivery method must be either pickup or waybill',
            'payment_method.in' => 'Invalid payment method selected',
            'status.in' => 'Invalid order status',
            'payment_status.in' => 'Invalid payment status',
            'tax_rate.max' => 'Tax rate cannot exceed 100%',
            'notes.max' => 'Notes cannot exceed 2000 characters',
        ];
    }

    /**
     * Prepare data for validation
     */
    protected function prepareForValidation(): void
    {
        // Auto-calculate total if amounts are provided
        if ($this->has(['subtotal', 'tax', 'shipping'])) {
            $this->merge([
                'total' => $this->subtotal + $this->tax + $this->shipping
            ]);
        }
    }
}
