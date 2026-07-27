<?php

namespace App\Salla\Services;

use App\Enums\Wms\FulfillmentStatus;
use App\Models\Backend\Parcel;
use App\Models\Backend\Wms\WmsFulfillment;
use App\Models\Backend\Wms\WmsFulfillmentItem;
use App\Models\Backend\Wms\WmsLocation;
use App\Models\Backend\Wms\WmsProduct;
use App\Salla\Models\Order as SallaOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Turns a Salla order + freshly-created Rushly parcel into a WMS
 * fulfillment row + pick items, so the tenant's warehouse ops team
 * sees the order in `/admin/wms/fulfillment` right after the Salla
 * webhook lands.
 *
 * Only fires when the tenant's Salla integration is configured with
 * `service_type = delivery_and_fulfillment` (see
 * IntegrationsController::update()).
 *
 * SKU matching: Salla items[].sku → wms_products.sku (scoped to the
 * tenant's company_id). Unknown SKUs are logged and skipped — a
 * merchant may sell some Rushly-only products via Salla that don't
 * exist in the WMS catalogue, or vice versa; that shouldn't block
 * the fulfillment from being created at all.
 *
 * Location strategy: the first active `wms_locations` row for the
 * tenant's default hub (or the tenant-wide first active location if
 * no hub is set on the merchant). Real routing rules can come later.
 */
class SallaWmsFulfillmentService
{
    public function createFor(SallaOrder $sallaOrder, Parcel $parcel): ?WmsFulfillment
    {
        $companyId = $parcel->company_id;
        $items     = (array) ($sallaOrder->payload['items'] ?? []);

        if (empty($items)) {
            Log::info('salla.wms.fulfillment.no_items', [
                'salla_order_id' => $sallaOrder->id,
                'parcel_id'      => $parcel->id,
            ]);
            return null;
        }

        $location = $this->pickLocation($companyId, $parcel->hub_id ?? null);
        if (! $location) {
            Log::warning('salla.wms.fulfillment.no_location', [
                'company_id' => $companyId,
                'hint'       => 'Seed at least one wms_locations row before enabling delivery+fulfillment for this tenant.',
            ]);
            return null;
        }

        return DB::transaction(function () use ($sallaOrder, $parcel, $companyId, $items, $location) {
            $fulfillment = WmsFulfillment::create([
                'company_id'         => $companyId,
                'fulfillment_number' => $this->generateFulfillmentNumber(),
                'parcel_id'          => $parcel->id,
                'hub_id'             => $parcel->hub_id ?: $location->hub_id,
                'merchant_id'        => $parcel->merchant_id,
                'status'             => FulfillmentStatus::PENDING,
                'sla_deadline'       => now()->addDay(),
                'notes'              => "Auto-created from Salla order {$sallaOrder->reference_id} (salla_order_id={$sallaOrder->salla_order_id})",
            ]);

            $matched = 0;
            $unknown = [];
            foreach ($items as $item) {
                $sku = (string) ($item['sku'] ?? '');
                $qty = (int)    ($item['quantity'] ?? 1);
                if ($sku === '' || $qty <= 0) continue;

                $product = WmsProduct::query()
                    ->where('company_id', $companyId)
                    ->where('sku', $sku)
                    ->first();

                if (! $product) {
                    $unknown[] = $sku;
                    continue;
                }

                WmsFulfillmentItem::create([
                    'fulfillment_id'    => $fulfillment->id,
                    'product_id'        => $product->id,
                    'location_id'       => $location->id,
                    'quantity_required' => $qty,
                    'quantity_picked'   => 0,
                    'status'            => 'pending',
                ]);
                $matched++;
            }

            Log::info('salla.wms.fulfillment.created', [
                'salla_order_id'     => $sallaOrder->id,
                'parcel_id'          => $parcel->id,
                'fulfillment_id'     => $fulfillment->id,
                'fulfillment_number' => $fulfillment->fulfillment_number,
                'matched_items'      => $matched,
                'unknown_skus'       => $unknown,
            ]);

            return $fulfillment;
        });
    }

    /**
     * `WMS-YYYYMMDD-XXXXXX` matches the format the existing
     * WMS module uses (see the seed row from 2026-07-01).
     */
    private function generateFulfillmentNumber(): string
    {
        do {
            $candidate = 'WMS-' . now()->format('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        } while (WmsFulfillment::where('fulfillment_number', $candidate)->exists());
        return $candidate;
    }

    private function pickLocation(int $companyId, ?int $hubId): ?WmsLocation
    {
        $q = WmsLocation::query()->where('company_id', $companyId)->where('is_active', 1);
        if ($hubId) $q->where('hub_id', $hubId);
        return $q->orderBy('id')->first()
            // Fallback: any active location for the tenant, ignoring hub scope.
            ?: WmsLocation::query()->where('company_id', $companyId)->where('is_active', 1)->orderBy('id')->first();
    }
}
