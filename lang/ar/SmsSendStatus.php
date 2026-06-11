<?php

use App\Enums\SmsSendStatus;

return array (
    SmsSendStatus::PARCEL_CREATE                                 => 'إنشاء الشحنة',
    SmsSendStatus::DELIVERED_CANCEL_CUSTOMER                     => 'إلغاء التسليم — العميل',
    SmsSendStatus::DELIVERED_CANCEL_MERCHANT                     => 'إلغاء التسليم — التاجر',

);
