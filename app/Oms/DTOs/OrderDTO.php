<?php

namespace App\Oms\DTOs;

/**
 * Canonical order shape. Every provider's OrderMapperInterface produces
 * one of these; every downstream consumer (Phase 5 OMS OrderService,
 * Phase 6 FulfillmentRouter, Phase 7 InventorySync) reads only this
 * shape — provider variance stops at the mapper boundary.
 *
 * Immutable by construction. Money fields are float in one currency (the
 * `currency` field). Currency conversion is Phase 7's concern — for now
 * we pass provider-native currency through untouched.
 *
 * `resolvedCityId` / `resolvedAreaId` on the shipping address are the
 * AddressResolver's best-effort match against the tenant's cities/areas
 * tables. Null when no match — the OMS layer decides what to do (drop,
 * fall back to string city, prompt admin).
 */
final class OrderDTO
{
    public function __construct(
        public readonly string             $providerCode,        // 'salla', 'zid', ...
        public readonly string             $remoteOrderId,       // Provider's internal id
        public readonly ?string            $remoteOrderNumber,   // Human-readable ("#1042", "42")
        public readonly ?string            $providerStatus,      // Provider-native status ("under_review", "paid")
        public readonly ?string            $paymentMethod,       // Canonical: cod, prepaid, card, wallet, bank_transfer, mada, tabby, tamara, unknown
        public readonly ?string            $financialStatus,     // Canonical: pending, paid, partially_paid, refunded, voided, unknown
        public readonly OrderCustomerDTO   $customer,
        public readonly OrderAddressDTO    $shippingAddress,
        public readonly ?OrderAddressDTO   $billingAddress,      // null when provider doesn't distinguish
        /** @var OrderItemDTO[] */
        public readonly array              $items,
        public readonly float              $subtotal,
        public readonly float              $tax,
        public readonly float              $shippingFee,
        public readonly float              $discount,
        public readonly float              $total,
        public readonly float              $codAmount,           // 0.0 when not COD
        public readonly string             $currency,            // ISO 4217 (SAR, AED, USD, ...)
        public readonly ?string            $note,
        public readonly ?string            $occurredAt,          // ISO 8601 — provider's order-placed timestamp
        public readonly array              $extra = [],          // Provider-specific overflow (Salla urls, Shopify tags, ...)
    ) {}

    public function isCod(): bool
    {
        return $this->codAmount > 0;
    }

    /**
     * Wire shape for persistence to webhook_events.normalized_payload.
     * Consumed by Phase 5 OMS OrderService::receiveNormalized() and by
     * the admin webhook viewer.
     */
    public function toArray(): array
    {
        return [
            'provider_code'       => $this->providerCode,
            'remote_order_id'     => $this->remoteOrderId,
            'remote_order_number' => $this->remoteOrderNumber,
            'provider_status'     => $this->providerStatus,
            'payment_method'      => $this->paymentMethod,
            'financial_status'    => $this->financialStatus,
            'customer'            => $this->customer->toArray(),
            'shipping_address'    => $this->shippingAddress->toArray(),
            'billing_address'     => $this->billingAddress?->toArray(),
            'items'               => array_map(fn (OrderItemDTO $i) => $i->toArray(), $this->items),
            'subtotal'            => $this->subtotal,
            'tax'                 => $this->tax,
            'shipping_fee'        => $this->shippingFee,
            'discount'            => $this->discount,
            'total'               => $this->total,
            'cod_amount'          => $this->codAmount,
            'currency'            => $this->currency,
            'note'                => $this->note,
            'occurred_at'         => $this->occurredAt,
            'extra'               => $this->extra,
        ];
    }
}
