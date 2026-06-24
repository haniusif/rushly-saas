<?php

namespace App\Salla\Webhooks\Handlers;

use App\Salla\Jobs\CreateParcelJob;
use App\Salla\Models\Merchant;
use App\Salla\Models\Order;
use App\Salla\Webhooks\Contracts\Handler;
use Illuminate\Support\Facades\Log;

class OrderCreatedHandler implements Handler
{
    public function handle(array $event): void
    {
        $sallaMerchantId = $event['merchant'] ?? 0;
        $merchant = Merchant::where('salla_merchant_id', $sallaMerchantId)->first();
        if (! $merchant) {
            Log::warning('salla.order.created.unknown_merchant', [
                'salla_merchant_id' => $sallaMerchantId,
                'salla_order_id'    => $event['data']['id'] ?? null,
                'hint'              => 'app.store.authorize never created this merchant. Check Partner Portal subscriptions and re-install.',
            ]);
            return;
        }

        $data = $event['data'] ?? [];
        $shipping = $data['shipping']['address'] ?? [];

        $order = Order::updateOrCreate(
            [
                'salla_merchant_id' => $merchant->id,
                'salla_order_id'    => $data['id'] ?? 0,
            ],
            [
                'reference_id'     => $data['reference_id'] ?? null,
                'status'           => $data['status']['name'] ?? null,
                'customer_name'    => trim(($data['customer']['first_name'] ?? '').' '.($data['customer']['last_name'] ?? '')),
                'customer_phone'   => $data['customer']['mobile'] ?? null,
                'shipping_address' => $shipping['street'] ?? null,
                'shipping_city'    => $shipping['city'] ?? null,
                'total'            => $data['total']['amount'] ?? null,
                'currency'         => $data['total']['currency'] ?? null,
                'payload'          => $data,
            ],
        );

        $settings = $merchant->settings;
        if ($settings && $settings->auto_create_parcel) {
            CreateParcelJob::dispatch($order->id);
        }
    }
}
