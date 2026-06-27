<?php

namespace App\Services\Performance;

/**
 * Great-circle distance between two lat/lng points using the haversine
 * formula. Returns metres. Returns null if any coordinate is missing.
 *
 * Used by ParcelInstrumentationObserver to stamp parcels.distance_m at
 * create time, and by the backfill command for historical rows.
 */
class HaversineDistance
{
    private const EARTH_M = 6_371_000.0;

    public static function metres(?float $lat1, ?float $lng1, ?float $lat2, ?float $lng2): ?int
    {
        if ($lat1 === null || $lng1 === null || $lat2 === null || $lng2 === null) return null;
        if ($lat1 == 0 && $lng1 == 0) return null;
        if ($lat2 == 0 && $lng2 == 0) return null;

        $rad1 = deg2rad($lat1);
        $rad2 = deg2rad($lat2);
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2 + cos($rad1) * cos($rad2) * sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return (int) round(self::EARTH_M * $c);
    }
}
