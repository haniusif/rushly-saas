<?php

namespace App\Fulfillment\Listeners;

use App\Fulfillment\Services\FulfillmentService;
use App\Oms\Events\OrderReceived;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Phase 6 replacement for the Phase 5 log stub.
 *
 * Listens to OrderReceived and asks FulfillmentService to fulfill the
 * order — synchronously in Phase 6 MVP since the strategies are cheap.
 * Phase 6.5+ that dispatches real work (WMS pick tickets, 3PL API
 * calls) will queue this via ShouldQueue to keep the request path
 * fast.
 *
 * Never throws — the fulfillment pipeline records failures on the
 * Fulfillment row itself and dispatches FulfillmentFailed. Bubbling an
 * exception here would fail the parent IngestWebhookJob and trigger
 * pointless retries against Salla.
 */
class RouteToFulfillmentListener
{
    public function __construct(
        private readonly FulfillmentService $service,
    ) {}

    public function handle(OrderReceived $event): void
    {
        try {
            $this->service->fulfill($event->order);
        } catch (Throwable $e) {
            // FulfillmentService already stamped last_error + fired
            // FulfillmentFailed. Log once more here so ops sees the
            // pipeline-level context (order id + listener name).
            Log::error('fulfillment.listener.exception', [
                'order_id' => $event->order->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}
