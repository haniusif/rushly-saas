<?php

namespace App\Oms\Enums;

/**
 * Aggregate fulfillment state across all of an order's items / parcels.
 *
 * Distinct from OrderStatus: an order can be `confirmed` (commerce state)
 * while still `unfulfilled` (fulfillment state). Phase 6 FulfillmentRouter
 * drives the transitions.
 *
 * Distinct from Wms\Enums\FulfillmentStatus (which tracks WMS-side
 * warehouse pick/pack lifecycle). This one is the higher-level umbrella;
 * a WMS-fulfilled order rolls up to `fulfilled` here.
 */
interface FulfillmentStatus
{
    const UNFULFILLED  = 'unfulfilled';   // No fulfillment attempt yet
    const IN_PROGRESS  = 'in_progress';   // Handed to WMS / 3PL, awaiting confirmation
    const PARTIAL      = 'partial';       // Some items shipped, others not
    const FULFILLED    = 'fulfilled';     // All items shipped
    const CANCELLED    = 'cancelled';     // Cancelled before ship-out

    const ALL = [
        self::UNFULFILLED, self::IN_PROGRESS, self::PARTIAL,
        self::FULFILLED, self::CANCELLED,
    ];
}
