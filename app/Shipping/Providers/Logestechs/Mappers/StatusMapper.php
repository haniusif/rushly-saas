<?php

namespace App\Shipping\Providers\Logestechs\Mappers;

use App\Enums\ParcelStatus;

/**
 * Maps a Logestechs status string (Java-style SCREAMING_SNAKE) to the local
 * ParcelStatus enum. Tolerant substring matching — Logestechs status vocabulary
 * isn't formally documented; this mirrors the heuristic mapping that was in
 * the old LogestechsSyncTracking command.
 *
 * Unknown values return null so the caller can log + skip (calibrate the
 * mapping table from real responses over time).
 */
class StatusMapper
{
    public static function map(string $raw): ?int
    {
        $s = mb_strtoupper(trim($raw));

        if (str_contains($s, 'PARTIAL'))   return ParcelStatus::PARTIAL_DELIVERED;
        if (str_contains($s, 'DELIVERED') || str_contains($s, 'COMPLETED')) return ParcelStatus::DELIVERED;
        if (str_contains($s, 'CANCEL'))    return ParcelStatus::CANCELLED;

        if (str_contains($s, 'RETURN')) {
            if (str_contains($s, 'MERCHANT') || str_contains($s, 'SENDER') || str_contains($s, 'SHIPPER')) {
                return ParcelStatus::RETURN_RECEIVED_BY_MERCHANT;
            }
            return ParcelStatus::RETURN_TO_COURIER;
        }

        if (str_contains($s, 'OUT_FOR_DELIVERY') || str_contains($s, 'OUT FOR DELIVERY')
            || str_contains($s, 'WITH_DRIVER') || str_contains($s, 'ASSIGNED_TO_DELIVERY')) {
            return ParcelStatus::DELIVERY_MAN_ASSIGN;
        }

        if (str_contains($s, 'PICKED_UP') || str_contains($s, 'PICKED UP')
            || str_contains($s, 'COLLECTED') || str_contains($s, 'OUT_FOR_PICKUP')) {
            return ParcelStatus::RECEIVED_BY_PICKUP_MAN;
        }

        if (str_contains($s, 'AT_HUB') || str_contains($s, 'IN_HUB')
            || str_contains($s, 'RECEIVED_AT_HUB') || str_contains($s, 'AT_WAREHOUSE')
            || str_contains($s, 'AT_BRANCH')) {
            return ParcelStatus::RECEIVED_BY_HUB;
        }

        if (str_contains($s, 'IN_TRANSIT') || str_contains($s, 'IN TRANSIT')
            || str_contains($s, 'EN_ROUTE') || str_contains($s, 'TRANSFER')) {
            return ParcelStatus::TRANSFER_TO_HUB;
        }

        if (str_contains($s, 'FAILED') || str_contains($s, 'RESCHEDULE')
            || str_contains($s, 'POSTPONED')) {
            return ParcelStatus::DELIVERY_RE_SCHEDULE;
        }

        if (str_contains($s, 'PENDING') || str_contains($s, 'DRAFT')
            || str_contains($s, 'CREATED') || $s === 'NEW') {
            return ParcelStatus::PENDING;
        }

        return null;
    }
}
