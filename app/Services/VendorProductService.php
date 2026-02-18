<?php

namespace App\Services;

use App\Models\VendorProduct;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class VendorProductService
{
    public function getVendorProducts(array $filters = [], $vendorId): Collection
    {
        $sort = $filters['sort'] ?? 'newest';
        $search = trim($filters['search'] ?? '');

        $query = VendorProduct::select([
            'vendor_products.id',
            'vendor_products.product_id',
            'vendor_products.vendor_id',
            'vendor_products.price',
            'vendor_products.stock',
            'vendor_products.is_available',
            'vendor_products.created_at',
            'vendor_products.updated_at',
        ])
            ->join('products', 'vendor_products.product_id', '=', 'products.id')
            ->where('vendor_products.vendor_id', $vendorId);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('products.title', 'like', "{$search}%") // No leading wildcard
                    ->orWhere('products.sub_title', 'like', "{$search}%")
                    ->orWhere('products.description', 'like', "%{$search}%")
                    ->orWhere('products.keywords', 'like', "%{$search}%")
                    ->orWhere('products.brands', 'like', "{$search}%");
            });
        }

        $this->applySorting($query, $sort);

        $results = $query->with([
            'vendor',
            'product:id,title,sub_title,category_id,subcategory_id,image,brands,description,slug',
            'product.category:id,title,slug',
            'product.subcategory:id,title,slug'
        ])->get();

        return $results;
    }

    /**
     * Apply sorting - simplified since we already joined
     */
    private function applySorting(Builder $query, string $sort): void
    {
        match ($sort) {
            'name_asc' => $query->orderBy('products.title', 'asc'),
            'name_desc' => $query->orderBy('products.title', 'desc'),
            'oldest' => $query->orderBy('vendor_products.created_at', 'asc'),
            default => $query->orderBy('vendor_products.created_at', 'desc'), // newest
        };
    }
    // private function applySorting($query, string $sort)
    // {
    //     switch ($sort) {
    //         case 'name_asc':
    //             $query->orderBy('title', 'asc');
    //             break;
    //         case 'name_desc':
    //             $query->orderBy('title', 'desc');
    //             break;
    //         case 'newest':
    //             $query->orderBy('created_at', 'desc');
    //             break;
    //         case 'oldest':
    //             $query->orderBy('created_at', 'asc');
    //             break;
    //         default:
    //             $query->orderBy('created_at', 'desc');
    //     }
    // }
    // /**
    //  * Get vendor's products with optional filters
    //  */
    // public function getVendorProducts(array $filters = [])
    // {
    //     $query = VendorProduct::with(['product.category', 'product.subcategory'])
    //         ->where('vendor_id', Auth::id());

    //     // Search filter
    //     if (!empty($filters['search'])) {
    //         $search = trim($filters['search']);
    //         $query->whereHas('product', function ($q) use ($search) {
    //             $q->where('title', 'like', "%{$search}%")
    //                 ->orWhere('sub_title', 'like', "%{$search}%")
    //                 ->orWhere('description', 'like', "%{$search}%")
    //                 ->orWhere('keywords', 'like', "%{$search}%")
    //                 ->orWhere('brands', 'like', "%{$search}%");
    //         });
    //     }
    //     $this->applySorting($query, $filters['sort'] ?? 'newest');

    //     return $query->latest()->get();
    // }

    /**
     * Add products to vendor catalog (single or bulk)
     */
    public function addProducts(array $products): array
    {
        DB::beginTransaction();

        try {
            $vendorProducts = [];
            $skipped = 0;

            foreach ($products as $productData) {
                // Check if already exists
                $exists = VendorProduct::where('vendor_id', $productData['vendor_id'])
                    ->where('product_id', $productData['product_id'])
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                $vendorProducts[] = VendorProduct::create([
                    'vendor_id' => $productData['vendor_id'],
                    'product_id' => $productData['product_id'],
                    'price' => $productData['price'],
                    'stock' => $productData['stock'],
                    'is_available' => $productData['is_available'] ?? true,
                ]);
            }

            DB::commit();

            return [
                'products' => $vendorProducts,
                'added' => count($vendorProducts),
                'skipped' => $skipped,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update vendor products (single or bulk)
     */
    public function updateProducts(array $updates): array
    {
        DB::beginTransaction();

        try {
            $updated = [];

            foreach ($updates as $updateData) {
                $vendorProduct = VendorProduct::where('vendor_id', Auth::id())
                    ->findOrFail($updateData['id']);

                $vendorProduct->update(array_filter([
                    'price' => $updateData['price'] ?? null,
                    'stock' => $updateData['stock'] ?? null,
                    'is_available' => $updateData['is_available'] ?? null,
                ], fn($value) => $value !== null));

                $updated[] = $vendorProduct->fresh();
            }

            DB::commit();

            return $updated;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete vendor products (single or bulk)
     */
    public function deleteProducts(array $ids): int
    {
        DB::beginTransaction();

        try {
            $deleted = VendorProduct::whereIn('id', $ids)->delete();

            DB::commit();

            return $deleted;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get single vendor product
     */
    public function getVendorProduct(int $id)
    {
        return VendorProduct::with(['product.category', 'product.subcategory'])
            ->where('vendor_id', Auth::id())
            ->findOrFail($id);
    }

    /**
     * Check if vendor owns product
     */
    public function ownsProduct(int $id): bool
    {
        return VendorProduct::where('vendor_id', Auth::id())
            ->where('id', $id)
            ->exists();
    }
}
