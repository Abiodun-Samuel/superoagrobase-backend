<?php

namespace App\Http\Requests;

use App\Enums\PaymentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderRequest extends FormRequest
{

    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $isWaybill = $this->input('delivery_method') === 'waybill';

        return [

            'session_id' => 'required|string|uuid',

            'delivery_details.first_name' => 'required|string|max:255',
            'delivery_details.last_name' => 'required|string|max:255',
            'delivery_details.email' => 'required|email|max:255',
            'delivery_details.phone_number' => 'required|string|max:20',
            'delivery_details.whatsapp_number' => 'nullable|string|max:20',

            'delivery_details.address' => [Rule::requiredIf($isWaybill), 'string', 'max:500'],
            'delivery_details.city' => [Rule::requiredIf($isWaybill), 'string', 'max:500'],
            'delivery_details.state' => [Rule::requiredIf($isWaybill), 'string', 'max:500'],
            'delivery_details.country' => [Rule::requiredIf($isWaybill), 'string',  'max:255'],

            'items' => 'required|array|min:1',
            'items.*.product_id' => ['required', 'integer', 'exists:products,id',],
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price_at_purchase' => 'required|numeric|min:0',
            'items.*.subtotal' => 'required|numeric|min:0',

            'delivery_method' => 'required|in:pickup,waybill',
            'payment_method' => ['required', Rule::in(PaymentStatus::values())],
            'save_delivery_details' => 'boolean',

            'pricing.subtotal' => 'required|numeric|min:0',
            'pricing.tax' => 'required|numeric|min:0',
            'pricing.tax_rate' => 'required|numeric|min:0|max:100',
            'pricing.shipping' => 'required|numeric|min:0',
            'pricing.total' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Your cart is empty. Please add items before checkout.',
            'items.min' => 'Your cart must contain at least one item.',
            'delivery_details.first_name.required' => 'First name is required',
            'delivery_details.last_name.required' => 'Last name is required',
            'delivery_details.email.required' => 'Email address is required',
            'delivery_details.email.email' => 'Please provide a valid email address',
            'delivery_details.phone_number.required' => 'Phone number is required',
            'delivery_details.address.required' => 'Delivery address is required for waybill delivery',
            'delivery_details.city.required' => 'City is required for waybill delivery',
            'delivery_details.state.required' => 'State is required for waybill delivery',
            'delivery_method.required' => 'Please select a delivery method',
            'payment_method.required' => 'Please select a payment method',
        ];
    }
}
