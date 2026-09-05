<?php

namespace App\Shipping\Listeners;

use App\Services\ThreePlCourierHandoff;
use App\Shipping\Events\ShipmentCreated;

/**
 * Shipping-module half of the 3PL handover: once a carrier has accepted a
 * shipment and returned an AWB, move the parcel to out-for-delivery under the
 * courier that represents that carrier.
 *
 * Listening on the event rather than patching the callers means the bulk
 * action, the single-parcel assign and any future caller all get this for
 * free - ShipmentService::executeCreate fires it from the one place a
 * shipment can succeed.
 *
 * The legacy carriers (Panda, Zajel, Aramex, J&T) never reach this module;
 * their half lives in the Parcels_3pl model's created hook.
 */
class HandOffToThreePlCourier
{
    public function __construct(
        private readonly ThreePlCourierHandoff $handoff,
    ) {}

    public function handle(ShipmentCreated $event): void
    {
        $shipment = $event->shipment;
        $shipment->loadMissing(['connection.provider', 'parcel']);

        $parcel = $shipment->parcel;
        $code   = $shipment->connection?->provider?->code;

        if (! $parcel || ! $code) {
            return;
        }

        $this->handoff->handoff(
            parcel:      $parcel,
            carrierCode: $code,
            // The connection's own pick wins; the service falls back to the
            // tenant's carrier map when it is not set.
            courierId:   $shipment->connection->courier_delivery_man_id,
            awb:         $shipment->awb_number,
        );
    }
}
