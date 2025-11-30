<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $tax_rate = 0.075;
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'session_id' => $this->session_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'items' => CartItemResource::collection($this->whenLoaded('items')),

            'summary' => $this->when($this->relationLoaded('items'), fn() => [
                'item_count' => $this->item_count,
                'subtotal' => $this->subtotal,
                'tax' => round($this->subtotal * $tax_rate, 2),
                'tax_rate' => $tax_rate,
                'total' => $this->subtotal + round($this->subtotal * $tax_rate, 2),
            ]),

            'availability' => $this->when($this->relationLoaded('items'), fn() => [
                'has_unavailable_items' => $this->hasUnavailableItems(),
                'unavailable_count' => $this->unavailable_items->count(),
            ]),
        ];
    }
}
