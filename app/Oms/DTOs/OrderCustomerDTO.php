<?php

namespace App\Oms\DTOs;

/**
 * Order-side customer snapshot. Distinct from a persistent OMS Customer
 * entity (which lands in Phase 5 with dedup by phone/email) — this DTO
 * is just the customer info as it appeared on this specific order.
 *
 * Phone is normalized-ish (trimmed, E.164 hint) but not authoritative.
 * Phase 5's CustomerNormalizer does the real dedupe + canonical-form
 * normalization against a `customers` table.
 */
final class OrderCustomerDTO
{
    public function __construct(
        public readonly ?string $remoteId,
        public readonly ?string $name,
        public readonly ?string $email,
        public readonly ?string $phone,
    ) {}

    public function toArray(): array
    {
        return [
            'remote_id' => $this->remoteId,
            'name'      => $this->name,
            'email'     => $this->email,
            'phone'     => $this->phone,
        ];
    }
}
