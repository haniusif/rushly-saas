<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait TrackingTrait
{
    /**
     * The single tracking-id generator used everywhere. Format:
     *   <prefix>(10000000000 + parcelId)
     * so the parcel id fills the trailing digits of a fixed 11-digit base —
     * e.g. prefix "RL-" + parcel id 2100 => "RL-10000002100". Prefix falls back
     * to "RL-" when the tenant hasn't set one.
     *
     * Requires the parcel id, so callers assign tracking AFTER the parcel is
     * inserted (save first, then set + save).
     */
    public function generateTrackingId($id)
    {
        return Str::upper($this->trackingPrefix()) . (10000000000 + (int) $id);
    }

    /**
     * @deprecated Use generateTrackingId($parcel->id) after insert. Kept only
     * for any legacy caller; produces a non-id, non-standard tracking id.
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
