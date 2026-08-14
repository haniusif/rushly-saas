<?php

namespace App\Commerce\Contracts;

use App\Commerce\DTOs\CommerceConnectionDTO;
use App\Commerce\DTOs\RawOrderDTO;
use App\Commerce\DTOs\TestResultDTO;

/**
 * The single contract every commerce provider implements. Business logic
 * (OrderNormalizer, FulfillmentService, listeners, controllers) talks to
 * CommerceProviderInterface — never to a concrete provider — so swapping
 * "Salla" for "Shopify" is a routing / config decision, not a code change.
 *
 * Capability variance on the commerce side is much wider than shipping
 * (some providers push via webhooks but can't be polled; some only accept
 * pushed orders, some only writeback, some do inventory sync only). That
 * variance is expressed via the marker interfaces in this namespace —
 * SupportsOAuth, SupportsWebhooks, SupportsBulkFetch,
 * SupportsOrderWriteback, SupportsInventorySync. The base interface keeps
 * the contract small.
 *
 * Methods declared here are the irreducible core: every provider must be
 * able to identify itself, validate a configured connection, and fetch one
 * order by its remote id (even a cached read counts).
 */
interface CommerceProviderInterface
{
    /** Short machine code: 'salla', 'zid', 'shopify'. Matches commerce_providers.code. */
    public function code(): string;

    /**
     * Validate the connection end-to-end. Returns a TestResultDTO with
     * ok/fail + a human-readable diagnostic. MUST NOT mutate state.
     */
    public function testConnection(CommerceConnectionDTO $connection): TestResultDTO;

    /**
     * Hydrate / refresh authentication state on the connection. For OAuth
     * providers this is where access_token refresh lives. Returns a new
     * CommerceConnectionDTO with any refreshed fields. Idempotent — calling
     * it twice in a row is safe.
     */
    public function authenticate(CommerceConnectionDTO $connection): CommerceConnectionDTO;

    /**
     * Fetch a single order from the storefront by its remote identifier.
     * Returns the raw payload wrapped in a RawOrderDTO — normalization to
     * the canonical OrderDTO happens in the OMS layer, not here.
     *
     * Throws ProviderRejectedRequestException if the order doesn't exist
     * or the provider validated the request and refused it.
     * Throws ProviderUnavailableException on transport / 5xx.
     */
    public function fetchOrder(CommerceConnectionDTO $connection, string $remoteOrderId): RawOrderDTO;

    /**
     * Push an order-level update to the storefront. Typical payload:
     * `['status' => 'shipped', 'tracking_number' => 'RL...', 'carrier' => 'Logestechs']`.
     * Providers that don't support writeback (rare; some read-only ERP
     * sources) throw ProviderUnavailableException. SupportsOrderWriteback
     * is the marker interface that declares the method is safe to call.
     */
    public function pushOrderUpdate(CommerceConnectionDTO $connection, string $remoteOrderId, array $update): void;
}
