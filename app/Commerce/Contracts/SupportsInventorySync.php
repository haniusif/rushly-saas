<?php

namespace App\Commerce\Contracts;

use App\Commerce\DTOs\CommerceConnectionDTO;

/**
 * Marker for providers that accept stock-level pushes. The Phase 7
 * inventory-sync flow listens to WMS StockChanged events and dispatches
 * PushStockJob per active connection that implements this interface.
 *
 * SKU mapping is the provider's responsibility — most storefronts key
 * their inventory by a provider-side product/variant id, not the bare SKU
 * string. The provider resolves the mapping; the caller supplies SKU
 * because that's what WMS knows about.
 */
interface SupportsInventorySync
{
    /**
     * Push stock updates for one or more SKUs. $updates is an array of
     * ['sku' => string, 'quantity' => int] — provider impls translate to
     * their native shape (Shopify inventoryLevel mutations, Salla
     * /products/quantities, etc.).
     */
    public function pushInventoryUpdate(CommerceConnectionDTO $connection, array $updates): void;
}
