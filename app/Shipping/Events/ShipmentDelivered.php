<?php

namespace App\Shipping\Events;

use App\Shipping\Models\Shipment;
use Illuminate\Foundation\Events\Dispatchable;

class ShipmentDelivered
{
    use Dispatchable;

    public function __construct(public readonly Shipment $shipment) {}
}
