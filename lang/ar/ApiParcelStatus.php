<?php

use App\Enums\ParcelStatus;

return [
    ParcelStatus::RETURN_TO_COURIER                      => 'إرجاع إلى الشركة',
    ParcelStatus::PARTIAL_DELIVERED                      => 'تم التسليم الجزئي',
    ParcelStatus::DELIVERED                              => 'تم التسليم'
];
