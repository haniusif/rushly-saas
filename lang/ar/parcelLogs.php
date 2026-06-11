<?php

use App\Enums\ParcelStatus;

return [
    ParcelStatus::PICKUP_ASSIGN               => 'تم تعيين مندوب الاستلام',
    ParcelStatus::PICKUP_RE_SCHEDULE          => 'تمت إعادة جدولة استلام الشحنة',
    ParcelStatus::RECEIVED_BY_PICKUP_MAN      => 'تم استلام الشحنة بواسطة مندوب الاستلام',
    ParcelStatus::RECEIVED_WAREHOUSE          => 'تم استلام الشحنة في المستودع',
    ParcelStatus::TRANSFER_TO_HUB             => 'تم نقل الشحنة إلى المركز',
    ParcelStatus::RECEIVED_BY_HUB             => 'تم استلام الشحنة في المركز',
    ParcelStatus::DELIVERY_MAN_ASSIGN         => 'تم تعيين مندوب التوصيل',
    ParcelStatus::DELIVERY_RE_SCHEDULE        => 'تمت إعادة جدولة التسليم',

    ParcelStatus::DELIVER                     => 'التسليم',
    ParcelStatus::RETURN_TO_COURIER           => 'إرجاع إلى الشركة',
    ParcelStatus::RETURN_ASSIGN_TO_MERCHANT   => 'إرجاع إلى التاجر',
    ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE => 'تمت إعادة جدولة الإرجاع إلى التاجر',

    ParcelStatus::DELIVERED                   => 'تم التسليم',
    ParcelStatus::PARTIAL_DELIVERED           => 'تم التسليم الجزئي',
    ParcelStatus::RETURN_WAREHOUSE            => 'مستودع المرتجعات',
    ParcelStatus::ASSIGN_MERCHANT             => 'تعيين التاجر',
    ParcelStatus::RETURNED_MERCHANT           => 'تم الإرجاع إلى التاجر',
    ParcelStatus::RETURN_RECEIVED_BY_MERCHANT => 'تم استلام المرتجع بواسطة التاجر',

    'hub_name'                => 'اسم المركز',
    'hub_phone'               => 'هاتف المركز',
    'delivery_man'            => 'مندوب التوصيل',
    'delivery_man_phone'      => 'هاتف مندوب التوصيل'


];
