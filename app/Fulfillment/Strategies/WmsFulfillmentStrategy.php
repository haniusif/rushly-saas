<?php

namespace App\Fulfillment\Strategies;

use App\Enums\Wms\FulfillmentStatus as WmsStatus;
use App\Fulfillment\Bridges\OrderToParcelBridge;
use App\Fulfillment\Contracts\FulfillmentStrategyInterface;
use App\Fulfillment\Exceptions\StrategyRejectedException;
use App\Fulfillment\Models\Fulfillment;
use App\Models\Backend\Wms\WmsFulfillment;
use App\Oms\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Materialises an OMS Order into WMS pick/pack work.
 *
 * Pipeline:
 *   1. Bridge Order → Parcel (idempotent via `parcels.oms_order_id`)
 *   2. Create a WmsFulfillment row keyed to that Parcel, status=pending,
 *      targeting the route's hub_id
 *   3. Stamp `parcels.wms_fulfillment_id` so the existing WMS UIs
 *      surface the parcel under its fulfillment
 *   4. Stamp our own Fulfillment row: wms_fulfillment_id + parcel_id +
 *      external_reference (=WmsFulfillment.fulfillment_number) +
 *      status=in_progress
 *
 * Terminal status stays `in_progress` on our side — warehouse ops
 * drive the WmsFulfillment through picking → packing → dispatched via
 * the existing WMS UI, and a future Phase 7 listener on WMS
 * Dispatched will roll our Fulfillment forward to `completed`.
 *
 * Idempotency: the bridge dedupes Parcel creation. WmsFulfillment
 * lookup by parcel_id keeps this idempotent for retries — a Fulfillment
 * that already has a wms_fulfillment_id short-circuits.
 */
class WmsFulfillmentStrategy implements FulfillmentStrategyInterface
{
    public function __construct(
        private readonly OrderToParcelBridge $bridge,
    ) {}

    public function code(): string
    {
        return 'wms';
    }

    public function execute(Fulfillment $fulfillment, Order $order): void
    {
        // Idempotent retry: don't re-create WMS work if we've already
        // linked one to this Fulfillment.
        if ($fulfillment->wms_fulfillment_id) {
            Log::info('fulfillment.wms.already_linked', [
                'fulfillment_id'     => $fulfillment->id,
                'wms_fulfillment_id' => $fulfillment->wms_fulfillment_id,
            ]);
            return;
        }

        $parcel = $this->bridge->toParcel($order);

        DB::transaction(function () use ($fulfillment, $order, $parcel) {
            // Reuse an existing WmsFulfillment if one is already tied to
            // this parcel — happens when the bridge returned an existing
            // Parcel from a prior manual assign / re-processed webhook.
            $wms = WmsFulfillment::query()->where('parcel_id', $parcel->id)->first();
            if (! $wms) {
                $wms = WmsFulfillment::create([
                    'company_id'         => $order->company_id,
                    'fulfillment_number' => $this->generateFulfillmentNumber(),
                    'parcel_id'          => $parcel->id,
                    'merchant_id'        => $order->merchant_id,
                    'hub_id'             => $fulfillment->hub_id,   // may be null; warehouse assigns on pick
                    'status'             => WmsStatus::PENDING,
                    'notes'              => sprintf('OMS order #%d via FulfillmentRouter', $order->id),
                ]);
                // Also stamp the reverse pointer on the Parcel so the
                // existing WMS UI can find the fulfillment by parcel.
                $parcel->wms_fulfillment_id = $wms->id;
                $parcel->save();
            }

            $fulfillment->parcel_id          = $parcel->id;
            $fulfillment->wms_fulfillment_id = $wms->id;
            $fulfillment->external_reference = $wms->fulfillment_number;
            $fulfillment->status             = Fulfillment::STATUS_IN_PROGRESS;
            $fulfillment->started_at         = now();
            $fulfillment->last_error         = null;
            $fulfillment->payload            = array_merge((array) $fulfillment->payload, [
                'wms_fulfillment_number' => $wms->fulfillment_number,
                'wms_status'             => $wms->status,
                'parcel_tracking_id'     => $parcel->tracking_id,
                'hub_id'                 => $wms->hub_id,
            ]);
            $fulfillment->save();
        });

        Log::info('fulfillment.wms.dispatched', [
            'fulfillment_id'     => $fulfillment->id,
            'order_id'           => $order->id,
            'parcel_id'          => $fulfillment->parcel_id,
            'wms_fulfillment_id' => $fulfillment->wms_fulfillment_id,
        ]);
    }

    public function cancel(Fulfillment $fulfillment): void
    {
        if ($fulfillment->isTerminal()) {
            throw new StrategyRejectedException(
                'Fulfillment is already terminal — cannot cancel.',
                ['status' => $fulfillment->status],
            );
        }

        // Best-effort cancel of the underlying WmsFulfillment row.
        // Warehouses may reject the cancel if picking has started —
        // that's a business decision that lives in the WMS module.
        if ($fulfillment->wms_fulfillment_id) {
            $wms = WmsFulfillment::query()->find($fulfillment->wms_fulfillment_id);
            if ($wms && $wms->status !== WmsStatus::DISPATCHED) {
                $wms->status = WmsStatus::CANCELLED;
                $wms->save();
            }
        }

        $fulfillment->status = Fulfillment::STATUS_CANCELLED;
        $fulfillment->save();
    }

    /**
     * Human-readable, monotonic-ish fulfillment number. Format matches
     * the existing WMS convention ("WMS-<date>-<random>"). Uniqueness
     * isn't enforced here — collisions are astronomically unlikely
     * and the underlying WmsFulfillment doesn't have a UNIQUE constraint
     * on this column.
     */
    private function generateFulfillmentNumber(): string
    {
        return 'WMS-' . now()->format('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    }
}
