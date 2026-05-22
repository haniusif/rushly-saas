<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait TrackingTrait
{
    public function generateTrackingId($id)
    {
        return Str::upper(settings()->par_track_prefix) . random_int(11111111, 99999999) . $id;
    }
}
