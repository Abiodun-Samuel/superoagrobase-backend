<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'sub_title' => $this->sub_title,
            'keywords' => $this->keywords,
            'description' => $this->description,
            'ingredients' => $this->ingredients,
            'is_featured' => $this->is_featured,
            'brands' => $this->brands,
            'image' => $this->image,
            'images' => $this->images,
            'view_count' => $this->view_count,
            'sales_count' => $this->sales_count,
            'status' => $this->status,
            'pack_size' => $this->pack_size,
            'price' => $this->price,
            'discount_price' => $this->discount_price,
            'stock' => $this->stock,
            'badges' => $this->computeBadges(),
            'category' => CategoryResource::make($this->whenLoaded('category')),
            'subcategory' => SubcategoryResource::make($this->whenLoaded('subcategory')),
            'reviews_summary' => $this->whenLoaded('reviews', function () {
                $reviews = $this->reviews;
                return [
                    'reviews_count'   => $reviews->count(),
                    'average_ratings' => round($reviews->avg('rating'), 1),
                ];
            }) ?: [
                'reviews_count'   => 0,
                'average_ratings' => 0,
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
    protected function computeBadges(): array
    {
        $badges = [];

        if ($this->sales_count > 100) {
            $badges[] = 'Best Seller';
        }

        if ($this->view_count > 500) {
            $badges[] = 'Popular';
        }

        if ($this->stock < 10 && $this->stock > 0) {
            $badges[] = 'Limited Stock';
        }

        if ($this->created_at?->gt(now()->subDays(14))) {
            $badges[] = 'New Arrival';
        }

        if ($this->discount_price && $this->discount_price < $this->price) {
            $badges[] = 'Discounted';
        }

        if ($this->is_featured) {
            $badges[] = 'Featured';
        }

        if ($this->relationLoaded('reviews')) {
            $avgRating = round($this->reviews->avg('rating'), 1);

            if ($avgRating >= 4.5) {
                $badges[] = 'Top Rated';
            } elseif ($avgRating >= 4.0) {
                $badges[] = 'Good Rating';
            } elseif ($avgRating > 0) {
                $badges[] = 'Rated Product';
            }
        }

        return $badges;
    }
}
