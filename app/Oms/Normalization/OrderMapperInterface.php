<?php

namespace App\Oms\Normalization;

use App\Oms\DTOs\OrderDTO;

/**
 * Per-provider order mapper. One implementation per storefront platform;
 * routed by OrderNormalizer via config('commerce.providers.<code>.order_mapper').
 *
 * The mapper's job:
 *   1. Validate the payload's shape (throw NormalizationException with
 *      field-level errors for anything missing)
 *   2. Map provider-native fields into the canonical OrderDTO shape
 *   3. Call AddressResolver on the shipping address so downstream code
 *      gets local city_id / area_id when a match exists
 *
 * The mapper does NOT:
 *   - Persist anything (that's OMS Phase 5)
 *   - Call the provider's API (that's the CommerceProvider's job — the
 *     mapper works entirely from the payload it's given)
 *   - Convert currency (that's Phase 7)
 *   - Dedupe customers (that's Phase 5's CustomerNormalizer against a
 *     real customers table)
 */
interface OrderMapperInterface
{
    /** Provider code this mapper handles: 'salla', 'zid', ... */
    public function code(): string;

    /**
     * Map a provider-native order payload into a canonical OrderDTO.
     *
     * $companyId is the tenant id the event belongs to (from the resolved
     * CommerceConnection). AddressResolver needs it to scope its lookup
     * against the tenant's cities/areas — those tables carry a
     * `company_id` column when the tenant maintains their own geography.
     *
     * @throws \App\Oms\Exceptions\NormalizationException
     */
    public function map(array $payload, ?int $companyId = null): OrderDTO;
}
