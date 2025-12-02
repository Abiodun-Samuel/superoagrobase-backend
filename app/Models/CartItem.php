<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = [
        'cart_id',
        'product_id',
        'quantity',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getCurrentPriceAttribute(): float
    {
        return (float) $this->product->price;
    }

    public function getItemTotalAttribute(): float
    {
        return $this->current_price * $this->quantity;
    }
    public function isAvailable(): bool
    {
        return $this->product && $this->product->stock >= $this->quantity;
    }
    public function getIssue(): string
    {
        if (!$this->product) {
            return 'This product is currently unavailable.';
        }
        if ($this->product->stock <= 0 || $this->product->status === 'out_of_stock') {
            return 'This product is out of stock at the moment.';
        }
        if ($this->product->stock < $this->quantity) {
            return 'The requested quantity is not available in stock.';
        }
        return 'This product is available and ready for purchase.';
    }
}
