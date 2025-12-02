<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderService
{

    public function createOrder(User $user, array $orderData, array $cartItems): Order
    {
        return DB::transaction(function () use ($user, $orderData, $cartItems) {
            // Extract data
            $deliveryDetails = $orderData['delivery_details'];
            $pricing = $orderData['pricing'];

            // Create the order
            $order = Order::create([
                'user_id' => $user->id,
                'delivery_details' => $deliveryDetails,
                'delivery_method' => $orderData['delivery_method'],
                'payment_method' => $orderData['payment_method'],
                'subtotal' => $pricing['subtotal'],
                'tax' => $pricing['tax'],
                'tax_rate' => $pricing['tax_rate'],
                'shipping' => $pricing['shipping'],
                'total' => $pricing['total'],
                'status' => 'pending',
                'payment_status' => 'pending',
            ]);

            foreach ($cartItems as $item) {
                $this->createOrderItem($order, $item);
            }

            if ($orderData['save_delivery_details'] ?? false) {
                $this->saveUserDeliveryDetails($user, $deliveryDetails);
            }

            // $this->clearCart($user);

            return $order;
        });
    }

    protected function createOrderItem(Order $order, array $itemData): OrderItem
    {
        return OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $itemData['product_id'],
            'quantity' => $itemData['quantity'],
            'price_at_purchase' => $itemData['price_at_purchase'],
            'subtotal' => $itemData['subtotal'],
        ]);
    }

    protected function saveUserDeliveryDetails(User $user, array $deliveryDetails): void
    {
        $user->update([
            'shipping_details' => $deliveryDetails,
        ]);
    }

    protected function clearCart(User $user): void
    {
        $user->cartItems()->delete();
    }
}
