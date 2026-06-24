<?php

namespace App\Salla\Jobs;

use App\Salla\Models\Order;
use App\Salla\Services\ApiClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReturnWaybillJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public int $sallaOrderId,
        public string $sallaShipmentId,
    ) {}

    public function handle(): void
    {
        $order = Order::with(['merchant', 'shipment'])->find($this->sallaOrderId);
        if (! $order || ! $order->shipment) {
            return;
        }

        $shipment = $order->shipment;
        $awb = $shipment->awb_number ?? $shipment->rushly_tracking_number;
        $labelBase = rtrim((string) config('app.url'), '/');
        $labelUrl = $shipment->label_url ?? "{$labelBase}/parcel/{$shipment->rushly_tracking_number}/label";

        (new ApiClient($order->merchant))->returnWaybill(
            $this->sallaShipmentId,
            $awb,
            $labelUrl,
        );

        $shipment->update([
            'salla_shipment_id' => $this->sallaShipmentId,
            'awb_number'        => $awb,
            'label_url'         => $labelUrl,
            'status'            => 'awb_returned',
        ]);
    }
}
