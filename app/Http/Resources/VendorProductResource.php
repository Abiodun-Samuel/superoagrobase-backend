<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vendor_id' => $this->vendor_id,
            'product_id' => $this->product_id,
            'price' => (float) $this->price,
            'stock' => $this->stock,
            'is_available' => (bool) $this->is_available,

            'product' => $this->whenLoaded('product', [
                'id' => $this->product->id,
                'slug' => $this->product->slug,
                'title' => $this->product->title,
                'sub_title' => $this->product->sub_title,
                'description' => $this->product->description,
                'category' => $this->product->category,
                'subcategory' => $this->product->subcategory,
                'brands' => $this->product->brands,
                'image' => $this->product->image,
                'price' => (float) $this->product->price,
                'stock' => $this->product->stock,
            ]),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
