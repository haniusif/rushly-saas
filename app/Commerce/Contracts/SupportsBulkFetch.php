<?php

namespace App\Commerce\Contracts;

use App\Commerce\DTOs\CommerceConnectionDTO;

/**
 * Marker for providers whose REST surface exposes a list endpoint so we
 * can backfill historical orders (e.g. on first install, or to repair a
 * webhook gap). Providers without a list endpoint (push-only customs)
 * skip this interface.
 */
interface SupportsBulkFetch
{
    /**
     * Fetch a page of orders. $filter is provider-agnostic and minimal —
     * the concrete impl translates to its own query params.
     *
     * Recognised keys:
     *   - 'since'   => ISO 8601 — earliest updated_at to include
     *   - 'cursor'  => opaque — pagination token returned by previous call
     *   - 'limit'   => int     — page size hint (provider may clamp)
     *   - 'status'  => string  — provider-native status filter
     *
     * Returns ['orders' => RawOrderDTO[], 'next_cursor' => ?string].
     */
    public function fetchOrders(CommerceConnectionDTO $connection, array $filter): array;
}
