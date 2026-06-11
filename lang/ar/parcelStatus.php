<?php

use App\Enums\ParcelStatus;

return array (
    ParcelStatus::PENDING                                => 'قيد الانتظار',
    ParcelStatus::PICKUP_ASSIGN                          => 'تعيين الاستلام',
    ParcelStatus::PICKUP_ASSIGN_CANCEL                   => 'إلغاء تعيين الاستلام',
    ParcelStatus::PICKUP_RE_SCHEDULE_CANCEL              => 'إلغاء إعادة جدولة الاستلام',
    ParcelStatus::PICKUP_RE_SCHEDULE                     => 'إعادة جدولة الاستلام',
    ParcelStatus::RECEIVED_BY_PICKUP_MAN                 => 'تم الاستلام بواسطة المندوب',
    ParcelStatus::RECEIVED_BY_PICKUP_MAN_CANCEL          => 'إلغاء استلام المندوب',
    ParcelStatus::RECEIVED_WAREHOUSE                     => 'تم الاستلام في المستودع',
    ParcelStatus::RECEIVED_WAREHOUSE_CANCEL              => 'إلغاء الاستلام في المستودع',
    ParcelStatus::RECEIVED_BY_HUB_CANCEL                 => 'إلغاء الاستلام في المركز',
    ParcelStatus::TRANSFER_TO_HUB                        => 'تم النقل إلى المركز',
    ParcelStatus::TRANSFER_TO_HUB_CANCEL                 => 'إلغاء النقل إلى المركز',
    ParcelStatus::RECEIVED_BY_HUB                        => 'تم الاستلام في المركز',
    ParcelStatus::DELIVERY_MAN_ASSIGN                    => 'تعيين مندوب التوصيل',
    ParcelStatus::DELIVERY_MAN_ASSIGN_CANCEL             => 'إلغاء تعيين مندوب التوصيل',
    ParcelStatus::DELIVERY_RE_SCHEDULE_CANCEL            => 'إلغاء إعادة جدولة التسليم',
    ParcelStatus::DELIVERY_RE_SCHEDULE                   => 'إعادة جدولة التسليم',
    ParcelStatus::PARTIAL_DELIVERED_CANCEL               => 'إلغاء التسليم الجزئي',
    ParcelStatus::RETURN_TO_COURIER                      => 'إرجاع إلى الشركة',
    ParcelStatus::RETURN_TO_COURIER_CANCEL               => 'إلغاء الإرجاع إلى الشركة',
    ParcelStatus::PARTIAL_DELIVERED                      => 'تم التسليم الجزئي',
    ParcelStatus::DELIVERED                              => 'تم التسليم',
    ParcelStatus::DELIVERED_CANCEL                       => 'إلغاء التسليم',
    ParcelStatus::RETURN_ASSIGN_TO_MERCHANT_CANCEL       => 'إلغاء الإرجاع إلى التاجر',
    ParcelStatus::RETURN_ASSIGN_TO_MERCHANT              => 'إرجاع إلى التاجر',
    ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE_CANCEL     => 'إلغاء إعادة جدولة الإرجاع إلى التاجر',
    ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE            => 'إعادة جدولة الإرجاع إلى التاجر',
    ParcelStatus::RETURN_RECEIVED_BY_MERCHANT            => 'تم استلام الإرجاع بواسطة التاجر',
    ParcelStatus::RETURN_RECEIVED_BY_MERCHANT_CANCEL     => 'إلغاء استلام التاجر للإرجاع',
    ParcelStatus::DELIVER                                => 'التسليم',
    ParcelStatus::RETURN_WAREHOUSE                       => 'مستودع الإرجاع',
    ParcelStatus::ASSIGN_MERCHANT                        => 'تعيين التاجر',
    ParcelStatus::RETURNED_MERCHANT                      => 'تاجر مُرجَع',
    ParcelStatus::CANCELLED                              => 'ملغاة',


);
