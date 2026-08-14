<?php

namespace App\Shipping\Jobs;

use App\Shipping\Exceptions\ProviderRejectedShipmentException;
use App\Shipping\Models\Shipment;
use App\Shipping\Services\ShipmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateShipmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $shipmentId) {}

    public function tries(): int
    {
        return (int) config('shipping.retry.tries', 3);
    }

    /** Per-attempt backoff in seconds. */
    public function backoff(): array
    {
        return (array) config('shipping.retry.backoff', [10, 30, 90]);
    }

    public function timeout(): int
    {
        return (int) config('shipping.retry.timeout', 60);
    }

    public function handle(ShipmentService $service): void
    {
        $shipment = Shipment::with('connection.provider', 'parcel')->find($this->shipmentId);
        if (! $shipment) return;

        // Idempotency: if a concurrent run / earlier attempt already succeeded,
        // bail out early without calling the provider again.
        if ($shipment->remote_shipment_id) return;

        try {
            $service->executeCreate($shipment);
        } catch (ProviderRejectedShipmentException $e) {
            // Provider rejected the payload — don't retry, surface as final fail.
            $this->fail($e);
        }
    }
}
