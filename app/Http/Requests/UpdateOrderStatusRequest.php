<?php

namespace App\Http\Requests;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $order = $this->route('order');

        if ($this->user()->hasRole(['user', 'vendor'])) {
            if ($order->user_id !== $this->user()->id) {
                return false;
            }

            if ($this->input('status') === OrderStatus::CANCELLED->value) {
                return in_array($order->status, [
                    OrderStatus::PENDING->value,
                    OrderStatus::CONFIRMED->value,
                ]);
            }

            return false;
        }

        return $this->user()->hasRole(['admin', 'super_admin']);
    }

    public function rules(): array
    {
        $user = $this->user();

        if ($user->hasRole(['user', 'vendor'])) {
            return [
                'status' => [
                    'required',
                    Rule::in([OrderStatus::CANCELLED->value]),
                ],
            ];
        }

        return [
            'status' => [
                'required',
                Rule::in(OrderStatus::values()),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Order status is required',
            'status.in' => 'Invalid order status',
        ];
    }
}
