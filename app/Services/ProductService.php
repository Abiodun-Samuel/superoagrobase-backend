<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\Subcategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;

class ProductService
{
    private function applySorting($query, string $sort)
    {
        switch ($sort) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'name_asc':
                $query->orderBy('title', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('title', 'desc');
                break;
            case 'popular':
                $query->orderBy('sales_count', 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['category'])) {
            $query->whereHas('category', fn($q) => $q->where('slug', $filters['category']));
        }
        if (!empty($filters['subcategory'])) {
            $query->whereHas('subcategory', fn($q) => $q->where('slug', $filters['subcategory']));
        }
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function (Builder $q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('sub_title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('keywords', 'like', "%{$search}%")
                    ->orWhere('brands', 'like', "%{$search}%");
            });
        }
        if (!empty($filters['brand'])) {
            $query->where('brands', 'like', '%' . trim($filters['brand']) . '%');
        }
        if (!empty($filters['minPrice']) && is_numeric($filters['minPrice'])) {
            $query->where('price', '>=', floatval($filters['minPrice']));
        }
        if (!empty($filters['maxPrice']) && is_numeric($filters['maxPrice'])) {
            $query->where('price', '<=', floatval($filters['maxPrice']));
        }
        if (!empty($filters['inStock']) && filter_var($filters['inStock'], FILTER_VALIDATE_BOOLEAN)) {
            $query->where('stock', '>', 0);
        }
    }


    public function getProducts(array $filters, ?int $perPage = null)
    {
        $query = Product::query();
        $this->applyFilters($query, $filters);
        $this->applySorting($query, $filters['sort'] ?? 'newest');
        $perPage = max(1, min(100, intval($perPage ?? $filters['per_page'] ?? 50)));
        return $query->paginate($perPage);
    }

    public function getFeaturedProducts(int $per_page = 16): Collection
    {
        return Product::query()
            ->where('is_featured', true)
            ->inRandomOrder()
            ->limit($per_page)
            ->get();
    }

    public function getTrendingProducts(int $per_page = 16): Collection
    {
        return Product::query()
            ->where(function ($query) {
                $query->orWhere('stock', '>', 10);
                $query->orWhere('view_count', '>', 500);
                $query->orWhere('sales_count', '>', 50);
            })
            ->inRandomOrder()
            ->limit($per_page)
            ->get();
    }
}
