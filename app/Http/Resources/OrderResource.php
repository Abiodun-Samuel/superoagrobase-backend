<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id'        => $this->user->id,
                    'full_name' => trim($this->user->first_name . ' ' . $this->user->last_name),
                    'avatar' => $this->user->avatar,
                    'email' => $this->user->email,
                    'phone_number' => $this->user->phone_number,
                ];
            }),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'transactions' => $this->whenLoaded('transactions', function () {
                return $this->transactions->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'reference' => $transaction->reference,
                        'transaction_reference' => $transaction->transaction_reference,
                        'amount' => $transaction->amount,
                        'status' => $transaction->status,
                        'channel' => $transaction->channel,
                        'currency' => $transaction->currency,
                        'created_at' => $transaction->created_at->toISOString(),
                    ];
                });
            }),
            'delivery_details' => $this->delivery_details,
            'delivery_method' => $this->delivery_method,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'subtotal' => $this->subtotal,
            'tax' => $this->tax,
            'tax_rate' => $this->tax_rate,
            'shipping' => $this->shipping,
            'total' => $this->total,
            'status' => $this->status,
            'notes' => $this->notes,
            'confirmed_at' => $this->confirmed_at?->toISOString(),
            'paid_at' => $this->paid_at?->toISOString(),
            'shipped_at' => $this->shipped_at?->toISOString(),
            'delivered_at' => $this->delivered_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
