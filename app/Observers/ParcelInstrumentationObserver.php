<?php

namespace App\Observers;

use App\Models\Backend\Parcel;
use App\Services\Performance\HaversineDistance;
use App\Services\Performance\SlaProxy;
use Carbon\Carbon;

/**
 * Auto-stamps performance instrumentation columns on parcel create:
 *   - parcels.expected_delivery_at  = created_at + SLA hours for the delivery_type
 *   - parcels.distance_m            = haversine(pickup, customer)
 *
 * Idempotent: never overwrites values that are already set (so manual
 * overrides from the admin form / integrations win), and never touches
 * existing rows (creating-only listener).
 *
 * Uses the `creating` hook (not `created`) so the values land in the
 * INSERT statement and the row is consistent in a single trip to the DB.
 */
class ParcelInstrumentationObserver
{
    public function creating(Parcel $parcel): void
    {
        // Expected delivery target
        if (! $parcel->expected_delivery_at) {
            $hours = SlaProxy::hoursFor($parcel->delivery_type_id);
            // Use created_at if the row already has one (e.g. importer sets it),
            // otherwise now() — same as what the DB will store.
            $start = $parcel->created_at ?? Carbon::now();
            $parcel->expected_delivery_at = Carbon::parse($start)->addHours($hours);
        }

        // Straight-line distance (metres)
        if ($parcel->distance_m === null) {
            $parcel->distance_m = HaversineDistance::metres(
                (float) ($parcel->pickup_lat ?? 0),
                (float) ($parcel->pickup_long ?? 0),
                (float) ($parcel->customer_lat ?? 0),
                (float) ($parcel->customer_long ?? 0),
            );
        }
    }
}
