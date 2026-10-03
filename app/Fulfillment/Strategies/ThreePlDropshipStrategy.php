<?php

namespace App\Fulfillment\Strategies;

use App\Fulfillment\Bridges\OrderToParcelBridge;
use App\Fulfillment\Contracts\FulfillmentStrategyInterface;
use App\Fulfillment\Exceptions\StrategyRejectedException;
use App\Fulfillment\Models\Fulfillment;
use App\Oms\Models\Order;
use App\Shipping\Models\ShippingConnection;
use App\Shipping\Services\ShipmentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Dispatches an OMS Order to a 3PL courier via the Shipping module.
 *
 * Pipeline:
 *   1. Validate connection: exists + same tenant + status=active
 *   2. Bridge Order → Parcel (idempotent via `parcels.oms_order_id`)
 *   3. Call ShipmentService::dispatchCreate($parcel, $connection) —
 *      pre-creates a Shipment row in pending and queues the actual
 *      provider call via CreateShipmentJob
 *   4. Stamp Fulfillment.parcel_id + shipping_connection_id +
 *      external_reference (AWB when available) + status=in_progress
 *
 * Terminal transition to `completed`/`failed` happens out-of-band: a
 * Phase 7+ listener on Shipping's ShipmentCreated / ShipmentDelivered
 * events will roll the Fulfillment forward. For Phase 6.5 the
 * Fulfillment sits in in_progress once the shipment job is queued.
 *
 * Idempotency: ShipmentService itself checks `findByParcelAndConnection`
 * and returns the existing Shipment if one already exists with a
 * remote_shipment_id. Our own guard here — external_reference already
 * set — skips re-dispatch on retries.
 */
class ThreePlDropshipStrategy implements FulfillmentStrategyInterface
{
    public function __construct(
        private readonly OrderToParcelBridge $bridge,
        private readonly ShipmentService     $shipping,
    ) {}

    public function code(): string
    {
        return 'threepl_dropship';
    }

    public function execute(Fulfillment $fulfillment, Order $order): void
    {
        if (! $fulfillment->shipping_connection_id) {
            throw new StrategyRejectedException(
                'ThreePlDropshipStrategy requires shipping_connection_id on the route or fulfillment.',
                ['fulfillment_id' => $fulfillment->id],
            );
        }

        $connection = ShippingConnection::query()->find($fulfillment->shipping_connection_id);
        if (! $connection) {
            throw new StrategyRejectedException(
                "Shipping connection {$fulfillment->shipping_connection_id} not found.",
                ['fulfillment_id' => $fulfillment->id],
            );
        }
        if ((int) $connection->company_id !== (int) $order->company_id) {
            throw new StrategyRejectedException(
                'Cross-tenant fulfillment blocked — connection belongs to a different company.',
                ['order_company' => $order->company_id, 'connection_company' => $connection->company_id],
            );
        }
        if ($connection->status !== 'active') {
            throw new StrategyRejectedException(
                "Shipping connection {$connection->id} is not active (status={$connection->status}).",
                ['connection_id' => $connection->id],
            );
        }

        // Idempotent retry short-circuit.
        if ($fulfillment->parcel_id && $fulfillment->external_reference) {
            Log::info('fulfillment.threepl.already_dispatched', [
                'fulfillment_id'     => $fulfillment->id,
                'parcel_id'          => $fulfillment->parcel_id,
                'external_reference' => $fulfillment->external_reference,
            ]);
            return;
        }

        $parcel = $this->bridge->toParcel($order);

        // Hand off to Shipping. dispatchCreate is itself idempotent
        // (returns the existing Shipment if one is already created for
        // this parcel+connection pair) and queues CreateShipmentJob.
        $shipment = $this->shipping->dispatchCreate($parcel, $connection);

        DB::transaction(function () use ($fulfillment, $parcel, $connection, $shipment) {
            $fulfillment->parcel_id          = $parcel->id;
            $fulfillment->external_reference = $shipment->awb_number;   // null on first dispatch, filled when the job runs
            $fulfillment->status             = Fulfillment::STATUS_IN_PROGRESS;
            $fulfillment->started_at         = now();
            $fulfillment->last_error         = null;
            $fulfillment->payload            = array_merge((array) $fulfillment->payload, [
                'shipping_connection_id' => $connection->id,
                'shipping_provider'      => $connection->provider->code ?? null,
                'shipment_id'            => $shipment->id,
                'shipment_state'         => $shipment->state,
                'parcel_tracking_id'     => $parcel->tracking_id,
            ]);
            $fulfillment->save();
        });

        Log::info('fulfillment.threepl.dispatched', [
            'fulfillment_id' => $fulfillment->id,
            'order_id'       => $order->id,
            'parcel_id'      => $parcel->id,
            'shipment_id'    => $shipment->id,
            'connection'     => $connection->connection_name,
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

        // Best-effort cancel via Shipping. If a Shipment row exists for
        // (parcel, connection), dispatchCancel queues the provider
        // cancel. Providers that can't cancel post-pickup (Aramex
        // post-pickup, J&T post-pickup) will throw at that layer.
        if ($fulfillment->parcel_id && $fulfillment->shipping_connection_id) {
            $shipment = \App\Shipping\Models\Shipment::query()
                ->where('parcel_id', $fulfillment->parcel_id)
                ->where('connection_id', $fulfillment->shipping_connection_id)
                ->first();
            if ($shipment) {
                $this->shipping->dispatchCancel($shipment);
            }
        }

        $fulfillment->status = Fulfillment::STATUS_CANCELLED;
        $fulfillment->save();
    }
}
