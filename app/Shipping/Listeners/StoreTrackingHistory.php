<?php

namespace App\Shipping\Listeners;

use App\Models\Backend\ParcelEvent;
use App\Shipping\Events\ShipmentStatusChanged;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Append a row to parcel_events so the timeline UI shows every shipping
 * status transition without needing to query the Shipment table separately.
 *
 * The model's own `updated` hook handles CANCELLED events (writes a timeline
 * entry from $parcel->cancellationReason), so we skip those to avoid dupes.
 */
class StoreTrackingHistory
{
    public function handle(ShipmentStatusChanged $event): void
    {
        $new = $event->newStatus;
        if ($new === \App\Enums\ParcelStatus::CANCELLED) return;

        try {
            $e = new ParcelEvent();
            $e->parcel_id     = $event->shipment->parcel_id;
            $e->parcel_status = $new;
            $e->note          = 'Shipping: ' . $event->shipment->current_status_raw;
            $e->created_by    = Auth::id();
            $e->save();
        } catch (\Throwable $ex) {
            Log::warning('shipping.store_tracking_history_failed', [
                'shipment_id' => $event->shipment->id,
                'error'       => $ex->getMessage(),
            ]);
        }
    }
}
