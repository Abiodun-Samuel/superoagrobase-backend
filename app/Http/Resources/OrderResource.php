<?php

namespace App\Http\Resources;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
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
            // Core Information
            'id' => $this->id,
            'reference' => $this->reference,

            // User Information
            'user' => $this->whenLoaded('user', fn() => [
                'id' => $this->user->id,
                'full_name' => trim("{$this->user->first_name} {$this->user->last_name}"),
                'avatar' => $this->user->avatar,
                'email' => $this->user->email,
                'phone_number' => $this->user->phone_number,
            ]),

            // Order Items
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'items_count' => $this->when(
                $this->relationLoaded('items'),
                fn() => $this->items->count()
            ),

            // Transactions - Using TransactionResource
            'transactions' => TransactionResource::collection($this->whenLoaded('transactions')),

            // Delivery Information
            'delivery_details' => $this->delivery_details,
            'delivery_method' => $this->delivery_method,
            'delivery_method_label' => $this->getDeliveryMethodLabel(),
            'delivery_address' => $this->when(
                $this->delivery_method === 'waybill' && is_array($this->delivery_details),
                fn() => $this->formatDeliveryAddress()
            ),

            // Payment Information
            'payment_method' => $this->payment_method,
            'payment_gateway' => $this->payment_gateway,
            'payment_status' => $this->payment_status,
            'payment_status_label' => PaymentStatus::tryFrom($this->payment_status)?->label() ?? ucfirst(str_replace('_', ' ', $this->payment_status)),
            'payment_status_color' => PaymentStatus::tryFrom($this->payment_status)?->color() ?? 'gray',
            'is_paid' => $this->isPaid(),

            // Pricing
            'subtotal' => $this->subtotal,
            'tax' => $this->tax,
            'tax_rate' => $this->tax_rate,
            'shipping' => $this->shipping,
            'total' => $this->total,

            // Order Status
            'status' => $this->status,
            'status_label' => OrderStatus::tryFrom($this->status)?->label() ?? ucfirst(str_replace('_', ' ', $this->status)),
            'status_color' => OrderStatus::tryFrom($this->status)?->color() ?? 'gray',

            // Additional Information
            'notes' => $this->notes,

            // Timestamps
            'confirmed_at' => $this->confirmed_at?->toISOString(),
            'paid_at' => $this->paid_at?->toISOString(),
            'shipped_at' => $this->shipped_at?->toISOString(),
            'delivered_at' => $this->delivered_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),

            // Computed Fields
            'progress_percentage' => $this->getProgressPercentage(),
            'can_cancel' => $this->canBeCancelled(),
            'can_track' => $this->canBeTracked(),
        ];
    }

    /**
     * Get human-readable delivery method label
     */
    protected function getDeliveryMethodLabel(): string
    {
        return match ($this->delivery_method) {
            'pickup' => 'Store Pickup',
            'waybill' => 'Home Delivery',
            default => ucfirst(str_replace('_', ' ', $this->delivery_method))
        };
    }

    /**
     * Format delivery address for display
     */
    protected function formatDeliveryAddress(): ?string
    {
        $parts = array_filter([
            $this->delivery_details['address'] ?? null,
            $this->delivery_details['city'] ?? null,
            $this->delivery_details['state'] ?? null,
            $this->delivery_details['country'] ?? null,
        ]);

        return !empty($parts) ? implode(', ', $parts) : null;
    }

    /**
     * Calculate order progress percentage
     */
    protected function getProgressPercentage(): int
    {
        return match ($this->status) {
            'pending' => 10,
            'pending_payment' => 15,
            'confirmed' => 30,
            'processing' => 50,
            'ready_for_pickup', 'shipped' => 75,
            'out_for_delivery' => 90,
            'delivered', 'completed' => 100,
            'cancelled', 'refunded', 'failed' => 0,
            default => 0
        };
    }

    /**
     * Check if order can be cancelled
     */
    protected function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'pending_payment', 'confirmed']);
    }

    /**
     * Check if order can be tracked
     */
    protected function canBeTracked(): bool
    {
        return $this->delivery_method === 'waybill'
            && in_array($this->status, ['confirmed', 'processing', 'shipped', 'out_for_delivery']);
    }
}
