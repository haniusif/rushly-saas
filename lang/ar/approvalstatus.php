<?php

use App\Enums\ApprovalStatus;

return [
    ApprovalStatus::REJECT    => 'مرفوض',
    ApprovalStatus::APPROVED  => 'تمت الموافقة',
    ApprovalStatus::PENDING   => 'قيد الانتظار',
    ApprovalStatus::PROCESSED => 'تمت المعالجة',

];
