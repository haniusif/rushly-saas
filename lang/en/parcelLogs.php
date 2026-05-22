<?php

use App\Enums\ParcelStatus;

return [
    ParcelStatus::PICKUP_ASSIGN => 'Pickup Assigned',
    ParcelStatus::PICKUP_RE_SCHEDULE => 'Shipment pickup Re-Scheduled',
    ParcelStatus::RECEIVED_BY_PICKUP_MAN => 'Shipment received  courier',
    ParcelStatus::RECEIVED_WAREHOUSE => 'Shipment received to Warehouse',
    ParcelStatus::TRANSFER_TO_HUB        => 'Shipment transfer to hub',
    ParcelStatus::RECEIVED_BY_HUB        => 'Received By Hub',
    ParcelStatus::DELIVERY_MAN_ASSIGN => 'OFD',
    ParcelStatus::DELIVERY_RE_SCHEDULE => 'Delivery Re-Scheduled',

    ParcelStatus::DELIVER => 'Deliver',
    ParcelStatus::RETURN_TO_COURIER => 'Return to Courier',
    ParcelStatus::RETURN_ASSIGN_TO_MERCHANT => 'Assing RTC',
    ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE => 'RTC Re-Schedule',

    ParcelStatus::DELIVERED => 'Delivered',
    ParcelStatus::PARTIAL_DELIVERED => 'Partial Delivered',
    ParcelStatus::RETURN_WAREHOUSE => 'RTO',
    ParcelStatus::ASSIGN_MERCHANT => 'Assign client',
    ParcelStatus::RETURNED_MERCHANT => 'RTC',
    ParcelStatus::RETURN_RECEIVED_BY_MERCHANT => 'Return received by client',

    'hub_name'                      => 'Hub Name',
    'hub_phone'                      => 'Hub Phone',
    'delivery_man'                  => 'Courier',
    'delivery_man_phone'            => 'Courier phone'


];
