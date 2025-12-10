<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
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
            'order_id' => $this->order_id,
            'product' => $this->whenLoaded('product', function () {
                return [
                    'id' => $this->product->id,
                    'slug' => $this->product->slug,
                    'title' => $this->product->title,
                    'image' => $this->product->image,
                    'pack_size' => $this->product->pack_size,
                    'price' => $this->product->price,
                    'stock' => $this->product->stock,
                ];
            }),
            'product_id' => $this->product_id,
            'quantity' => $this->quantity,
            'price_at_purchase' => $this->price_at_purchase,
            'subtotal' => $this->subtotal,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
