<?php

namespace App\Shipping\Services;

use App\Shipping\DTOs\ConnectionDTO;
use App\Shipping\Events\ShipmentDelivered;
use App\Shipping\Events\ShipmentStatusChanged;
use App\Shipping\Factory\ShippingProviderFactory;
use App\Shipping\Models\Shipment;
use App\Shipping\Models\ShippingConnection;
use App\Shipping\Repositories\ShipmentRepository;
use Illuminate\Support\Facades\Log;

/**
 * Pull status updates from providers. Designed to run inside SyncTrackingJob,
 * which is dispatched per-connection by `shipping:sync-tracking`.
 *
 * Per-connection (not global) so one slow provider doesn't block other
 * tenants' polling.
 */
class TrackingService
{
    public function __construct(
        private readonly ShippingProviderFactory $factory,
        private readonly ShipmentRepository $shipments,
    ) {}

    public function syncConnection(ShippingConnection $connection, ?int $limit = null): array
    {
        $limit ??= (int) config('shipping.sync.batch_per_run', 200);
        $provider = $this->factory->forConnection($connection);
        $rows     = $this->shipments->pendingForConnection($connection->id, $limit);

        $changed   = 0;
        $delivered = 0;
        $errors    = 0;

        foreach ($rows as $shipment) {
            try {
                $dto = $provider->getStatus(
                    ConnectionDTO::fromModel($connection),
                    (string) $shipment->remote_shipment_id,
                );

                $old = $shipment->current_status_local;
                $shipment->current_status_raw   = $dto->rawStatus;
                $shipment->current_status_local = $dto->localStatus;
                $shipment->last_status_at       = $dto->occurredAt ?: now();
                $shipment->last_synced_at       = now();
                $shipment->last_sync_error      = null;
                $shipment->save();

                if ($dto->localStatus !== null && $dto->localStatus !== $old) {
                    event(new ShipmentStatusChanged($shipment, $old, $dto->localStatus));
                    $changed++;
                    if ($dto->localStatus === \App\Enums\ParcelStatus::DELIVERED) {
                        event(new ShipmentDelivered($shipment));
                        $delivered++;
                    }
                }
            } catch (\Throwable $e) {
                $shipment->last_sync_error = mb_substr($e->getMessage(), 0, 1000);
                $shipment->save();
                $errors++;
                Log::warning('shipping.tracking.sync_failed', [
                    'connection_id' => $connection->id,
                    'shipment_id'   => $shipment->id,
                    'error'         => $e->getMessage(),
                ]);
            }
        }

        $connection->last_sync_at = now();
        $connection->save();

        return compact('changed', 'delivered', 'errors') + ['total' => $rows->count()];
    }
}
