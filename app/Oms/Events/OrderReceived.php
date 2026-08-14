<?php

namespace App\Oms\Events;

use App\Oms\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired the first time an Order is materialised from a webhook.
 * Phase 5: listeners just log + audit-trail. Phase 6: FulfillmentRouter
 * listens and dispatches to a FulfillmentStrategy (WMS / 3PL / vendor).
 */
class OrderReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Order $order) {}
}
