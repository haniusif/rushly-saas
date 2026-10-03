<?php

namespace App\Oms\Listeners;

use App\Oms\Events\OrderReceived;
use Illuminate\Support\Facades\Log;

/**
 * Phase 5 stub — records receipt in the structured log for ops
 * visibility while the downstream Fulfillment path (Phase 6) is still
 * being built.
 *
 * Order + item + audit rows are already persisted by OrderService before
 * this listener runs. It intentionally does NOT create a Parcel — the
 * legacy `app/Salla/Webhooks/Handlers/OrderCreatedHandler` still handles
 * that path (via SallaMerchant → CreateParcelJob → Parcel), so the new
 * OMS path runs strictly in-parallel with the courier-facing legacy path
 * until Phase 6 replaces the legacy pipeline.
 *
 * When Phase 6 lands, this listener gets swapped for `RouteToFulfillment`
 * which resolves a FulfillmentStrategy (WMS / 3PL / vendor) via
 * FulfillmentRouter and hands the order off.
 */
class LogOrderReceivedListener
{
    public function handle(OrderReceived $event): void
    {
        $o = $event->order;
        Log::info('oms.order.received', [
            'order_id'         => $o->id,
            'company_id'       => $o->company_id,
            'connection_id'    => $o->connection_id,
            'source_provider'  => $o->source_provider_code,
            'remote_order_id'  => $o->remote_order_id,
            'total'            => $o->total,
            'currency'         => $o->currency,
            'cod_amount'       => $o->cod_amount,
            'items'            => $o->items()->count(),
        ]);
    }
}
