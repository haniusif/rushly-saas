<?php

use App\Enums\ParcelStatus;

return array (
    ParcelStatus::PENDING                                => 'قيد الانتظار',
    ParcelStatus::PICKUP_ASSIGN                          => 'تعيين الاستلام',
    ParcelStatus::PICKUP_RE_SCHEDULE                     => 'إعادة جدولة الاستلام',
    ParcelStatus::RECEIVED_BY_PICKUP_MAN                 => 'تم الاستلام بواسطة المندوب',
    ParcelStatus::RECEIVED_WAREHOUSE                     => 'تم الاستلام في المستودع',
    ParcelStatus::TRANSFER_TO_HUB                        => 'تم النقل إلى المركز',
    ParcelStatus::RECEIVED_BY_HUB                        => 'تم الاستلام في المركز',
    ParcelStatus::DELIVERY_MAN_ASSIGN                    => 'تعيين مندوب التوصيل',
    ParcelStatus::RETURN_TO_COURIER                      => 'إرجاع إلى الشركة',
    ParcelStatus::PARTIAL_DELIVERED                      => 'تم التسليم الجزئي',
    ParcelStatus::DELIVERED                              => 'تم التسليم',
    ParcelStatus::RETURN_ASSIGN_TO_MERCHANT              => 'إرجاع إلى التاجر',
    ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE            => 'إعادة جدولة الإرجاع إلى التاجر',
    ParcelStatus::RETURN_RECEIVED_BY_MERCHANT            => 'تم استلام الإرجاع بواسطة التاجر',



);
