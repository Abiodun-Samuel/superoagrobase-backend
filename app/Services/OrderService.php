<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class OrderService
{
    public function getOrders(array $filters, ?int $userId = null): Collection
    {
        $query = Order::query()
            ->with([
                'user:id,first_name,last_name,email,phone_number,avatar',
                'items.product:id,slug,title,image,pack_size,price,stock',
                'transactions'
            ])
            ->latest('created_at');

        // User scope - users can only see their own orders
        if ($userId) {
            $query->where('user_id', $userId);
        }

        // Apply filters
        $this->applyFilters($query, $filters);

        return $query->get();
    }

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

            return $order;
        });
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        // Filter by reference
        if (!empty($filters['reference'])) {
            $query->where('reference', 'like', '%' . $filters['reference'] . '%');
        }

        // Filter by status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by payment status
        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        // Filter by payment method
        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        // Filter by delivery method
        if (!empty($filters['delivery_method'])) {
            $query->where('delivery_method', $filters['delivery_method']);
        }

        // Filter by user (admin only)
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Filter by date range
        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        // Filter by minimum total
        if (!empty($filters['min_total'])) {
            $query->where('total', '>=', $filters['min_total']);
        }

        // Filter by maximum total
        if (!empty($filters['max_total'])) {
            $query->where('total', '<=', $filters['max_total']);
        }

        // Search in delivery details
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereRaw("JSON_EXTRACT(delivery_details, '$.email') LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("JSON_EXTRACT(delivery_details, '$.phone_number') LIKE ?", ["%{$search}%"]);
            });
        }

        // Sort
        if (!empty($filters['sort_by'])) {
            $direction = $filters['sort_direction'] ?? 'desc';
            $query->orderBy($filters['sort_by'], $direction);
        }
    }

    protected function createOrderItem(Order $order, array $itemData): OrderItem
    {
        return OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $itemData['product_id'],
            'quantity' => $itemData['quantity'],
            'price_at_purchase' => $itemData['product']['price'],
            'subtotal' => $itemData['quantity'] * $itemData['product']['price'],
        ]);
    }

    protected function saveUserDeliveryDetails(User $user, array $deliveryDetails): void
    {
        $user->update([
            'shipping_details' => $deliveryDetails,
        ]);
    }
}
