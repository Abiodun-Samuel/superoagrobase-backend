<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;


class CartService
{
    public function getOrCreateCart(?int $userId, string $sessionId): Cart
    {

        $cart = Cart::where('session_id', $sessionId)->first();

        if ($cart) {
            if ($userId !== null && $cart->user_id === null) {
                $cart->update(['user_id' => $userId]);
                $cart->refresh();
            }
            if ($userId !== null && $cart->user_id !== null && $cart->user_id !== $userId) {
                throw new ValidationException('This cart belongs to another user');
            }
            return $cart;
        }
        return Cart::create([
            'session_id' => $sessionId,
            'user_id' => $userId,
        ]);
    }

    public function addItem(array $data): array
    {
        $product = Product::findOrFail($data['product_id']);
        $cart = $this->getOrCreateCart($data['user_id'] ?? null, $data['session_id']);

        $existingItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->first();

        if ($existingItem) {
            throw new \DomainException('This item is already in your cart. Please update the quantity instead.');
        }

        $cartItem = DB::transaction(function () use ($cart, $product, $data) {
            return CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => $data['quantity'],
            ]);
        });

        $cartItem->load('product');

        $quantity = $data['quantity'];
        $quantityText = $quantity > 1 ? "{$quantity} quantities" : "{$quantity} quantity";

        return [
            'message' => "{$quantityText} of {$product->title} added to cart successfully",
            'cart_item' => $cartItem,
        ];
    }

    public function updateItem(int $cartItemId, array $data): array
    {
        $cartItem = CartItem::with('product')->findOrFail($cartItemId);

        $cart = $this->getOrCreateCart($data['user_id'] ?? null, $data['session_id']);

        if ($cartItem->cart_id !== $cart->id) {
            throw new ValidationException('You do not have permission to update this cart item.');
        }

        DB::transaction(function () use ($cartItem, $data) {
            $cartItem->update([
                'quantity' => $data['quantity'],
            ]);
        });

        $cartItem->refresh();
        $quantity = $cartItem->quantity;
        $quantityText = $quantity > 1 ? "{$quantity} quantities" : "{$quantity} quantity";

        return [
            'message' => "{$cartItem->product->title} updated to {$quantityText}",
            'cart_item' => $cartItem,
        ];
    }

    public function removeItem(int $cartItemId, ?int $userId, string $sessionId): array
    {
        $cartItem = CartItem::findOrFail($cartItemId);
        $cart = $this->getOrCreateCart($userId, $sessionId);

        if ($cartItem->cart_id !== $cart->id) {
            throw new ValidationException('You do not have permission to remove this cart item.');
        }
        $productTitle = $cartItem->product->title;
        $cartItem->delete();

        return [
            'message' => "{$productTitle} has been removed from cart successfully",
        ];
    }

    public function clearCart(?int $userId, string $sessionId): void
    {
        $cart = $this->getOrCreateCart($userId, $sessionId);
        CartItem::where('cart_id', $cart->id)->delete();
    }

    public function getCart(?int $userId, string $sessionId): ?Cart
    {
        return Cart::with(['items.product'])
            ->where('session_id', $sessionId)
            ->when($userId !== null, fn($q) => $q->where('user_id', $userId))
            ->first();
    }

    // public function validateCart(Cart $cart): array
    // {
    //     $issues = [];

    //     foreach ($cart->items as $item) {
    //         if (!$item->isAvailable()) {
    //             $issues[] = [
    //                 'item_id' => $item->id,
    //                 'product_id' => $item->product_id,
    //                 'product_title' => $item->product->title,
    //                 'issue' => 'Product is out of stock or unavailable',
    //                 'requested_quantity' => $item->quantity,
    //                 'available_stock' => $item->product->stock,
    //             ];
    //         }
    //     }

    //     return $issues;
    // }
}
