<?php

namespace App\Salla\Webhooks\Handlers;

use App\Salla\Jobs\ReturnWaybillJob;
use App\Salla\Models\Merchant;
use App\Salla\Models\Order;
use App\Salla\Webhooks\Contracts\Handler;
use Illuminate\Support\Facades\Log;

class ShipmentCreatingHandler implements Handler
{
    public function handle(array $event): void
    {
        $sallaMerchantId = $event['merchant'] ?? 0;
        $merchant = Merchant::where('salla_merchant_id', $sallaMerchantId)->first();
        if (! $merchant) {
            Log::warning('salla.shipment.creating.unknown_merchant', [
                'salla_merchant_id'  => $sallaMerchantId,
                'salla_shipment_id'  => $event['data']['id'] ?? null,
            ]);
            return;
        }

        $data = $event['data'] ?? [];
        $sallaOrderId = $data['order_id'] ?? $data['order']['id'] ?? null;
        $sallaShipmentId = (string) ($data['id'] ?? '');

        $order = Order::where('salla_merchant_id', $merchant->id)
            ->where('salla_order_id', $sallaOrderId)
            ->first();

        if (! $order) {
            Log::warning('salla.shipment.creating: unknown order', [
                'merchant' => $merchant->id,
                'order_id' => $sallaOrderId,
            ]);
            return;
        }

        ReturnWaybillJob::dispatch($order->id, $sallaShipmentId);
    }
}
