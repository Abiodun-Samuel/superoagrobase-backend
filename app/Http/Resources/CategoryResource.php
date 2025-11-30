<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return  [
            'id' => $this->id,
            'slug' => $this->slug,
            'image' => $this->image,
            'title' => $this->title,
            'product_count' => $this->when(
                $this->relationLoaded('products'),
                fn() => $this->products->count()
            ),
            'badges' => $this->computeBadges(),
            'subcategory' => SubcategoryResource::collection($this->whenLoaded('subcategory')),
        ];
    }
    protected function computeBadges(): array
    {
        $badges = [];

        // Only compute badges if products relationship is loaded
        if (!$this->relationLoaded('products')) {
            return $badges;
        }

        $products = $this->products;
        $productCount = $products->count();

        // Empty category
        if ($productCount === 0) {
            $badges[] = 'Empty';
            return $badges; // No need to check other badges
        }

        // New Category - Based on category's created_at (no query needed)
        if ($this->created_at?->gt(now()->subDays(30))) {
            $badges[] = 'New';
        }

        // Popular - High number of products
        if ($productCount > 50) {
            $badges[] = 'Popular';
        }

        // Top Seller - High total sales (calculated from loaded products)
        $totalSales = $products->sum('sales_count');
        if ($totalSales > 500) {
            $badges[] = 'Top Seller';
        }

        // Trending - High views relative to product count (calculated from loaded products)
        $totalViews = $products->sum('view_count');
        if ($productCount > 0 && ($totalViews / $productCount) > 300) {
            $badges[] = 'Trending';
        }

        // Featured - Has multiple featured products (from loaded products)
        $featuredCount = $products->where('is_featured', true)->count();
        if ($featuredCount > 3) {
            $badges[] = 'Featured';
        }

        // Hot Deals - Has many discounted products (from loaded products)
        $discountedCount = $products->filter(function ($product) {
            return $product->discount_price && $product->discount_price < $product->price;
        })->count();

        if ($discountedCount > 5) {
            $badges[] = 'Hot Deals';
        }

        // Limited Stock - Many products with low stock (from loaded products)
        $lowStockCount = $products->filter(function ($product) {
            return $product->stock < 10 && $product->stock > 0;
        })->count();

        if ($lowStockCount > 10) {
            $badges[] = 'Limited Stock';
        }

        // Premium - High average rating (from loaded products, if rating exists)
        $productsWithRating = $products->whereNotNull('rating');
        if ($productsWithRating->count() > 0) {
            $averageRating = $productsWithRating->avg('rating');
            if ($averageRating >= 4.5) {
                $badges[] = 'Premium';
            }

            // Curated - Moderate number of high-quality products
            if ($productCount >= 10 && $productCount <= 30 && $averageRating >= 4.0) {
                $badges[] = 'Curated';
            }

            // Best Value - Many discounted products with good ratings
            if ($discountedCount > 3 && $averageRating >= 4.0) {
                $badges[] = 'Best Value';
            }
        }

        // Growing - Recent products added (from loaded products)
        $recentProducts = $products->filter(function ($product) {
            return $product->created_at?->gt(now()->subDays(14));
        })->count();

        if ($recentProducts > 5) {
            $badges[] = 'Growing';
        }

        return $badges;
    }
}
