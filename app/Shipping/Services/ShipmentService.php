<?php

namespace App\Shipping\Services;

use App\Models\Backend\Parcel;
use App\Shipping\DTOs\ConnectionDTO;
use App\Shipping\DTOs\ShipmentDTO;
use App\Shipping\Events\ShipmentCancelled;
use App\Shipping\Events\ShipmentCreated;
use App\Shipping\Factory\ShippingProviderFactory;
use App\Shipping\Jobs\CancelShipmentJob;
use App\Shipping\Jobs\CreateShipmentJob;
use App\Shipping\Models\Shipment;
use App\Shipping\Models\ShippingConnection;
use App\Shipping\Repositories\ShipmentRepository;
use App\Shipping\Repositories\ShippingConnectionRepository;
use InvalidArgumentException;

/**
 * The only entry point business logic (controllers, observers, jobs) uses to
 * create/cancel shipments. Owns the idempotency + tenant-safety checks; the
 * actual provider call happens inside the dispatched job.
 *
 * Why a service + a job (not just a job)?
 *   - Service guarantees tenant + idempotency checks happen synchronously, so
 *     the caller gets immediate feedback ("already created", "not allowed").
 *   - Job carries out the slow provider call out of the request lifecycle.
 *   - For tests, we can call the service directly and assert the dispatched job.
 */
class ShipmentService
{
    public function __construct(
        private readonly ShippingProviderFactory $factory,
        private readonly ShippingConnectionRepository $connections,
        private readonly ShipmentRepository $shipments,
    ) {}

    /**
     * Dispatch a create job for the given parcel + connection. Returns the
     * Shipment row that will hold the result (pre-created in pending state)
     * so callers can render a "queued" status.
     *
     * Tenant safety: parcel.company_id must match connection.company_id.
     * Idempotency: if a Shipment row for (parcel, connection) already exists
     * with a remote_shipment_id, return that one instead of dispatching again.
     */
    public function dispatchCreate(Parcel $parcel, ShippingConnection $connection): Shipment
    {
        if ((int) $parcel->company_id !== (int) $connection->company_id) {
            throw new InvalidArgumentException(
                'Parcel and connection belong to different tenants — refusing.'
            );
        }
        if (! $connection->isReady()) {
            throw new InvalidArgumentException(
                'Connection is not ready (status / credentials / remote_company_id missing).'
            );
        }

        $existing = $this->shipments->findByParcelAndConnection($parcel->id, $connection->id);
        if ($existing && $existing->remote_shipment_id) {
            return $existing;
        }

        // Pre-create the row in 'pending' state so the job has a stable handle
        // and the UI has something to render immediately.
        $shipment = $existing ?: new Shipment([
            'company_id'    => (int) $parcel->company_id,
            'parcel_id'     => (int) $parcel->id,
            'connection_id' => (int) $connection->id,
            'state'         => 'pending',
        ]);
        $shipment->state           = 'pending';
        $shipment->last_sync_error = null;
        $shipment->save();

        CreateShipmentJob::dispatch($shipment->id)
            ->onConnection((string) config('shipping.queue.connection'))
            ->onQueue((string) config('shipping.queue.name', 'shipping'));

        return $shipment;
    }

    /**
     * Synchronous create — used by inline flows (single-parcel admin assign)
     * where the operator wants immediate feedback. Same idempotency contract.
     */
    public function createNow(Parcel $parcel, ShippingConnection $connection): Shipment
    {
        $shipment = $this->dispatchCreate($parcel, $connection);
        if ($shipment->remote_shipment_id) {
            return $shipment;
        }
        $this->executeCreate($shipment);
        return $shipment->fresh();
    }

    /**
     * Called by CreateShipmentJob. Does the actual provider call + result
     * persistence. Not meant to be invoked directly outside the job/service.
     */
    public function executeCreate(Shipment $shipment): void
    {
        $shipment->loadMissing(['connection.provider']);
        $connection = $shipment->connection;
        $parcel     = $shipment->parcel;

        if (! $parcel || ! $connection) {
            $shipment->state = 'failed';
            $shipment->last_sync_error = 'Parcel or connection missing.';
            $shipment->save();
            return;
        }

        try {
            $provider   = $this->factory->forConnection($connection);
            $dto        = ShipmentDTO::fromParcel($parcel);
            $shipment->request_payload = $dto->extra + ['parcel_id' => $dto->parcelId];
            $shipment->save();

            $result = $provider->createShipment(ConnectionDTO::fromModel($connection), $dto);

            $shipment->fill([
                'remote_shipment_id'  => $result->remoteShipmentId,
                'awb_number'          => $result->awbNumber,
                'awb_pdf_url'         => $result->awbPdfUrl,
                'response_payload'    => $result->providerResponse,
                'state'               => 'created',
                'last_sync_error'     => null,
                'last_synced_at'      => now(),
            ])->save();

            event(new ShipmentCreated($shipment->fresh()));
        } catch (\Throwable $e) {
            $shipment->fill([
                'state'           => 'failed',
                'last_sync_error' => mb_substr($e->getMessage(), 0, 1000),
            ])->save();
            throw $e; // let the job handle retry / final failure
        }
    }

    /** Queue a cancellation. Synchronous path: cancelNow(). */
    public function dispatchCancel(Shipment $shipment): void
    {
        if (! $shipment->remote_shipment_id) {
            // Nothing to cancel remotely.
            $shipment->state = 'cancelled';
            $shipment->save();
            return;
        }
        CancelShipmentJob::dispatch($shipment->id)
            ->onConnection((string) config('shipping.queue.connection'))
            ->onQueue((string) config('shipping.queue.name', 'shipping'));
    }

    public function executeCancel(Shipment $shipment): void
    {
        $shipment->loadMissing(['connection.provider']);
        $provider = $this->factory->forConnection($shipment->connection);
        $provider->cancelShipment(ConnectionDTO::fromModel($shipment->connection), (string) $shipment->remote_shipment_id);

        $shipment->state = 'cancelled';
        $shipment->save();

        event(new ShipmentCancelled($shipment));
    }
}
