<?php

namespace App\Shipping\Contracts;

use App\Shipping\DTOs\AddressDTO;
use App\Shipping\DTOs\ConnectionDTO;
use App\Shipping\DTOs\ShipmentDTO;
use App\Shipping\DTOs\TestResultDTO;
use App\Shipping\DTOs\TrackingDTO;

/**
 * The single contract every shipping provider implements. Business logic
 * (ShipmentService, jobs, controllers) talks to ProviderInterface — never to
 * a concrete provider — so swapping Logestechs for OTO is purely a config
 * change.
 *
 * Capability variations (some providers support webhooks, some don't return
 * AWB PDFs, some can't cancel post-create) are expressed via the optional
 * marker interfaces in this namespace (SupportsWebhooks, SupportsAwbPdf,
 * SupportsCancel). A provider that can't cancel still implements
 * cancelShipment() — it just throws ProviderUnavailableException.
 */
interface ShippingProviderInterface
{
    /** Short machine code: 'logestechs', 'oto', 'aramex_v2'. Matches shipping_providers.code. */
    public function code(): string;

    /**
     * Resolve a provider-specific "company id" from a publicly-known domain.
     * Logestechs: paste 'foo.logestechs.com' → returns the remote company id.
     * Providers that don't need this (most) return null.
     */
    public function resolveCompanyByDomain(string $domain): ?string;

    /**
     * Validate the connection end-to-end. Returns a TestResultDTO with ok/fail
     * + a human-readable diagnostic. MUST NOT mutate state.
     */
    public function testConnection(ConnectionDTO $connection): TestResultDTO;

    /**
     * Hydrate / refresh authentication state on the connection. For Logestechs
     * this validates email+password via /auth/customer/login. Returns a new
     * ConnectionDTO with any refreshed fields (e.g. resolved remote_company_id).
     */
    public function authenticate(ConnectionDTO $connection): ConnectionDTO;

    /**
     * Create a shipment on the provider side. Returns the same ShipmentDTO
     * with remote identifiers populated (remote_shipment_id, awb_number,
     * awb_pdf_url). Throws ProviderRejectedShipmentException on validation
     * failures, ProviderUnavailableException on transport errors.
     */
    public function createShipment(ConnectionDTO $connection, ShipmentDTO $shipment): ShipmentDTO;

    /**
     * Cancel a previously-created shipment. Providers that can't cancel
     * post-create (Aramex, J&T post-pickup) throw ProviderUnavailableException
     * with a clear reason.
     */
    public function cancelShipment(ConnectionDTO $connection, string $remoteShipmentId): void;

    /** Latest status only (cheap). Used by the per-5min sync. */
    public function getStatus(ConnectionDTO $connection, string $remoteShipmentId): TrackingDTO;

    /** Full status history (heavy). Used for the parcel-details timeline. */
    public function getTracking(ConnectionDTO $connection, string $remoteShipmentId): array;

    /** PDF bytes of the AWB label(s). Throws if provider doesn't return labels. */
    public function printAwb(ConnectionDTO $connection, array $remoteShipmentIds): string;

    /**
     * Free-text village/area lookup. Returns AddressDTO[] sorted by relevance.
     * Used in the assign-time UI to bind a local area to the provider's
     * destination address. Providers without a lookup return [].
     */
    public function searchVillages(ConnectionDTO $connection, string $query): array;
}
