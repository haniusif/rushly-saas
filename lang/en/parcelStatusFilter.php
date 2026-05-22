<?php

use App\Enums\ParcelStatus;

return array (
    ParcelStatus::PENDING                                => 'Created',
    ParcelStatus::PICKUP_ASSIGN                          => 'Pickup Assign',
    ParcelStatus::PICKUP_RE_SCHEDULE                     => 'Pickup Re-Schedule',
    ParcelStatus::RECEIVED_BY_PICKUP_MAN                 => 'Received By Courier',
    ParcelStatus::RECEIVED_WAREHOUSE                     => 'Received Warehouse',
    ParcelStatus::TRANSFER_TO_HUB                        => 'Transfer to hub',
    ParcelStatus::RECEIVED_BY_HUB                        => 'Received by hub',
    ParcelStatus::DELIVERY_MAN_ASSIGN                    => 'Assign to Courier',
    // ParcelStatus::DELIVERY_RE_SCHEDULE                   => 'Delivery Re-Schedule',
    ParcelStatus::RETURN_TO_COURIER                      => 'Not Delivered',
    // ParcelStatus::PARTIAL_DELIVERED                      => 'Partial Delivered',
    ParcelStatus::DELIVERED                              => 'Delivered',
    ParcelStatus::RETURN_ASSIGN_TO_MERCHANT              => 'Return assign to client',
    ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE            => 'Return assign to client Re-Schedule ',
    ParcelStatus::RETURN_RECEIVED_BY_MERCHANT            => 'Return received by client',
    // ParcelStatus::DELIVER                                => 'Deliver',
    // ParcelStatus::RETURN_WAREHOUSE                       => 'Return Warehouse',
    // ParcelStatus::ASSIGN_MERCHANT                        => 'Assign Merchant',
    // ParcelStatus::RETURNED_MERCHANT                      => 'Returned Merchant',



);
