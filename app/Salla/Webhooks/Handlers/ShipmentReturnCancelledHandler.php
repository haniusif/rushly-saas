<?php

namespace App\Salla\Webhooks\Handlers;

use App\Salla\Models\Merchant;
use App\Salla\Models\Shipment;
use App\Salla\Webhooks\Contracts\Handler;
use Illuminate\Support\Facades\Log;

class ShipmentReturnCancelledHandler implements Handler
{
    public function handle(array $event): void
    {
        $sallaMerchantId = $event['merchant'] ?? 0;
        $merchant = Merchant::where('salla_merchant_id', $sallaMerchantId)->first();
        if (! $merchant) {
            Log::warning('salla.shipment.return.cancelled.unknown_merchant', [
                'salla_merchant_id' => $sallaMerchantId,
            ]);
            return;
        }

        $data            = $event['data'] ?? [];
        $sallaShipmentId = (string) ($data['id'] ?? '');

        // Revert to awb_returned — the forward shipment is still live even
        // though the return leg was cancelled. Only touch rows that were in
        // a return-lifecycle state to avoid clobbering a delivered/lost row.
        Shipment::where('salla_shipment_id', $sallaShipmentId)
            ->whereIn('status', ['return_creating', 'returning'])
            ->update(['status' => 'awb_returned']);

        Log::info('salla.shipment.return.cancelled', [
            'merchant'          => $merchant->id,
            'salla_shipment_id' => $sallaShipmentId,
        ]);
    }
}
