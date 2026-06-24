<?php

namespace App\Salla\Webhooks\Handlers;

use App\Salla\Models\Merchant;
use App\Salla\Models\Shipment;
use App\Salla\Webhooks\Contracts\Handler;
use Illuminate\Support\Facades\Log;

class ShipmentCancelledHandler implements Handler
{
    public function handle(array $event): void
    {
        $sallaMerchantId = $event['merchant'] ?? 0;
        $merchant = Merchant::where('salla_merchant_id', $sallaMerchantId)->first();
        if (! $merchant) {
            Log::warning('salla.shipment.cancelled.unknown_merchant', [
                'salla_merchant_id'  => $sallaMerchantId,
                'salla_shipment_id'  => $event['data']['id'] ?? null,
            ]);
            return;
        }

        $sallaShipmentId = (string) ($event['data']['id'] ?? '');
        Shipment::where('salla_shipment_id', $sallaShipmentId)
            ->update(['status' => 'cancelled']);
    }
}
