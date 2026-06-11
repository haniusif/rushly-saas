<?php

use App\Enums\InvoiceStatus;
use App\Models\Backend\Merchantpanel\Invoice;

return [
            'id'                      => 'المعرّف',
            InvoiceStatus::PAID       => 'مدفوعة',
            InvoiceStatus::UNPAID     => 'غير مدفوعة',
            InvoiceStatus::PROCESSING => 'قيد المعالجة',
            'paid_out'                => 'تم الدفع',
            'invoice'                 => 'الفاتورة',
            'status_updated'          => 'تم تحديث حالة الفاتورة بنجاح',
            'status_update'           => 'تحديث الحالة',
            'paid_invoice'            => 'فاتورة مدفوعة',

            'invoice_generated_successfully' => 'تم إنشاء الفاتورة بنجاح',
            'invoice_generate_menually'      => 'إنشاء فاتورة',
            'generate'                       => 'إنشاء',
            'invoice_description'            => 'بعد الضغط على زر "إنشاء"، سيتم إنشاء الفاتورة وفقاً لفترة الدفع الخاصة بالتاجر.'

       ];
