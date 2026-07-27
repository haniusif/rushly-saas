<?php

namespace App\Salla\Webhooks\Handlers;

use App\Salla\Models\Merchant;
use App\Salla\Models\Shipment;
use App\Salla\Webhooks\Contracts\Handler;
use Illuminate\Support\Facades\Log;

class ShipmentReturnCreatedHandler implements Handler
{
    public function handle(array $event): void
    {
        $sallaMerchantId = $event['merchant'] ?? 0;
        $merchant = Merchant::where('salla_merchant_id', $sallaMerchantId)->first();
        if (! $merchant) {
            Log::warning('salla.shipment.return.created.unknown_merchant', [
                'salla_merchant_id' => $sallaMerchantId,
            ]);
            return;
        }

        $data            = $event['data'] ?? [];
        $sallaShipmentId = (string) ($data['id'] ?? '');

        Shipment::where('salla_shipment_id', $sallaShipmentId)
            ->update(['status' => 'returning']);

        Log::info('salla.shipment.return.created', [
            'merchant'          => $merchant->id,
            'salla_shipment_id' => $sallaShipmentId,
            'tracking_number'   => $data['tracking_number'] ?? null,
        ]);
    }
}
