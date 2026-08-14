<?php

namespace App\Wms\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a WmsProduct's on-hand stock changes — via GRN inbound,
 * WmsAdjustment, WmsOutbound picking, cycle-count correction, etc.
 *
 * The listener path (Phase 7 Commerce/PushStockToConnectedChannelsListener)
 * subscribes to this event and dispatches per-connection PushStockJobs
 * for every active CommerceConnection whose provider implements
 * `SupportsInventorySync`.
 *
 * `previousQuantity` may be null when the event fires from an insert
 * (previously untracked stock row). `reason` is a free-form hint —
 * 'grn' / 'adjustment' / 'outbound' / 'cycle_count' — used for logging
 * + diagnostics, not for logic.
 *
 * The event carries the SKU + merchant_id + total quantity across ALL
 * stock locations for the product so downstream listeners don't need
 * to re-query. Reserved quantity is intentionally excluded — the
 * storefront should see "sellable stock" which we treat as total_qty
 * (aggressive; conservative would be `total - reserved`, but that's a
 * pricing/oversell tradeoff to make explicit in Phase 7.5).
 */
class StockChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int     $companyId,
        public readonly int     $productId,
        public readonly ?string $sku,
        public readonly ?int    $merchantId,
        public readonly ?int    $previousQuantity,
        public readonly int     $newQuantity,
        public readonly string  $reason = 'unknown',
    ) {}
}
