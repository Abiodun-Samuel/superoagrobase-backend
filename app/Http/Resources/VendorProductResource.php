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

            'vendor' => $this->whenLoaded('vendor', function () {
                return [
                    'id' => $this->vendor->id,
                    'first_name' => $this->vendor->first_name,
                    'last_name' => $this->vendor->last_name,
                    'email' => $this->vendor->email,
                    'phone_number' => $this->vendor->phone_number,
                    'avatar' => $this->vendor->avatar,
                    'company_name' => $this->vendor->company_name,
                    'company_email' => $this->vendor->company_email,
                    'company_phone' => $this->vendor->company_phone,
                    'company_address' => $this->vendor->company_address,
                    'company_website' => $this->vendor->company_website,
                ];
            }),

            'product' => $this->whenLoaded('product', function () {
                return [
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
                ];
            }),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
