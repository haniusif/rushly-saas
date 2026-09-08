<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait TrackingTrait
{
    public function generateTrackingId($id)
    {
        return Str::upper($this->trackingPrefix()) . random_int(11111111, 99999999) . $id;
    }

    /**
     * Standalone tracking id (no parcel id suffix). Used by the external
     * partner endpoints (Salla / Zid / WooCommerce) where the parcel hasn't
     * been inserted yet at the point we need the tracking id.
     */
    public function trackingId(): string
    {
        return Str::upper($this->trackingPrefix()) . random_int(11111111, 99999999);
    }

    /**
     * Give an already-inserted parcel the canonical tracking id.
     *
     * The external partner paths (Salla / Zid / WooCommerce / fulfillment)
     * set a provisional id inside Parcel::create, because the column must
     * not be null. This rewrites it once the row exists so every creation
     * path in the app ends up with the SAME shape - prefix, eight digits,
     * then the parcel id.
     */
    public function applyTrackingId($parcel)
    {
        if ($parcel && $parcel->id) {
            $parcel->update(['tracking_id' => $this->generateTrackingId($parcel->id)]);
        }
        return $parcel;
    }

    private function trackingPrefix(): string
    {
        try {
            $prefix = settings()->par_track_prefix ?? null;
        } catch (\Throwable) {
            $prefix = null;
        }
        return (string) ($prefix ?: 'RL-');
    }
}
