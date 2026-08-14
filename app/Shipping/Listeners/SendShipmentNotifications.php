<?php

namespace App\Shipping\Listeners;

use App\Shipping\Events\ShipmentDelivered;
use Illuminate\Support\Facades\Log;

/**
 * Hook point for merchant/customer notifications on terminal shipment events.
 * Intentionally minimal — wires into the existing NotificationDispatcher /
 * SMS pipeline rather than reimplementing notification logic here.
 *
 * Today: log-only. Real SMS/email is gated by the parcel-level send_sms_*
 * flags that ParcelRepository::parcelDelivered already respects, so this
 * listener is reserved for *additional* shipping-specific notifications
 * (e.g. "your courier has updated your tracking number").
 */
class SendShipmentNotifications
{
    public function handle(ShipmentDelivered $event): void
    {
        Log::info('shipping.delivered_notification_hook', [
            'shipment_id' => $event->shipment->id,
            'parcel_id'   => $event->shipment->parcel_id,
        ]);
    }
}
