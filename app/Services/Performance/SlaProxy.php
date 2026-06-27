<?php

namespace App\Services\Performance;

use App\Enums\DeliveryType;

/**
 * Proxy SLA targets for last-mile parcels.
 *
 * The DB has no explicit per-parcel expected_delivery_at, so we infer a target
 * window from delivery_type_id. These are deliberate, documented assumptions —
 * shown to users as "(proxy)" in the UI. Phase 4 will replace with real targets
 * captured at parcel creation.
 *
 * Hours measured from parcel `created_at` to `delivery_date`.
 */
class SlaProxy
{
    public const HOURS = [
        DeliveryType::SAMEDAY     => 24,
        DeliveryType::NEXTDAY     => 48,
        DeliveryType::SUBCITY     => 72,
        DeliveryType::OUTSIDECITY => 120,
    ];

    public const DEFAULT_HOURS = 72; // fallback when delivery_type missing

    public static function hoursFor(?int $deliveryTypeId): int
    {
        return self::HOURS[$deliveryTypeId] ?? self::DEFAULT_HOURS;
    }
}
