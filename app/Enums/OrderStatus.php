<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';                   // Order created, awaiting payment
    case PENDING_PAYMENT = 'pending_payment';   // Explicitly waiting for payment
    case CONFIRMED = 'confirmed';               // Payment received, order confirmed
    case PROCESSING = 'processing';             // Order being prepared/packed
    case READY_FOR_PICKUP = 'ready_for_pickup'; // Available for customer pickup
    case SHIPPED = 'shipped';                   // Order dispatched/in transit
    case OUT_FOR_DELIVERY = 'out_for_delivery'; // Final delivery stage
    case DELIVERED = 'delivered';               // Successfully delivered
    case COMPLETED = 'completed';               // Order fulfilled and closed
    case CANCELLED = 'cancelled';               // Order cancelled
    case REFUNDED = 'refunded';                 // Order refunded
    case FAILED = 'failed';                     // Order failed (payment/processing)
    case ON_HOLD = 'on_hold';                   // Temporarily paused
    case PARTIALLY_SHIPPED = 'partially_shipped'; // Some items shipped
    case RETURNED = 'returned';                 // Order returned by customer
    case PARTIALLY_RETURNED = 'partially_returned'; // Some items returned

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PENDING_PAYMENT => 'Awaiting Payment',
            self::CONFIRMED => 'Confirmed',
            self::PROCESSING => 'Processing',
            self::READY_FOR_PICKUP => 'Ready for Pickup',
            self::SHIPPED => 'Shipped',
            self::OUT_FOR_DELIVERY => 'Out for Delivery',
            self::DELIVERED => 'Delivered',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
            self::REFUNDED => 'Refunded',
            self::FAILED => 'Failed',
            self::ON_HOLD => 'On Hold',
            self::PARTIALLY_SHIPPED => 'Partially Shipped',
            self::RETURNED => 'Returned',
            self::PARTIALLY_RETURNED => 'Partially Returned',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DELIVERED, self::COMPLETED => 'green',
            self::CONFIRMED, self::PROCESSING, self::SHIPPED => 'blue',
            self::PENDING, self::PENDING_PAYMENT, self::READY_FOR_PICKUP, self::OUT_FOR_DELIVERY => 'yellow',
            self::CANCELLED, self::FAILED => 'red',
            self::REFUNDED, self::RETURNED, self::PARTIALLY_RETURNED => 'orange',
            self::ON_HOLD, self::PARTIALLY_SHIPPED => 'gray',
        };
    }
}
