<?php

use App\Enums\PaymentType;

return[
     PaymentType::STRIPE       => 'سترايب',
     PaymentType::SSL_COMMERZ  => 'SSL Commerz',
     PaymentType::PAYPAL       => 'باي بال',
     PaymentType::PAYONEER     => 'باي أونير',
     PaymentType::BKASH        => 'بي كاش',
     PaymentType::VISA         => 'فيزا',
     PaymentType::SKRILL       => 'سكريل',
     PaymentType::AAMARPAY     => 'أمار باي',

];
