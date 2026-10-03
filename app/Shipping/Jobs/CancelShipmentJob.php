<?php

namespace App\Shipping\Jobs;

use App\Shipping\Models\Shipment;
use App\Shipping\Services\ShipmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CancelShipmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $shipmentId) {}

    public function tries(): int     { return (int) config('shipping.retry.tries', 3); }
    public function backoff(): array { return (array) config('shipping.retry.backoff', [10, 30, 90]); }
    public function timeout(): int   { return (int) config('shipping.retry.timeout', 60); }

    public function handle(ShipmentService $service): void
    {
        $shipment = Shipment::with('connection.provider')->find($this->shipmentId);
        if (! $shipment) return;
        if ($shipment->state === 'cancelled') return;
        $service->executeCancel($shipment);
    }
}
