<?php

return [
    'title'                       => 'الشحنات غير الطبيعية',
    'singular'                    => 'شحنة غير طبيعية',

    // بطاقات الملخص
    'stalled_3_days'              => 'متعطلة +3 أيام',
    'stalled_5_days'              => 'متعطلة +5 أيام',
    'stalled_7_days_critical'     => 'متعطلة +7 أيام (حرجة)',
    'closed_as_lost'              => 'مُغلَقة كمفقودة',

    // الفلاتر
    'duration'                    => 'المدة',
    'any_severity'                => 'أي درجة خطورة',
    'any_investigator'            => 'أي محقق',
    'detection_threshold'         => 'حد الاكتشاف',
    'days'                        => 'أيام',

    // أعمدة الجدول
    'last_event'                  => 'آخر حدث',
    'stale_days'                  => 'أيام التوقف',
    'severity'                    => 'الخطورة',
    'no_abnormal'                 => 'لا توجد شحنات غير طبيعية. ستظهر تلقائيًا عند رصدها بواسطة المهمة المجدولة الساعية.',

    // صفحة التفاصيل
    'detected'                    => 'تاريخ الاكتشاف',
    'assigned_to'                 => 'مُسنَدة إلى',
    'nobody_yet'                  => 'لا أحد بعد',
    'stale_progress'              => 'مؤشّر التوقف',
    'event_timeline'              => 'الخط الزمني للأحداث',
    'no_events'                   => 'لا توجد أحداث مُسجَّلة للشحنة.',
    'investigation'               => 'التحقيق',
    'assign_to_investigator'      => 'إسناد إلى محقق',
    'assign'                      => 'إسناد',
    'take_action'                 => 'اتخاذ إجراء',
    'create_ndr'                  => 'إنشاء بلاغ عدم توصيل',
    'customer_contact_logged'     => 'تم تسجيل محاولة الاتصال بالعميل.',
    'log_customer_contact'        => 'تسجيل اتصال بالعميل',
    'escalate'                    => 'تصعيد',
    'close_as_lost_confirm'       => 'الإغلاق كمفقودة يتطلب تأكيد مشرفَين. هل تريد المتابعة؟',
    'close_as_lost'               => 'إغلاق كمفقودة',
    'resolution_note_placeholder' => 'ملاحظة المعالجة (اختياري)',
    'mark_resolved'               => 'تحديد كمُعالَجة',
    'notes'                       => 'الملاحظات',

    // الإعدادات
    'detection'                   => 'الاكتشاف',
    'detection_after_inactivity'  => 'اعتبار الشحنة غير طبيعية بعد توقّفها مدة…',
    'default_3_days'              => 'الافتراضي: 3 أيام',
    'auto_escalation_threshold'   => 'حد التصعيد التلقائي',
    'auto_escalation_hint'        => 'عند بلوغ مدة التوقّف هذا الحد، يتم إشعار مشرفي الشركة.',
    'exclude_from_detection'      => 'استبعاد من الاكتشاف',
    'public_holidays'             => 'العطل الرسمية',
    'public_holidays_hint'        => 'الأيام المُعلَّمة كعطلات لا تُحتسَب ضمن أيام التوقف.',
    'pending_customs'             => 'في انتظار التخليص الجمركي',
    'pending_customs_hint'        => 'الاحتجاز في الجمارك ليس وضعًا غير طبيعي.',
    'sender_hold'                 => 'موقوفة بطلب من المُرسِل',
    'sender_hold_hint'            => 'التاجر أوقف الشحنة بشكل صريح.',
    'notifications'               => 'الإشعارات',
    'daily_digest_8am'            => 'ملخص يومي في الساعة 8:00 صباحًا',
    'daily_digest_hint'           => 'إشعار فوري للمشرفين بملخص جميع الشحنات غير الطبيعية المفتوحة.',
    'save_settings'               => 'حفظ الإعدادات',
];
