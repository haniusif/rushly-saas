<?php

namespace App\Commerce\Listeners;

use App\Commerce\Jobs\PushStockJob;
use App\Commerce\Models\CommerceConnection;
use App\Wms\Events\StockChanged;
use Illuminate\Support\Facades\Log;

/**
 * On WMS StockChanged, dispatches one PushStockJob per active
 * CommerceConnection in the same tenant whose provider advertises
 * `inventory_sync` in its supports[] array.
 *
 * Merchant-scoping: if the event carries `merchantId`, only connections
 * feeding THAT merchant are notified — a product owned by merchant A
 * doesn't leak stock updates to merchant B's Salla connection. When
 * `merchantId` is null (rare — usually means WmsProduct has no
 * merchant), we fan out to every connection in the tenant.
 *
 * SKU check: if the product has no SKU, we can't route it to a
 * storefront that keys on SKU (which is every provider we support in
 * Phase 7). Skip cleanly.
 *
 * Not queued itself — dispatching jobs is cheap. The heavy work
 * (HTTP calls) happens inside PushStockJob, which is queued.
 */
class PushStockToConnectedChannelsListener
{
    public function handle(StockChanged $event): void
    {
        if (! $event->sku) {
            // No SKU on the product → nothing storefronts can key on.
            return;
        }

        $query = CommerceConnection::query()
            ->with('provider')
            ->where('company_id', $event->companyId)
            ->where('status', 'active')
            ->whereHas('provider', function ($q) {
                // LIKE match on the JSON-encoded supports[] — works on
                // both MySQL and sqlite. False-positive risk is nil
                // because our capability tokens don't contain each other
                // as substrings.
                $q->where('supports', 'like', '%"inventory_sync"%');
            });

        if ($event->merchantId !== null) {
            $query->where(function ($q) use ($event) {
                // Match connections wired to this merchant, OR connections
                // with no merchant binding (feed all tenant merchants).
                $q->where('merchant_id', $event->merchantId)
                  ->orWhereNull('merchant_id');
            });
        }

        $connections = $query->get();
        if ($connections->isEmpty()) {
            return;
        }

        $updates = [[
            'sku'      => $event->sku,
            'quantity' => $event->newQuantity,
        ]];

        foreach ($connections as $connection) {
            PushStockJob::dispatch($connection->id, $updates)
                ->onQueue((string) config('commerce.queue.name', 'commerce'));
        }

        Log::info('commerce.push_stock.dispatched', [
            'company_id'    => $event->companyId,
            'sku'           => $event->sku,
            'new_quantity'  => $event->newQuantity,
            'reason'        => $event->reason,
            'connections'   => $connections->pluck('id')->all(),
        ]);
    }
}
