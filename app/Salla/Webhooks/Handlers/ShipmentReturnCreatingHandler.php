<?php

namespace App\Salla\Webhooks\Handlers;

use App\Salla\Models\Merchant;
use App\Salla\Models\Shipment;
use App\Salla\Webhooks\Contracts\Handler;
use Illuminate\Support\Facades\Log;

/**
 * Salla is about to create a return shipment for an already-fulfilled
 * order. We flag the local salla_shipments row so ops can see the return
 * lifecycle without introducing a second shipment table. The AWB return
 * itself flows through the same PUT /shipments/{id} once ShipmentReturn-
 * Created fires.
 */
class ShipmentReturnCreatingHandler implements Handler
{
    public function handle(array $event): void
    {
        $sallaMerchantId = $event['merchant'] ?? 0;
        $merchant = Merchant::where('salla_merchant_id', $sallaMerchantId)->first();
        if (! $merchant) {
            Log::warning('salla.shipment.return.creating.unknown_merchant', [
                'salla_merchant_id' => $sallaMerchantId,
            ]);
            return;
        }

        $data            = $event['data'] ?? [];
        $sallaShipmentId = (string) ($data['id'] ?? '');

        Shipment::where('salla_shipment_id', $sallaShipmentId)
            ->update(['status' => 'return_creating']);

        Log::info('salla.shipment.return.creating', [
            'merchant'          => $merchant->id,
            'salla_shipment_id' => $sallaShipmentId,
        ]);
    }
}
