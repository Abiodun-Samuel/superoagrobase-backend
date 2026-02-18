<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductService
{
    protected MediaUploadService $uploadService;

    public function __construct(MediaUploadService $uploadService)
    {
        $this->uploadService = $uploadService;
    }
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
        return $query->with(['category', 'subcategory'])->paginate($perPage);
    }

    public function getFeaturedProducts(int $per_page = 16): Collection
    {
        return Product::with(['category', 'subcategory'])
            ->where('is_featured', true)
            ->inRandomOrder()
            ->limit($per_page)
            ->get();
    }

    public function getTrendingProducts(int $per_page = 16): Collection
    {
        return Product::with(['category', 'subcategory'])->query()
            ->where(function ($query) {
                $query->orWhere('stock', '>', 10);
                $query->orWhere('view_count', '>', 500);
                $query->orWhere('sales_count', '>', 50);
            })
            ->inRandomOrder()
            ->limit($per_page)
            ->get();
    }

    public function bulkUpdateFeatured(array $productIds, bool $isFeatured)
    {
        try {
            DB::beginTransaction();

            $products = Product::whereIn('id', $productIds)->get(['id', 'title', 'is_featured']);
            foreach ($products as $product) {
                try {
                    $product->update(['is_featured' => $isFeatured]);
                } catch (\Exception $e) {
                }
            }
            DB::commit();
            return;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getAllProducts(array $filters = [])
    {
        $query = Product::with([
            'category',
            'subcategory',
            'vendorProducts' => function ($query) {
                $query->with('vendor')->without('product');
            }
        ]);

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['subcategory_id'])) {
            $query->where('subcategory_id', $filters['subcategory_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['is_featured'])) {
            $query->where('is_featured', $filters['is_featured'] === 'true');
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('keywords', 'LIKE', "%{$search}%")
                    ->orWhere('brands', 'LIKE', "%{$search}%");
            });
        }

        if (!empty($filters['sort_by'])) {
            $sortBy = $filters['sort_by'];
            $sortDirection = $filters['sort_direction'] ?? 'asc';
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->latest();
        }

        return $query->paginate($filters['per_page'] ?? 50);
    }

    protected function handleImageUpload($avatarFile, string $folder): string
    {
        return $this->uploadService->upload($avatarFile, $folder);
    }

    public function createProduct(array $data): Product
    {
        return DB::transaction(function () use ($data) {

            $image = $this->handleImageUpload($data['image'], 'superoagrobase_products');

            $data['slug'] = $this->generateUniqueSlug($data['title']);

            return Product::create([
                'category_id' => $data['category_id'] ?? null,
                'subcategory_id' => $data['subcategory_id'] ?? null,
                'slug' => $data['slug'],
                'title' => $data['title'],
                'sub_title' => $data['sub_title'] ?? null,
                'keywords' => $data['keywords'] ?? null,
                'description' => $data['description'] ?? null,
                'ingredients' => $data['ingredients'] ?? null,
                'is_featured' => $data['is_featured'] ?? false,
                'brands' => $data['brands'] ?? null,
                'image' => $image ?? null,
                'images' => $data['images'] ?? null,
                'status' => $data['status'] ?? 'in_stock',
                'pack_size' => $data['pack_size'] ?? null,
                'price' => $data['price'],
                'discount_price' => $data['discount_price'] ?? null,
                'stock' => $data['stock'] ?? 0,
            ]);
        });
    }

    public function updateProduct(Product $product, array $data): Product
    {

        return DB::transaction(function () use ($product, $data) {

            if (isset($data['image']) && !empty($product->image)) {
                $this->uploadService->delete($product->image);
                $data['image'] = $this->handleImageUpload($data['image'], 'superoagrobase_products');
            }

            if (isset($data['title']) && $data['title'] !== $product->title) {
                $data['slug'] = $this->generateUniqueSlug($data['title']);
            }

            $product->update($data);

            return $product->fresh(['category', 'subcategory']);
        });
    }

    public function deleteProduct(Product $product): bool
    {
        return DB::transaction(function () use ($product) {
            $this->uploadService->delete($product->image);
            return $product->delete();
        });
    }

    private function generateUniqueSlug(string $title): string
    {
        return Str::slug($title) . '-' . now()->timestamp . '-' . Str::random(4);
    }
}
