<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class ProductService
{
    public function getFeaturedProducts(int $limit = 16): Collection
    {
        return Product::query()
            ->where('is_featured', true)
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }
    public function getTrendingProducts(int $limit = 16): Collection
    {
        return Product::query()
            ->where(function ($query) {
                $query->orWhere('stock', '>', 10);
                $query->orWhere('view_count', '>', 500);
                $query->orWhere('sales_count', '>', 50);
            })
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }
}
