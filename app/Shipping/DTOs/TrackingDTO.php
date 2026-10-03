<?php

namespace App\Shipping\DTOs;

/**
 * One status event. Either the latest (returned from getStatus) or one item in
 * the timeline (getTracking returns TrackingDTO[]).
 */
final class TrackingDTO
{
    public function __construct(
        public readonly string  $remoteShipmentId,
        public readonly string  $rawStatus,           // Provider's status string (e.g. "OUT_FOR_DELIVERY")
        public readonly ?int    $localStatus,         // Mapped ParcelStatus constant, null if unknown
        public readonly ?string $description = null,  // Human-readable note
        public readonly ?string $occurredAt  = null,  // ISO 8601
        public readonly array   $raw         = [],    // Full provider event payload
    ) {}
}
