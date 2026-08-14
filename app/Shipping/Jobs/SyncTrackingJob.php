<?php

namespace App\Shipping\Jobs;

use App\Shipping\Models\ShippingConnection;
use App\Shipping\Services\TrackingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Polls one connection's non-terminal shipments. Dispatched per-connection by
 * the `shipping:sync-tracking` console command so one slow provider doesn't
 * block other tenants' polling.
 */
class SyncTrackingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $connectionId) {}

    public function tries(): int   { return 1; }                 // Don't pile up retries on a per-5min job
    public function timeout(): int { return 300; }               // Up to 5 minutes per connection batch

    public function handle(TrackingService $service): void
    {
        $connection = ShippingConnection::with('provider')->find($this->connectionId);
        if (! $connection || $connection->status !== 'active') return;

        $result = $service->syncConnection($connection);

        Log::info('shipping.tracking.synced', [
            'connection_id' => $this->connectionId,
            'provider'      => $connection->provider->code,
        ] + $result);
    }
}
