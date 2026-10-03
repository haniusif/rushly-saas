<?php

namespace App\Oms\Events;

use App\Oms\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when an existing Order is re-normalised (e.g. Salla order.updated
 * webhook). Carries the array of columns that changed so listeners can
 * decide whether to react (a status change → Fulfillment; a note change → ignore).
 */
class OrderUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Order $order,
        /** @var array<string, array{from: mixed, to: mixed}> */
        public readonly array $changes,
    ) {}
}
