<?php

namespace App\Shipping\Listeners;

use App\Enums\ParcelStatus;
use App\Models\Backend\Parcel;
use App\Repositories\Parcel\ParcelInterface;
use App\Shipping\Events\ShipmentStatusChanged;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Bridges the new module's status updates back to the existing Parcel
 * lifecycle. Without this, status changes only live on the Shipment row and
 * the broader app (NDR, abnormal detection, merchant dashboards) wouldn't see
 * them.
 */
class UpdateParcelStatus
{
    public function __construct(
        private readonly ParcelInterface $parcels,
    ) {}

    public function handle(ShipmentStatusChanged $event): void
    {
        $shipment = $event->shipment;
        $parcel   = Parcel::find($shipment->parcel_id);
        if (! $parcel) return;

        $new = $event->newStatus;
        if ((int) $parcel->status === $new) return;

        try {
            if ($new === ParcelStatus::DELIVERED && (int) $parcel->status === ParcelStatus::DELIVERY_MAN_ASSIGN) {
                // Routes through the repository so balances/notifications fire
                $this->parcels->parcelDelivered($parcel->id, new Request([
                    'note'              => 'Shipping: ' . $shipment->current_status_raw,
                    'send_sms_customer' => 'off',
                    'send_sms_merchant' => 'off',
                ]));
                return;
            }

            $parcel->status = $new;
            if ($new === ParcelStatus::CANCELLED) {
                $parcel->cancellationReason = 'Shipping: ' . $shipment->current_status_raw;
            }
            $parcel->save();
        } catch (\Throwable $e) {
            Log::warning('shipping.update_parcel_status_failed', [
                'shipment_id' => $shipment->id,
                'parcel_id'   => $parcel->id,
                'error'       => $e->getMessage(),
            ]);
        }
    }
}
