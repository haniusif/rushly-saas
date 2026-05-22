<?php

use App\Enums\ParcelStatus;

return array (
    ParcelStatus::PENDING                                => 'Created',
    ParcelStatus::PICKUP_ASSIGN                          => 'Pickup Assign',
    ParcelStatus::RECEIVED_WAREHOUSE                     => 'Received Warehouse',
    ParcelStatus::DELIVERY_MAN_ASSIGN                    => 'OFD',
    ParcelStatus::PARTIAL_DELIVERED                      => 'Partial Delivered',
    ParcelStatus::DELIVERED                              => 'Delivered',
    ParcelStatus::RETURN_ASSIGN_TO_MERCHANT              => 'RTC',

);
