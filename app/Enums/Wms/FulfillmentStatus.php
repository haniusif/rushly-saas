<?php
namespace App\Enums\Wms;

interface FulfillmentStatus
{
    const PENDING    = 'pending';
    const PICKING    = 'picking';
    const PACKING    = 'packing';
    const READY      = 'ready';
    const DISPATCHED = 'dispatched';
    const CANCELLED  = 'cancelled';
}
