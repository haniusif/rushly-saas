<?php

namespace App\Salla\Jobs;

use App\Salla\Models\Order;
use App\Salla\Models\Shipment;
use App\Salla\Services\ParcelCreationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class CreateParcelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public int $sallaOrderId) {}

    public function handle(ParcelCreationService $service): void
    {
        $order = Order::with('merchant')->find($this->sallaOrderId);
        if (! $order || $order->shipment) {
            return;
        }

        $merchant = $order->merchant;
        if (! $merchant->rushly_merchant_id) {
            Log::warning('salla.parcel.create.missing_rushly_merchant', [
                'salla_order_id'    => $order->id,
                'salla_merchant_id' => $merchant->salla_merchant_id,
                'hint'              => 'Set rushly_merchant_id on this salla_merchants row to enable parcel creation.',
            ]);
            return;
        }

        $settings = $merchant->settings;
        if (! $settings) {
            Log::warning('salla.parcel.create.missing_settings', [
                'salla_order_id' => $order->id,
            ]);
            return;
        }

        $payload = [
            'salla_merchant_id' => $merchant->salla_merchant_id,
            'salla_order_id'    => $order->salla_order_id,
            'merchant_id'       => $merchant->rushly_merchant_id,
            'shop_id'           => $settings->default_rushly_shop_id ?? $merchant->rushly_shop_id,
            'city_id'           => $settings->default_city_id,
            'category_id'       => $settings->default_category_id,
            'delivery_type_id'  => $settings->default_delivery_type_id,
            'customer_name'     => $order->customer_name,
            'customer_address'  => $order->shipping_address,
            'customer_phone'    => $order->customer_phone,
            'cash_collection'   => (float) ($order->total ?? 0),
            'meta'              => [
                'salla_reference' => $order->reference_id,
            ],
        ];

        try {
            $result = $service->create($payload);
        } catch (RuntimeException $e) {
            Log::error('salla.parcel.create.failed', [
                'salla_order_id' => $order->id,
                'error'          => $e->getMessage(),
            ]);
            throw $e;
        }

        $parcel = $result['parcel'];

        Shipment::create([
            'salla_order_id'         => $order->id,
            'rushly_tracking_number' => $parcel->tracking_id,
            'status'                 => 'label_created',
            'last_rushly_status'     => $parcel->status ?? 'pending',
            'last_synced_at'         => now(),
        ]);
    }
}
