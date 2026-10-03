<?php

namespace App\Shipping\Events;

use App\Shipping\Models\Shipment;
use Illuminate\Foundation\Events\Dispatchable;

class ShipmentStatusChanged
{
    use Dispatchable;

    public function __construct(
        public readonly Shipment $shipment,
        public readonly ?int $oldStatus,
        public readonly int $newStatus,
    ) {}
}
