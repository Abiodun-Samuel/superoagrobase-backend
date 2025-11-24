<?php

namespace App\Enums;

enum Status: string
{
    case PENDING = 'pending';             // Awaiting verification/activation
    case ACTIVE = 'active';               // Operational
    case INACTIVE = 'inactive';           // Temporarily disabled
    case SUSPENDED = 'suspended';         // Temporarily blocked
    case DEACTIVATED = 'deactivated';     // Fully closed
    case BANNED = 'banned';
    case OUT_OF_STOCK = 'out_of_stock';               // Permanently blocked
    case IN_STOCK = 'in_stock';               // Permanently blocked
    case ONLINE = 'online';               // Permanently blocked

        // --- Student-specific academic states ---
    case ENROLLED = 'enrolled';           // Currently studying
    case GRADUATED = 'graduated';         // Completed school
    case EXPELLED = 'expelled';           // Expelled
    case DROPPED_OUT = 'dropped_out';     // Voluntarily left

        // --- School lifecycle ---
    case APPROVED = 'approved';           // Verified and operational
    case REJECTED = 'rejected';           // Rejected by system/admin
    case UNDER_REVIEW = 'under_review';   // Awaiting admin approval
    case ARCHIVED = 'archived';           // Old/no longer accessible

        // --- Subscription lifecycle ---
    case TRIAL = 'trial';                 // Trial period active
    case ACTIVE_SUBSCRIPTION = 'active_subscription';  // Paid and active
    case EXPIRED = 'expired';             // Subscription expired
    case CANCELLED = 'cancelled';         // Canceled by user or system
    case BLOCKED = 'blocked';             // Disabled due to policy

        // --- Plan status ---
    case ENABLED = 'enabled';             // Plan is publicly selectable
    case DISABLED = 'disabled';           // Plan hidden or restricted
    case DRAFT = 'draft';                 // Plan is still being configured.
    case PAUSED = 'paused';                 // Temporarily paused by system or admin (optional, for violations).
    case PAST_DUE = 'past_due';                 // Payment failed; pply before deactivation.
    case UPGRADED = 'upgraded';                 // Payment failed; pply before deactivation.
    case DOWNGRADED = 'downgraded';                 // Payment failed; pply before deactivation.
    case RENEWED = 'renewed';                 // Payment failed; pply before deactivation.

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
