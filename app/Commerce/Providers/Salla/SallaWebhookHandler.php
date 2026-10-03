<?php

namespace App\Commerce\Providers\Salla;

use App\Commerce\Models\CommerceConnection;
use App\Commerce\Models\WebhookEvent;
use App\Commerce\Webhooks\HandlerInterface;
use App\Oms\Exceptions\NormalizationException;
use App\Oms\Normalization\OrderNormalizer;
use App\Oms\Services\OrderService;
use Illuminate\Support\Facades\Log;

/**
 * Phase 3 minimal Salla webhook handler.
 *
 * What works now (Phase 3):
 *   - app.installed / app.store.authorize — log only (token delivery via
 *     this path is part of the OAuth-port work in Phase 3.5)
 *   - app.uninstalled — flip the linked connection to status='paused' so
 *     subsequent jobs / writebacks short-circuit
 *   - order.created / order.updated / order.cancelled — stamp connection
 *     last_event_at and LOG the event id. Actual order materialization
 *     happens in Phase 4 once the OrderNormalizer + OMS Order entity land.
 *   - shipment.creating / shipment.cancelled — log only; writeback handoff
 *     lives in Phase 6 (Fulfillment Module)
 *   - Anything else — log at info, no error
 *
 * Throwing here triggers Laravel's queue retry on the parent job; for
 * Phase 3 we keep the handler permissive (log, don't throw) since we're
 * not yet doing anything that could legitimately fail.
 */
class SallaWebhookHandler implements HandlerInterface
{
    public function __construct(
        private readonly OrderNormalizer $normalizer,
        private readonly OrderService    $orderService,
    ) {}

    public function handle(WebhookEvent $event, CommerceConnection $connection): void
    {
        $type = (string) $event->event_type;

        // Always stamp last_event_at — useful for the admin UI to surface
        // connection liveness even when handlers are still being filled in.
        $connection->last_event_at = now();

        switch (true) {
            case $type === 'app.uninstalled':
                $connection->status = 'paused';
                Log::info('commerce.salla.webhook.app_uninstalled', [
                    'connection_id'   => $connection->id,
                    'remote_store_id' => $connection->remote_store_id,
                ]);
                break;

            case $type === 'app.installed':
            case $type === 'app.store.authorize':
                // Token-delivery wiring lands in Phase 3.5 (OAuth port).
                // For now, just log so ops sees the event arrive.
                Log::info('commerce.salla.webhook.install_event', [
                    'connection_id' => $connection->id,
                    'type'          => $type,
                ]);
                break;

            case str_starts_with($type, 'order.'):
                // Phase 4: normalize into canonical OrderDTO and persist
                // alongside the raw payload. Phase 5 (OMS) reads
                // normalized_payload to materialize the Order entity.
                //
                // NormalizationException is non-retryable — a bad payload
                // won't get better on retry. Catch, stamp the error, keep
                // going. Any other exception is genuinely unexpected;
                // rethrow so IngestWebhookJob triggers Laravel queue retry.
                try {
                    $dto = $this->normalizer->normalize('salla', (array) $event->payload, (int) $connection->company_id);
                    $event->normalized_payload  = $dto->toArray();
                    $event->normalization_error = null;
                    $event->save();

                    // Phase 5 handoff — materialise the canonical Order.
                    // Idempotent: replays hit the same (connection_id,
                    // remote_order_id) UNIQUE key and update in place.
                    $order = $this->orderService->receiveNormalized($dto, $connection, $event);

                    Log::info('commerce.salla.webhook.order_materialized', [
                        'connection_id'   => $connection->id,
                        'type'            => $type,
                        'event_row_id'    => $event->id,
                        'remote_order_id' => $dto->remoteOrderId,
                        'order_id'        => $order->id,
                    ]);
                } catch (NormalizationException $ne) {
                    $event->normalized_payload  = null;
                    $event->normalization_error = mb_substr($ne->getMessage(), 0, 65_000);
                    $event->save();
                    Log::warning('commerce.salla.webhook.normalize_failed', [
                        'connection_id' => $connection->id,
                        'type'          => $type,
                        'event_row_id'  => $event->id,
                        'error'         => $ne->getMessage(),
                    ]);
                }
                break;

            case str_starts_with($type, 'shipment.'):
                Log::info('commerce.salla.webhook.shipment_event', [
                    'connection_id' => $connection->id,
                    'type'          => $type,
                ]);
                // Phase 6: FulfillmentService::handleShipmentEvent(...)
                break;

            default:
                Log::info('commerce.salla.webhook.unhandled', [
                    'connection_id' => $connection->id,
                    'type'          => $type,
                ]);
                break;
        }

        $connection->save();
    }
}
