<?php

use App\Enums\ParcelStatus;
return array (
    ParcelStatus::PENDING                                => 'قيد الانتظار',
    ParcelStatus::PICKUP_ASSIGN                          => 'تعيين الاستلام',
    ParcelStatus::RECEIVED_WAREHOUSE                     => 'تم الاستلام في المستودع',
    ParcelStatus::DELIVERY_MAN_ASSIGN                    => 'تعيين مندوب التوصيل',
    ParcelStatus::PARTIAL_DELIVERED                      => 'تم التسليم الجزئي',
    ParcelStatus::DELIVERED                              => 'تم التسليم',
    ParcelStatus::RETURN_ASSIGN_TO_MERCHANT              => 'إرجاع إلى التاجر',

);
