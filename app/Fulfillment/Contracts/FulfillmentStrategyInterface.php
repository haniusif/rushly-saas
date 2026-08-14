<?php

namespace App\Fulfillment\Contracts;

use App\Fulfillment\Models\Fulfillment;
use App\Oms\Models\Order;

/**
 * A fulfillment strategy is the "how do we actually get this order out
 * the door" — WMS pick/pack, dropship via 3PL, ship from vendor's
 * warehouse, or hand it to the merchant to fulfill themselves.
 *
 * Called by FulfillmentService AFTER the Fulfillment row is created
 * (with status='pending' and the route decision recorded). The
 * strategy's `execute()` transitions the row to `in_progress`,
 * `completed`, or `failed` and populates strategy-specific fields
 * (shipping_connection_id, wms_fulfillment_id, external_reference, ...).
 *
 * `execute()` MAY be synchronous (MerchantSelfStrategy — no external
 * work needed) or MAY dispatch a queued job (WMS + 3PL — real work
 * happens out-of-band). The Fulfillment row's status reflects "current
 * belief about state", updated by the strategy or by a callback.
 *
 * Idempotency: FulfillmentService guards against double-execute on the
 * same Fulfillment row. Strategies don't need to guard themselves.
 *
 * Errors: strategies throw StrategyRejectedException for validation
 * problems (non-retryable). Any other throwable is treated as a
 * transient fault that gets stamped to `last_error` — the retry policy
 * for that is TBD (Phase 6.5).
 */
interface FulfillmentStrategyInterface
{
    /** Short machine code: 'wms', 'threepl_dropship', 'vendor_direct', 'merchant_self'. */
    public function code(): string;

    /**
     * Do the work. Mutates + saves the Fulfillment row directly.
     *
     * @throws \App\Fulfillment\Exceptions\StrategyRejectedException on validation failure
     */
    public function execute(Fulfillment $fulfillment, Order $order): void;

    /**
     * Attempt to cancel a fulfillment. Some strategies can (3PL: call
     * provider cancel; WMS: unlink WmsFulfillment); some can't past a
     * certain state (3PL post-pickup) and MUST throw StrategyRejectedException
     * with a clear reason.
     */
    public function cancel(Fulfillment $fulfillment): void;
}
