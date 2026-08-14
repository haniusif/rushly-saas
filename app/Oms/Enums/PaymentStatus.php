<?php

namespace App\Oms\Enums;

/**
 * Canonical financial state of an order. Mapped from provider-native
 * financial_status strings by each OrderMapper (see the SallaOrderMapper
 * canonicalFinancialStatus() method).
 *
 * Phase 5: this drives display / filtering only. Phase 8+ may introduce
 * a separate `order_payments` table with per-payment records — for now
 * the aggregate lives on `orders.payment_status`.
 */
interface PaymentStatus
{
    const PENDING          = 'pending';           // Awaiting collection (typical for COD before delivery)
    const PAID             = 'paid';              // Fully paid via prepaid / card / wallet / COD-collected
    const PARTIALLY_PAID   = 'partially_paid';    // Split-payment providers
    const REFUNDED         = 'refunded';
    const VOIDED           = 'voided';            // Cancelled before payment settled
    const UNKNOWN          = 'unknown';           // Mapper couldn't canonicalise a provider-native status

    const ALL = [
        self::PENDING, self::PAID, self::PARTIALLY_PAID,
        self::REFUNDED, self::VOIDED, self::UNKNOWN,
    ];
}
