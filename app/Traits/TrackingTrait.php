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
