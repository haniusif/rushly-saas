<?php

use App\Enums\SalaryStatus;

return [
            SalaryStatus::UNPAID        => 'غير مدفوع',
            SalaryStatus::PARTIAL_PAID  => 'مدفوع جزئياً',
            SalaryStatus::PAID          => 'مدفوع'
    ];
