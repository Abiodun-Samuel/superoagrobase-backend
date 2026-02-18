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
    protected function getBaseQuery(): Builder
    {
        return Order::query()
            ->with([
                'user:id,first_name,last_name,email,phone_number,avatar',
                'items' => function ($query) {
                    $query->select([
                        'id',
                        'order_id',
                        'product_id',
                        'quantity',
                        'price_at_purchase',
                        'subtotal',
                        'created_at',
                        'updated_at'
                    ])->with([
                        'product:id,slug,title,image,pack_size,price,stock'
                    ]);
                },
                'transactions' => function ($query) {
                    $query->select([
                        'id',
                        'order_id',
                        'reference',
                        'transaction_reference',
                        'amount',
                        'status',
                        'channel',
                        'currency',
                        'metadata',
                        'ip_address',
                        'created_at',
                        'updated_at'
                    ])->latest();
                }
            ])
            ->select([
                'id',
                'reference',
                'user_id',
                'delivery_details',
                'delivery_method',
                'payment_method',
                'payment_gateway',
                'payment_status',
                'subtotal',
                'tax',
                'tax_rate',
                'shipping',
                'total',
                'status',
                'confirmed_at',
                'paid_at',
                'shipped_at',
                'delivered_at',
                'cancelled_at',
                'created_at',
                'updated_at'
            ]);
    }

    public function getOrders(array $filters = [], ?int $userId = null): Collection
    {
        $query = $this->getBaseQuery();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $this->applyFilters($query, $filters);

        if (empty($filters['sort_by'])) {
            $query->latest('created_at');
        }

        return $query->get();
    }

    public function getUserOrders(User $user, array $filters = []): Collection
    {
        $query = $this->getBaseQuery()->where('user_id', $user->id);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        return $query->latest('created_at')->get();
    }

    public function getOrderByReference(string $reference): Order
    {
        return $this->getBaseQuery()
            ->where('reference', $reference)
            ->firstOrFail();
    }

    public function getUserOrderByReference(User $user, string $reference): Order
    {
        return $this->getBaseQuery()
            ->where('user_id', $user->id)
            ->where('reference', $reference)
            ->firstOrFail();
    }

    public function createOrder(User $user, array $orderData, array $cartItems): Order
    {
        return DB::transaction(function () use ($user, $orderData, $cartItems) {
            $deliveryDetails = $orderData['delivery_details'];
            $pricing = $orderData['pricing'];

            $order = Order::create([
                'user_id' => $user->id,
                'delivery_details' => $deliveryDetails,
                'delivery_method' => $orderData['delivery_method'],
                'payment_method' => $orderData['payment_method'],
                'payment_gateway' => $orderData['payment_gateway'] ?? null,
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

    public function updateOrderStatus(Order $order, string $status): Order
    {
        DB::beginTransaction();

        try {
            $updates = ['status' => $status];

            switch ($status) {
                case 'confirmed':
                    if (!$order->confirmed_at) {
                        $updates['confirmed_at'] = now();
                    }
                    break;
                case 'shipped':
                    if (!$order->shipped_at) {
                        $updates['shipped_at'] = now();
                    }
                    break;
                case 'delivered':
                    if (!$order->delivered_at) {
                        $updates['delivered_at'] = now();
                    }
                    break;
                case 'cancelled':
                    if (!$order->cancelled_at) {
                        $updates['cancelled_at'] = now();
                    }
                    break;
            }

            $order->update($updates);

            DB::commit();

            $order->load(['user', 'items.product', 'transactions']);

            return $order;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateOrder(Order $order, array $data): Order
    {
        DB::beginTransaction();

        try {
            $order->update($data);

            DB::commit();

            $order->load(['user', 'items.product', 'transactions']);

            return $order;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteOrder(Order $order): bool
    {
        return $order->delete();
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['reference'])) {
            $query->where('reference', 'like', '%' . $filters['reference'] . '%');
        }

        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        if (!empty($filters['payment_status'])) {
            if (is_array($filters['payment_status'])) {
                $query->whereIn('payment_status', $filters['payment_status']);
            } else {
                $query->where('payment_status', $filters['payment_status']);
            }
        }

        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (!empty($filters['delivery_method'])) {
            $query->where('delivery_method', $filters['delivery_method']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        if (!empty($filters['min_total'])) {
            $query->where('total', '>=', $filters['min_total']);
        }

        if (!empty($filters['max_total'])) {
            $query->where('total', '<=', $filters['max_total']);
        }

        if (!empty($filters['confirmed_from'])) {
            $query->whereDate('confirmed_at', '>=', $filters['confirmed_from']);
        }

        if (!empty($filters['confirmed_to'])) {
            $query->whereDate('confirmed_at', '<=', $filters['confirmed_to']);
        }

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
