<?php

namespace App\Shipping\Events;

use App\Shipping\Models\Shipment;
use Illuminate\Foundation\Events\Dispatchable;

class ShipmentCreated
{
    use Dispatchable;

    public function __construct(public readonly Shipment $shipment) {}
}
