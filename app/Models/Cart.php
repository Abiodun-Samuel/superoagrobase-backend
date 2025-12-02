<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    protected $fillable = ['user_id', 'session_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function getSubtotalAttribute(): float
    {
        return $this->items->sum(function ($item) {
            return $item->itemTotal;
        });
    }

    public function getItemCountAttribute(): int
    {
        return $this->items->count();
    }

    public function getUnavailableItemsAttribute()
    {
        return $this->items->filter(function ($item) {
            return !$item->isAvailable();
        });
    }

    public function hasUnavailableItems(): bool
    {
        return $this->unavailable_items->isNotEmpty();
    }

    public function getAvailabilityIssuesAttribute(): array
    {
        return $this->unavailable_items->map(function ($item) {
            return [
                'product_slug' => $item->product?->slug,
                'product_title' => $item->product?->title,
                'product_image' => $item->product?->image,
                'product_issue' => $item->getIssue(),
            ];
        })->toArray();
    }
}
