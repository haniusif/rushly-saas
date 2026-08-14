<?php

namespace App\Oms\Enums;

/**
 * Canonical OMS order status. Orthogonal to the courier-side ParcelStatus
 * (which stays as the 34-state courier lifecycle). One Order can produce
 * 0..N Parcels; the Order's status tracks the merchant-side commerce
 * lifecycle, the Parcel's status tracks the courier movement.
 *
 * Kept intentionally small — 7 states cover every commerce channel we
 * expect to onboard. Phase 6 (FulfillmentRouter) drives most transitions.
 *
 * String values (not int) because we serialise them to `orders.status`
 * as varchar and the strings are self-documenting in payloads / logs.
 */
interface OrderStatus
{
    const PENDING         = 'pending';           // Order received, not yet acknowledged / paid
    const CONFIRMED       = 'confirmed';         // Merchant-side confirmation OR auto-confirmed on paid
    const IN_FULFILLMENT  = 'in_fulfillment';    // Handed off to Fulfillment Module (WMS pick, 3PL assign, ...)
    const SHIPPED         = 'shipped';           // At least one Parcel is out for delivery
    const DELIVERED       = 'delivered';         // All Parcels delivered
    const CANCELLED       = 'cancelled';         // Merchant / customer / provider cancellation
    const RETURNED        = 'returned';          // Fulfilled then returned

    const ALL = [
        self::PENDING, self::CONFIRMED, self::IN_FULFILLMENT,
        self::SHIPPED, self::DELIVERED, self::CANCELLED, self::RETURNED,
    ];

    const TERMINAL = [self::DELIVERED, self::CANCELLED, self::RETURNED];
}
