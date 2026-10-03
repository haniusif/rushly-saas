<?php

namespace App\Fulfillment\Bridges;

use App\Enums\ParcelStatus;
use App\Fulfillment\Exceptions\StrategyRejectedException;
use App\Models\Backend\Parcel;
use App\Oms\Models\Order;
use App\Traits\TrackingTrait;

/**
 * Bridges a canonical OMS Order into a legacy courier-side Parcel.
 *
 * Idempotent: `parcels.oms_order_id` is the unique key. Same Order
 * always resolves to the same Parcel row — repeat calls return the
 * existing one, no duplicates.
 *
 * This is Phase 6.5 glue between two eras of the data model:
 *   - OMS Order (Phase 5) — merchant-side commerce order, cheap to
 *     replay, no courier concept
 *   - Parcel (legacy) — courier-side shipment unit with hub / delivery
 *     man / status-machine concerns
 *
 * WMS + 3PL strategies call this bridge to get a Parcel, then hand
 * that Parcel to their downstream consumer (WmsFulfillment creation,
 * ShipmentService::dispatchCreate). MerchantSelfStrategy skips it —
 * merchants handle fulfillment out-of-band and don't need a Parcel.
 *
 * Field mapping keeps things minimal: only merchant_id is strictly
 * required on the parcels table (constrained FK). Everything else is
 * nullable, so the bridge fills what it knows from the Order and
 * leaves the rest null — hub, driver, category etc. are assigned by
 * later workflow (WMS pick, admin manual assign, or bulk-action).
 *
 * Throws StrategyRejectedException (non-retryable) when:
 *   - Order has no merchant_id (connection wasn't wired to a Rushly merchant)
 */
class OrderToParcelBridge
{
    use TrackingTrait;

    public function toParcel(Order $order): Parcel
    {
        if (! $order->merchant_id) {
            throw new StrategyRejectedException(
                'Order has no merchant_id — cannot materialise a Parcel. Wire the CommerceConnection to a Rushly merchant first.',
                ['order_id' => $order->id, 'source' => $order->source_provider_code],
            );
        }

        $existing = Parcel::query()->where('oms_order_id', $order->id)->first();
        if ($existing) {
            return $existing;
        }

        return $this->createFromOrder($order);
    }

    private function createFromOrder(Order $order): Parcel
    {
        // Assemble the customer_address string from the shipping line
        // fields — Parcel stores it as one longtext.
        $address = trim(implode(', ', array_filter([
            $order->shipping_line1,
            $order->shipping_line2,
            $order->shipping_area_name,
            $order->shipping_city_name,
        ])));

        // Note field carries provenance so ops can trace back to the
        // source order without opening the admin viewer.
        $note = sprintf(
            'OMS order #%d from %s (%s)',
            $order->id,
            $order->source_provider_code,
            $order->remote_order_number ?: $order->remote_order_id,
        );

        $parcel = Parcel::create([
            'company_id'       => $order->company_id,
            'merchant_id'      => $order->merchant_id,
            'tracking_id'      => $this->trackingId(),
            'customer_name'    => $order->customer_name,
            'customer_phone'   => $order->customer_phone,
            'customer_address' => $address ?: null,
            'city_id'          => $order->shipping_city_id,
            'area_id'          => $order->shipping_area_id,
            'cash_collection'  => $order->cod_amount,
            'cod_amount'       => $order->cod_amount,
            'reference_number' => $order->remote_order_number ?: $order->remote_order_id,
            'note'             => $note,
            'status'           => ParcelStatus::PENDING,
        ]);

        // `oms_order_id` isn't in the Parcel model's $fillable (adding it
        // would touch a widely-used domain model). Assign directly + save
        // so the reverse link is captured.
        $parcel->oms_order_id = $order->id;
        $parcel->save();

        return $parcel;
    }
}
