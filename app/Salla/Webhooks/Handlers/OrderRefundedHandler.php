<?php

namespace App\Salla\Webhooks\Handlers;

use App\Salla\Models\Merchant;
use App\Salla\Models\Order;
use App\Salla\Webhooks\Contracts\Handler;
use Illuminate\Support\Facades\Log;

class OrderRefundedHandler implements Handler
{
    public function handle(array $event): void
    {
        $sallaMerchantId = $event['merchant'] ?? 0;
        $merchant = Merchant::where('salla_merchant_id', $sallaMerchantId)->first();
        if (! $merchant) {
            Log::warning('salla.order.refunded.unknown_merchant', [
                'salla_merchant_id' => $sallaMerchantId,
                'salla_order_id'    => $event['data']['id'] ?? null,
            ]);
            return;
        }

        $orderId = $event['data']['id'] ?? 0;

        Order::where('salla_merchant_id', $merchant->id)
            ->where('salla_order_id', $orderId)
            ->update([
                'status'  => 'refunded',
                'payload' => $event['data'] ?? [],
            ]);

        Log::info('salla.order.refunded', [
            'merchant'       => $merchant->id,
            'salla_order_id' => $orderId,
            'amount'         => $event['data']['amount'] ?? null,
        ]);
    }
}
