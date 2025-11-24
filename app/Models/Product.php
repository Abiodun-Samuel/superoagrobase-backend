<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
