<?php

namespace App\Salla\Webhooks;

use App\Salla\Webhooks\Contracts\Handler;
use App\Salla\Webhooks\Handlers\AppInstalledHandler;
use App\Salla\Webhooks\Handlers\AppStoreAuthorizeHandler;
use App\Salla\Webhooks\Handlers\AppUninstalledHandler;
use App\Salla\Webhooks\Handlers\AppUpdatedHandler;
use App\Salla\Webhooks\Handlers\OrderCancelledHandler;
use App\Salla\Webhooks\Handlers\OrderCreatedHandler;
use App\Salla\Webhooks\Handlers\OrderRefundedHandler;
use App\Salla\Webhooks\Handlers\OrderStatusUpdatedHandler;
use App\Salla\Webhooks\Handlers\OrderUpdatedHandler;
use App\Salla\Webhooks\Handlers\ShipmentCancelledHandler;
use App\Salla\Webhooks\Handlers\ShipmentCreatingHandler;
use App\Salla\Webhooks\Handlers\ShipmentReturnCancelledHandler;
use App\Salla\Webhooks\Handlers\ShipmentReturnCreatedHandler;
use App\Salla\Webhooks\Handlers\ShipmentReturnCreatingHandler;
use Illuminate\Support\Facades\Log;

class Dispatcher
{
    /**
     * @var array<string, class-string<Handler>>
     *
     * Salla's current shipment-lifecycle events are prefixed `order.shipment.*`
     * (per docs.salla.dev webhook-events-list). The un-prefixed `shipment.*`
     * names are legacy; we keep them mapped so any older store install that
     * still fires them keeps working.
     */
    private array $map = [
        'app.installed'                    => AppInstalledHandler::class,
        'app.uninstalled'                  => AppUninstalledHandler::class,
        'app.updated'                      => AppUpdatedHandler::class,
        'app.store.authorize'              => AppStoreAuthorizeHandler::class,
        'order.created'                    => OrderCreatedHandler::class,
        'order.updated'                    => OrderUpdatedHandler::class,
        'order.status.updated'             => OrderStatusUpdatedHandler::class,
        'order.cancelled'                  => OrderCancelledHandler::class,
        'order.refunded'                   => OrderRefundedHandler::class,
        'order.shipment.creating'          => ShipmentCreatingHandler::class,
        'order.shipment.cancelled'         => ShipmentCancelledHandler::class,
        'order.shipment.return.creating'   => ShipmentReturnCreatingHandler::class,
        'order.shipment.return.created'    => ShipmentReturnCreatedHandler::class,
        'order.shipment.return.cancelled'  => ShipmentReturnCancelledHandler::class,
        'shipment.creating'                => ShipmentCreatingHandler::class,
        'shipment.cancelled'               => ShipmentCancelledHandler::class,
    ];

    public function dispatch(array $event): bool
    {
        $name = (string) ($event['event'] ?? '');
        $class = $this->map[$name] ?? null;

        if (! $class) {
            Log::info('salla.webhook.unhandled', ['event' => $name]);
            return false;
        }

        app($class)->handle($event);
        return true;
    }
}
