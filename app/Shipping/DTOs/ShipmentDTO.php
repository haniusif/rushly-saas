<?php

namespace App\Shipping\DTOs;

use App\Models\Backend\Parcel;

/**
 * Canonical shipment payload. Built from a Parcel via fromParcel(); mapped to
 * the provider-specific wire shape inside the provider's ShipmentRequestMapper.
 *
 * Holds remote identifiers after a successful create. The same DTO returned
 * from createShipment() carries the awb back to the caller — no awkward
 * second-call pattern.
 */
final class ShipmentDTO
{
    public function __construct(
        // Local handles
        public readonly int     $parcelId,
        public readonly string  $referenceNumber,    // local tracking id / invoice no
        public readonly string  $description,
        public readonly int     $quantity,
        public readonly float   $weight,             // kg
        public readonly float   $codAmount,
        public readonly string  $currency,
        public readonly AddressDTO $sender,
        public readonly AddressDTO $recipient,
        public readonly array   $extra = [],         // provider-specific overrides
        // Remote handles (populated on response)
        public readonly ?string $remoteShipmentId = null,
        public readonly ?string $awbNumber        = null,
        public readonly ?string $awbPdfUrl        = null,
        public readonly ?array  $providerResponse = null,
    ) {}

    public static function fromParcel(Parcel $parcel): self
    {
        $recipient = new AddressDTO(
            name:    (string) ($parcel->customer_name ?? ''),
            phone:   (string) ($parcel->customer_phone ?? ''),
            line1:   (string) ($parcel->customer_address ?? ''),
            city:    optional($parcel->city)->en_name ?: optional($parcel->city)->name,
            area:    optional($parcel->area)->en_name ?: optional($parcel->area)->name,
            // Parcel doesn't carry a country relationship — providers that
            // need it set it via $extra['country'] at assign-time.
            country: null,
        );

        // Sender = the HUB the parcel ships from, falling back to the
        // merchant's pickup details.
        //
        // It previously carried name/phone/line1 only — no city, area or
        // region — so any provider needing an origin locality received blanks.
        // EcoExpress rejected exactly that: "Shipper city is not valid,
        // Shipper state is not valid". The hub is the real physical origin, so
        // its name and address fill the locality; merchant details remain the
        // fallback for parcels with no hub assigned.
        $hub = $parcel->hub;

        $sender = new AddressDTO(
            name:    (string) ($hub->name ?? $parcel->merchant->business_name ?? ''),
            phone:   (string) ($hub->phone ?? $parcel->pickup_phone ?: ($parcel->merchant->user->mobile ?? '')),
            line1:   (string) ($hub->address ?? $parcel->pickup_address ?? ''),
            // Prefer the hub's CITY now that hubs carry one. The hub name is
            // kept as the fallback for hubs whose city has not been set yet —
            // it is what the origin was resolved from before the column
            // existed, and it still works for hubs named after their city.
            city:    (string) (optional($hub?->city)->en_name ?: optional($hub?->city)->name ?: $hub?->name ?? ''),
            region:  (string) (optional($hub?->city)->en_name ?: optional($hub?->city)->name ?: $hub?->name ?? ''),
        );

        return new self(
            parcelId:        (int) $parcel->id,
            referenceNumber: (string) ($parcel->tracking_id ?? $parcel->id),
            description:    (string) ($parcel->package_description ?? ($parcel->note ?? 'Parcel')),
            quantity:       max(1, (int) ($parcel->number_of_boxes ?? 1)),
            weight:         (float) ($parcel->weight ?? 0),
            codAmount:      (float) ($parcel->cash_collection ?? 0),
            currency:       (string) (settings()->currency_code ?? 'SAR'),
            sender:         $sender,
            recipient:      $recipient,
        );
    }

    public function withRemote(string $remoteId, ?string $awb, ?string $awbPdf, ?array $rawResponse): self
    {
        return new self(
            parcelId:         $this->parcelId,
            referenceNumber:  $this->referenceNumber,
            description:     $this->description,
            quantity:        $this->quantity,
            weight:          $this->weight,
            codAmount:       $this->codAmount,
            currency:        $this->currency,
            sender:          $this->sender,
            recipient:       $this->recipient,
            extra:           $this->extra,
            remoteShipmentId:$remoteId,
            awbNumber:       $awb,
            awbPdfUrl:       $awbPdf,
            providerResponse:$rawResponse,
        );
    }

    public function withExtra(array $extra): self
    {
        return new self(
            parcelId:        $this->parcelId,
            referenceNumber: $this->referenceNumber,
            description:     $this->description,
            quantity:        $this->quantity,
            weight:          $this->weight,
            codAmount:       $this->codAmount,
            currency:        $this->currency,
            sender:          $this->sender,
            recipient:       $this->recipient,
            extra:           array_merge($this->extra, $extra),
            remoteShipmentId:$this->remoteShipmentId,
            awbNumber:       $this->awbNumber,
            awbPdfUrl:       $this->awbPdfUrl,
            providerResponse:$this->providerResponse,
        );
    }

    public function isCod(): bool
    {
        return $this->codAmount > 0;
    }
}
