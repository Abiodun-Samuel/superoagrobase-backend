<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'subcategory_id',
        'slug',
        'title',
        'sub_title',
        'keywords',
        'description',
        'ingredients',
        'is_featured',
        'brands',
        'image',
        'images',
        'view_count',
        'sales_count',
        'status',
        'pack_size',
        'price',
        'discount_price',
        'stock',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'images' => 'array',
        'view_count' => 'integer',
        'sales_count' => 'integer',
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'stock' => 'integer',
    ];

    protected $with = ['category', 'subcategory', 'reviews'];

    protected $appends = [
        'final_price',
        'final_stock',
        'has_vendor_pricing',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class);
    }
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function vendorProducts(): HasMany
    {
        return $this->hasMany(VendorProduct::class);
    }

    public function availableVendorProducts(): HasMany
    {
        return $this->hasMany(VendorProduct::class)
            ->where('is_available', true)
            ->where('stock', '>', 0)
            ->orderBy('price', 'asc');
    }

    protected function finalPrice(): Attribute
    {
        return Attribute::make(
            get: function () {
                $cheapestAvailable = $this->availableVendorProducts()->first();
                return $cheapestAvailable ? $cheapestAvailable->price : $this->price;
            }
        );
    }

    protected function finalStock(): Attribute
    {
        return Attribute::make(
            get: function () {
                $totalVendorStock = $this->availableVendorProducts()->sum('stock');
                return $totalVendorStock > 0 ? $totalVendorStock : $this->stock;
            }
        );
    }

    protected function hasVendorPricing(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->availableVendorProducts()->exists()
        );
    }

    // ============================================
    // Query Scopes - Optimized Single Query
    // ============================================

    public function scopeWithVendorPricing($query)
    {
        return $query->addSelect([
            'products.*',
            'vendor_price' => VendorProduct::select('price')
                ->whereColumn('product_id', 'products.id')
                ->where('is_available', true)
                ->where('stock', '>', 0)
                ->orderBy('price', 'asc')
                ->limit(1),
            'vendor_stock' => VendorProduct::selectRaw('SUM(stock)')
                ->whereColumn('product_id', 'products.id')
                ->where('is_available', true)
                ->where('stock', '>', 0),
        ])->selectRaw(
            'COALESCE(
                (SELECT price FROM vendor_products
                 WHERE product_id = products.id
                 AND is_available = 1
                 AND stock > 0
                 ORDER BY price ASC LIMIT 1),
                products.price
            ) as calculated_price'
        )->selectRaw(
            'COALESCE(
                (SELECT SUM(stock) FROM vendor_products
                 WHERE product_id = products.id
                 AND is_available = 1
                 AND stock > 0),
                products.stock
            ) as calculated_stock'
        );
    }

    public function scopeInStock($query)
    {
        return $query->where(function ($q) {
            $q->where('stock', '>', 0)
                ->orWhereHas('availableVendorProducts');
        });
    }

    // ============================================
    // Helper Methods
    // ============================================

    public function getVendorsWithPrices()
    {
        return $this->vendorProducts()
            ->with('vendor:id,first_name,last_name,email')
            ->where('is_available', true)
            ->where('stock', '>', 0)
            ->orderBy('price', 'asc')
            ->get()
            ->map(function ($vendorProduct, $index) {
                return [
                    'id' => $vendorProduct->id,
                    'vendor_id' => $vendorProduct->vendor_id,
                    'vendor_name' => trim($vendorProduct->vendor->first_name . ' ' . $vendorProduct->vendor->last_name),
                    'vendor_email' => $vendorProduct->vendor->email,
                    'price' => (float) $vendorProduct->price,
                    'stock' => $vendorProduct->stock,
                    'is_cheapest' => $index === 0,
                ];
            });
    }
}
