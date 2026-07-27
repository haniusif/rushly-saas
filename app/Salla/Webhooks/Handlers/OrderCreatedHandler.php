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

        $data     = $event['data'] ?? [];
        $customer = $data['customer'] ?? [];
        $shipping = $data['shipping']['address'] ?? [];
        // Salla nests order money fields under `amounts.total`, not `total`.
        // Sub-total / shipping_cost / tax / cash_on_delivery live alongside.
        $totalAmount = $data['amounts']['total']['amount']
            ?? $data['total']['amount']         // legacy / synthetic-test shape
            ?? null;
        $totalCurrency = $data['amounts']['total']['currency']
            ?? $data['total']['currency']
            ?? null;
        // Compose an E.164-ish phone from mobile_code + mobile when both
        // arrive separately (Salla's actual payload). Falls back to bare
        // mobile for legacy shapes.
        $mobile      = (string) ($customer['mobile'] ?? '');
        $mobileCode  = trim((string) ($customer['mobile_code'] ?? ''));
        $phone       = $mobile !== '' && $mobileCode !== ''
            ? preg_replace('/^\++/', '+', $mobileCode.ltrim($mobile, '0'))
            : ($mobile !== '' ? $mobile : null);

        $order = Order::updateOrCreate(
            [
                'salla_merchant_id' => $merchant->id,
                'salla_order_id'    => $data['id'] ?? 0,
            ],
            [
                'reference_id'     => $data['reference_id'] ?? null,
                'status'           => $data['status']['name'] ?? null,
                'customer_name'    => trim(($customer['first_name'] ?? '').' '.($customer['last_name'] ?? '')),
                'customer_phone'   => $phone,
                // `shipping_address` (full formatted) is Salla's preferred
                // string; `street_number` is the raw street. Fall back down
                // the chain so both real + legacy payloads work.
                'shipping_address' => $shipping['shipping_address'] ?? $shipping['street_number'] ?? $shipping['street'] ?? null,
                'shipping_city'    => $shipping['city'] ?? null,
                'total'            => $totalAmount,
                'currency'         => $totalCurrency,
                'payload'          => $data,
            ],
        );

        $settings = $merchant->settings;
        if ($settings && $settings->auto_create_parcel) {
            CreateParcelJob::dispatch($order->id);
        }
    }
}
