<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
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
            'product_id' => $this->product_id,
            'cart_id' => $this->cart_id,
            'quantity' => $this->quantity,
            'current_price' => $this->current_price,
            'itemTotal' => $this->itemTotal,
            'is_available' => $this->isAvailable(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
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
        ];
    }
}
