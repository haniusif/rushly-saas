<?php

namespace App\Oms\DTOs;

/**
 * One line item on an order. Mapped from the provider's line-item shape
 * (Salla data.items[], Shopify line_items[], WC line_items[]).
 *
 * `remoteProductId` + `remoteVariantId` let Phase 7 (inventory sync)
 * push stock updates back to the right product handle on the source
 * storefront without needing a fresh product fetch.
 */
final class OrderItemDTO
{
    public function __construct(
        public readonly ?string $sku,
        public readonly string  $name,
        public readonly int     $quantity,
        public readonly float   $unitPrice,
        public readonly float   $totalPrice,
        public readonly string  $currency,
        public readonly ?string $remoteProductId  = null,
        public readonly ?string $remoteVariantId  = null,
        public readonly array   $extra            = [],
    ) {}

    public function toArray(): array
    {
        return [
            'sku'                => $this->sku,
            'name'               => $this->name,
            'quantity'           => $this->quantity,
            'unit_price'         => $this->unitPrice,
            'total_price'        => $this->totalPrice,
            'currency'           => $this->currency,
            'remote_product_id'  => $this->remoteProductId,
            'remote_variant_id'  => $this->remoteVariantId,
            'extra'              => $this->extra,
        ];
    }
}
