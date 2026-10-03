<?php

namespace App\Shipping\DTOs;

/**
 * Canonical address shape used both in shipment payloads and as the return
 * type of searchVillages(). Provider-specific IDs (cityId, regionId, villageId)
 * live in `extra` so the canonical fields stay clean.
 */
final class AddressDTO
{
    public function __construct(
        public readonly ?string $name      = null,   // Recipient/contact
        public readonly ?string $phone     = null,
        public readonly ?string $line1     = null,
        public readonly ?string $line2     = null,
        public readonly ?string $city      = null,
        public readonly ?string $area      = null,
        public readonly ?string $region    = null,
        public readonly ?string $country   = null,
        public readonly ?string $postcode  = null,
        public readonly array   $extra     = [],     // {cityId, regionId, villageId, prefix, ...}
    ) {}

    public function toArray(): array
    {
        return [
            'name'     => $this->name,
            'phone'    => $this->phone,
            'line1'    => $this->line1,
            'line2'    => $this->line2,
            'city'     => $this->city,
            'area'     => $this->area,
            'region'   => $this->region,
            'country'  => $this->country,
            'postcode' => $this->postcode,
            'extra'    => $this->extra,
        ];
    }
}
