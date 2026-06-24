<?php

namespace App\Salla\Services;

use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\Backend\SallaOrderLink;
use App\Traits\TrackingTrait;
use RuntimeException;

class ParcelCreationService
{
    use TrackingTrait;

    /**
     * Create a Rushly Parcel from a normalized Salla payload.
     *
     * @return array{parcel: Parcel, link: SallaOrderLink, created: bool}
     */
    public function create(array $payload): array
    {
        $merchant = Merchant::find($payload['merchant_id']);
        if (! $merchant) {
            throw new RuntimeException("Unknown Rushly merchant: {$payload['merchant_id']}");
        }

        $existing = SallaOrderLink::where('salla_merchant_id', $payload['salla_merchant_id'])
            ->where('salla_order_id', $payload['salla_order_id'])
            ->first();

        if ($existing && $existing->parcel_id) {
            $parcel = Parcel::find($existing->parcel_id);
            if ($parcel) {
                return ['parcel' => $parcel, 'link' => $existing, 'created' => false];
            }
        }

        $meta = (array) ($payload['meta'] ?? []);

        $parcel = Parcel::create([
            'company_id'       => $merchant->company_id,
            'tracking_id'      => $this->trackingId(),
            'merchant_id'      => $merchant->id,
            'merchant_shop_id' => $payload['shop_id'],
            'city_id'          => $payload['city_id'],
            'category_id'      => $payload['category_id'],
            'delivery_type_id' => $payload['delivery_type_id'],
            'customer_name'    => $payload['customer_name'],
            'customer_address' => $payload['customer_address'],
            'customer_phone'   => $payload['customer_phone'],
            'cash_collection'  => (float) ($payload['cash_collection'] ?? 0),
            'reference_number' => $meta['salla_reference'] ?? null,
            'note'             => isset($meta['salla_reference']) ? 'Salla order '.$meta['salla_reference'] : null,
            'status'           => 'pending',
        ]);

        $link = SallaOrderLink::create([
            'company_id'        => $merchant->company_id,
            'salla_merchant_id' => $payload['salla_merchant_id'],
            'salla_order_id'    => $payload['salla_order_id'],
            'salla_shipment_id' => $meta['salla_shipment_id'] ?? null,
            'merchant_id'       => $merchant->id,
            'parcel_id'         => $parcel->id,
            'meta'              => $meta,
        ]);

        return ['parcel' => $parcel, 'link' => $link, 'created' => true];
    }
}
