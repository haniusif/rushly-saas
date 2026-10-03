<?php

namespace App\Wms\Observers;

use App\Models\Backend\Wms\WmsProduct;
use App\Models\Backend\Wms\WmsStock;
use App\Wms\Events\StockChanged;
use Illuminate\Support\Facades\Event;

/**
 * Fires StockChanged whenever a WmsStock row changes. Consumers
 * (Commerce push-stock listener) then propagate to connected
 * storefronts.
 *
 * Fires on:
 *   - `updated`  — quantity change (most common: adjust, pick, receive)
 *   - `created`  — first stock row for a (product, location) pair
 *   - `deleted`  — stock removed (last row → total drops to 0)
 *
 * The event carries the WmsProduct's TOTAL across all locations
 * (`getTotalQtyAttribute()` sums `wms_stock.quantity` for the product)
 * — that's the number a storefront needs, not the per-location value
 * this row just mutated.
 *
 * Reason inference: reads the model's `updated` dirty attributes to
 * distinguish an increment (likely GRN or adjustment inbound) from a
 * decrement (likely pick or adjustment outbound). Best-effort — the
 * exact operation lives on wms_grn / wms_adjustment / wms_outbound;
 * the observer doesn't reach into those.
 */
class WmsStockObserver
{
    public function updated(WmsStock $stock): void
    {
        if (! $stock->wasChanged('quantity')) {
            return;
        }

        $previous = (int) $stock->getOriginal('quantity');
        $delta    = (int) $stock->quantity - $previous;
        $reason   = $delta > 0 ? 'increment' : ($delta < 0 ? 'decrement' : 'unknown');

        $this->fire($stock, $reason, previousProductTotal: null);
    }

    public function created(WmsStock $stock): void
    {
        // First stock row for this product/location — no previous total
        // to compare, but the product's total may have jumped from 0 to
        // the new value. Fire with a null previous.
        $this->fire($stock, 'grn', previousProductTotal: null);
    }

    public function deleted(WmsStock $stock): void
    {
        // Removing a stock row drops that quantity from the product's
        // total. Fire so downstream picks up the new (lower) total.
        $this->fire($stock, 'stock_removed', previousProductTotal: null);
    }

    private function fire(WmsStock $stock, string $reason, ?int $previousProductTotal): void
    {
        // Product total is authoritative; per-location changes roll up
        // into this. We compute it fresh to catch concurrent changes
        // that might have occurred between the row change and the
        // observer firing.
        $product = $stock->product ?: WmsProduct::query()->find($stock->product_id);
        if (! $product || ! $product->is_active) {
            // Skip inactive products — merchants have paused them from
            // being sold, we shouldn't push updates.
            return;
        }

        $newTotal = (int) $product->stocks()->sum('quantity');

        Event::dispatch(new StockChanged(
            companyId:        (int) $stock->company_id,
            productId:        (int) $product->id,
            sku:              $product->sku,
            merchantId:       $product->merchant_id !== null ? (int) $product->merchant_id : null,
            previousQuantity: $previousProductTotal,
            newQuantity:      $newTotal,
            reason:           $reason,
        ));
    }
}
