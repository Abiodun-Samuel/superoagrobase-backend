<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Transaction extends Model
{
    protected $fillable = [
        'order_id',
        'reference',
        'transaction_reference',
        'amount',
        'status',
        'channel',
        'currency',
        'metadata',
        'transaction_response',
        'ip_address',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the order that owns the transaction
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // ==================== QUERY SCOPES ====================

    /**
     * Scope to filter successful transactions
     */
    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->whereIn('status', ['success', 'paid']);
    }

    /**
     * Scope to filter pending transactions
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to filter failed transactions
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->whereIn('status', ['failed', 'declined']);
    }

    /**
     * Scope to filter by channel
     */
    public function scopeByChannel(Builder $query, string $channel): Builder
    {
        return $query->where('channel', $channel);
    }

    /**
     * Scope to filter by date range
     */
    public function scopeDateRange(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Scope to filter recent transactions
     */
    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // ==================== HELPER METHODS ====================

    /**
     * Check if transaction is successful
     */
    public function isSuccessful(): bool
    {
        return in_array($this->status, ['success', 'paid']);
    }

    /**
     * Check if transaction is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if transaction failed
     */
    public function isFailed(): bool
    {
        return in_array($this->status, ['failed', 'declined']);
    }

    /**
     * Check if transaction is refunded
     */
    public function isRefunded(): bool
    {
        return in_array($this->status, ['refunded', 'partially_refunded']);
    }

    /**
     * Get human-readable status label
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Pending',
            'success', 'paid' => 'Successful',
            'failed' => 'Failed',
            'refunded' => 'Refunded',
            'partially_refunded' => 'Partially Refunded',
            'authorized' => 'Authorized',
            'captured' => 'Captured',
            'voided' => 'Voided',
            'processing' => 'Processing',
            'declined' => 'Declined',
            'expired' => 'Expired',
            'disputed' => 'Disputed',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->status)
        };
    }

    /**
     * Get status color for UI
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            'success', 'paid', 'captured' => 'green',
            'pending', 'authorized', 'processing' => 'yellow',
            'failed', 'declined', 'voided' => 'red',
            'refunded', 'partially_refunded' => 'orange',
            'disputed' => 'purple',
            'expired', 'cancelled' => 'gray',
            default => 'gray'
        };
    }

    /**
     * Get formatted amount with currency
     */
    public function getFormattedAmount(): string
    {
        return $this->currency . ' ' . number_format($this->amount, 2);
    }

    /**
     * Get channel label
     */
    public function getChannelLabel(): string
    {
        return match ($this->channel) {
            'card' => 'Card Payment',
            'bank' => 'Bank Transfer',
            'ussd' => 'USSD',
            'qr' => 'QR Code',
            'bank_transfer' => 'Bank Transfer',
            'mobile_money' => 'Mobile Money',
            default => ucfirst($this->channel ?? 'Unknown')
        };
    }

    /**
     * Get transaction age in days
     */
    public function getAgeInDays(): int
    {
        return $this->created_at->diffInDays(now());
    }

    /**
     * Check if transaction is recent (within last 24 hours)
     */
    public function isRecent(): bool
    {
        return $this->created_at->isAfter(now()->subDay());
    }

    /**
     * Get gateway-specific metadata
     */
    public function getGatewayMetadata(?string $key = null): mixed
    {
        if (!$this->metadata) {
            return $key ? null : [];
        }

        return $key ? ($this->metadata[$key] ?? null) : $this->metadata;
    }

    // ==================== STATIC HELPERS ====================

    /**
     * Generate unique transaction reference
     */
    public static function generateReference(): string
    {
        do {
            $reference = 'TXN-' . strtoupper(uniqid()) . '-' . time();
        } while (self::where('reference', $reference)->exists());

        return $reference;
    }

    /**
     * Get total amount for successful transactions
     */
    public static function getTotalSuccessfulAmount(Builder $query = null): float
    {
        $query = $query ?? self::query();

        return (float) $query->successful()->sum('amount');
    }

    /**
     * Get statistics for a date range
     */
    public static function getStatistics(string $from, string $to): array
    {
        $query = self::whereBetween('created_at', [$from, $to]);

        return [
            'total_transactions' => $query->count(),
            'successful_transactions' => (clone $query)->successful()->count(),
            'failed_transactions' => (clone $query)->failed()->count(),
            'pending_transactions' => (clone $query)->pending()->count(),
            'total_amount' => (clone $query)->successful()->sum('amount'),
            'average_amount' => (clone $query)->successful()->avg('amount'),
        ];
    }
}
