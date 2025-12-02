<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';           // Payment not yet processed
    case PAID = 'paid';                 // Payment successful
    case ONLINE = 'online';                 // Payment successful
    case FAILED = 'failed';             // Payment failed
    case REFUNDED = 'refunded';         // Payment refunded
    case PARTIALLY_REFUNDED = 'partially_refunded';  // Partial refund issued
    case AUTHORIZED = 'authorized';     // Payment authorized but not captured
    case CAPTURED = 'captured';         // Payment captured after authorization
    case VOIDED = 'voided';            // Authorization voided before capture
    case PROCESSING = 'processing';     // Payment being processed by gateway
    case DECLINED = 'declined';         // Payment declined by bank/card issuer
    case EXPIRED = 'expired';           // Payment session/intent expired
    case DISPUTED = 'disputed';         // Chargeback/dispute initiated
    case CANCELLED = 'cancelled';       // Payment cancelled by user

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending Payment',
            self::PAID => 'Paid',
            self::FAILED => 'Failed',
            self::ONLINE => 'Paid online',
            self::REFUNDED => 'Refunded',
            self::PARTIALLY_REFUNDED => 'Partially Refunded',
            self::AUTHORIZED => 'Authorized',
            self::CAPTURED => 'Captured',
            self::VOIDED => 'Voided',
            self::PROCESSING => 'Processing',
            self::DECLINED => 'Declined',
            self::EXPIRED => 'Expired',
            self::DISPUTED => 'Disputed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PAID, self::CAPTURED => 'green',
            self::PENDING, self::AUTHORIZED, self::PROCESSING => 'yellow',
            self::FAILED, self::DECLINED, self::VOIDED => 'red',
            self::REFUNDED, self::PARTIALLY_REFUNDED => 'orange',
            self::DISPUTED => 'purple',
            self::EXPIRED, self::CANCELLED => 'gray',
        };
    }
}
