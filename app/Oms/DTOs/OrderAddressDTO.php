<?php

namespace App\Oms\DTOs;

/**
 * Order shipping / billing address. Carries both the provider-reported
 * strings AND the best-effort resolution against local cities/areas
 * tables (via AddressResolver).
 *
 * When `resolvedCityId` is null it means AddressResolver couldn't match
 * — the OMS layer decides whether to accept the order anyway (freeform
 * city string still usable for manual dispatch) or 4xx it back.
 */
final class OrderAddressDTO
{
    public function __construct(
        public readonly ?string $name,
        public readonly ?string $phone,
        public readonly ?string $line1,
        public readonly ?string $line2,
        public readonly ?string $cityName,          // Provider-reported city string
        public readonly ?string $areaName,          // Provider-reported area / district string
        public readonly ?string $region,            // State / province / emirate
        public readonly ?string $country,
        public readonly ?string $postcode,
        public readonly ?int    $resolvedCityId,    // FK into local cities.id (null when unmatched)
        public readonly ?int    $resolvedAreaId,    // FK into local areas.id (null when unmatched)
        public readonly array   $extra = [],
    ) {}

    public function toArray(): array
    {
        return [
            'name'              => $this->name,
            'phone'             => $this->phone,
            'line1'             => $this->line1,
            'line2'             => $this->line2,
            'city_name'         => $this->cityName,
            'area_name'         => $this->areaName,
            'region'            => $this->region,
            'country'           => $this->country,
            'postcode'          => $this->postcode,
            'resolved_city_id'  => $this->resolvedCityId,
            'resolved_area_id'  => $this->resolvedAreaId,
            'extra'             => $this->extra,
        ];
    }

    public function withResolved(?int $cityId, ?int $areaId): self
    {
        return new self(
            name: $this->name,
            phone: $this->phone,
            line1: $this->line1,
            line2: $this->line2,
            cityName: $this->cityName,
            areaName: $this->areaName,
            region: $this->region,
            country: $this->country,
            postcode: $this->postcode,
            resolvedCityId: $cityId,
            resolvedAreaId: $areaId,
            extra: $this->extra,
        );
    }
}
