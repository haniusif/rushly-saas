<?php

namespace App\Salla\Webhooks;

use App\Salla\Webhooks\Contracts\Handler;
use App\Salla\Webhooks\Handlers\AppInstalledHandler;
use App\Salla\Webhooks\Handlers\AppStoreAuthorizeHandler;
use App\Salla\Webhooks\Handlers\AppUninstalledHandler;
use App\Salla\Webhooks\Handlers\OrderCancelledHandler;
use App\Salla\Webhooks\Handlers\OrderCreatedHandler;
use App\Salla\Webhooks\Handlers\OrderUpdatedHandler;
use App\Salla\Webhooks\Handlers\ShipmentCancelledHandler;
use App\Salla\Webhooks\Handlers\ShipmentCreatingHandler;
use Illuminate\Support\Facades\Log;

class Dispatcher
{
    /** @var array<string, class-string<Handler>> */
    private array $map = [
        'app.installed'         => AppInstalledHandler::class,
        'app.uninstalled'       => AppUninstalledHandler::class,
        'app.store.authorize'   => AppStoreAuthorizeHandler::class,
        'order.created'         => OrderCreatedHandler::class,
        'order.updated'         => OrderUpdatedHandler::class,
        'order.cancelled'       => OrderCancelledHandler::class,
        'shipment.creating'     => ShipmentCreatingHandler::class,
        'shipment.cancelled'    => ShipmentCancelledHandler::class,
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
