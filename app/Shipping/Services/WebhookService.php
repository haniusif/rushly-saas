<?php

namespace App\Shipping\Services;

use App\Shipping\Contracts\SupportsWebhooks;
use App\Shipping\Events\ShipmentDelivered;
use App\Shipping\Events\ShipmentStatusChanged;
use App\Shipping\Factory\ShippingProviderFactory;
use App\Shipping\Repositories\ShipmentRepository;
use Illuminate\Http\Request;

/**
 * Dispatches inbound webhook requests to the matching provider, persists the
 * resulting TrackingDTO, fires events. Providers opt into webhooks by
 * implementing the SupportsWebhooks interface.
 *
 * Routed in: routes/api.php or routes/web.php (depending on the provider's
 * preference) as POST /api/shipping/webhooks/{providerCode}. The route
 * resolves the provider and delegates here.
 */
class WebhookService
{
    public function __construct(
        private readonly ShippingProviderFactory $factory,
        private readonly ShipmentRepository $shipments,
    ) {}

    public function handle(string $providerCode, Request $request): array
    {
        $provider = $this->factory->make($providerCode);
        if (! $provider instanceof SupportsWebhooks) {
            return ['ok' => false, 'reason' => 'provider does not support webhooks'];
        }

        if (! $provider->verifyWebhook($request)) {
            return ['ok' => false, 'reason' => 'verification failed'];
        }

        $dto = $provider->handleWebhook($request);

        // Locate the matching shipment by (provider_code, remote_shipment_id).
        // Connection scope is intentionally not required: webhooks arrive without
        // a connection id, but remote_shipment_id is unique within a provider.
        $shipment = \App\Shipping\Models\Shipment::query()
            ->whereHas('connection.provider', fn ($p) => $p->where('code', $providerCode))
            ->where('remote_shipment_id', $dto->remoteShipmentId)
            ->orderByDesc('id')
            ->first();

        if (! $shipment) {
            return ['ok' => true, 'matched' => false];
        }

        $old = $shipment->current_status_local;
        $shipment->current_status_raw   = $dto->rawStatus;
        $shipment->current_status_local = $dto->localStatus;
        $shipment->last_status_at       = $dto->occurredAt ?: now();
        $shipment->last_synced_at       = now();
        $shipment->save();

        if ($dto->localStatus !== null && $dto->localStatus !== $old) {
            event(new ShipmentStatusChanged($shipment, $old, $dto->localStatus));
            if ($dto->localStatus === \App\Enums\ParcelStatus::DELIVERED) {
                event(new ShipmentDelivered($shipment));
            }
        }

        return ['ok' => true, 'matched' => true, 'shipment_id' => $shipment->id];
    }
}
