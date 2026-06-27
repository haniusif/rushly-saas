<?php

return [
    'label'    => 'النظام',
    'overview' => 'التشغيل والمراقبة: سجل النشاط لمن قام بماذا في لوحة الإدارة.',
    'sub_pages' => [

        'logs' => [
            'icon'    => 'History',
            'label'   => 'سجل النشاط',
            'purpose' => 'يسجّل كل إجراءات الإدارة والنظام (إنشاء / تعديل / حذف) عبر الوحدات مع هوية الفاعل، التغييرات الموقّتة، ومراجع الموضوع. ضروري للتدقيق والامتثال وحلّ مشاكل التعديلات غير المصرّح بها أو الخاطئة.',
            'pages' => [
                ['path' => 'القائمة', 'desc' => 'جدول بصفحات (15 صفًا) لكل إدخالات سجل النشاط، مفلتر بالمستأجر الحالي. أعمدة: log_name، نوع الحدث (شارات ملوّنة: created/أخضر، updated/كهرماني، deleted/أحمر)، subject_type، الوصف، الفاعل (اسم المستخدم)، created_at. مرتّب حسب الأحدث. عرض للتفاصيل.'],
                ['path' => 'عرض',     'desc' => 'تفاصيل سجل واحد بجدول قبل/بعد (الخاصية، القيمة الجديدة، القيمة القديمة). تُفكّ JSON من properties.attributes وproperties.old؛ التسميات من lang/{locale}/ActivityLogs.php.'],
            ],
            'fields' => [
                'log_name', 'description', 'subject_type', 'subject_id',
                'causer_type', 'causer_id', 'event', 'properties',
                'batch_uuid', 'created_at', 'updated_at',
            ],
            'cross_links' => 'كل نموذج يستخدم Spatie ActivityLog trait يولّد إدخالات هنا — User وParcel وMerchant وHub وSupport وAccount وPayment وRole وGeneralSettings وNotificationSettings وNewsOffer وSalary وUpload وAssetCategory وPackaging وToDo وغيرها. الإدخالات مجمّعة حسب log_name.',
            'notes'       => 'الاحتفاظ 365 يومًا (config: delete_records_older_than_days). أمر activity:clean يحذف الأقدم. كل الاستعلامات مفلترة بـ company_id (عزل بين المستأجرين). الخصائص تُخزَّن JSON؛ الفروقات عبر $log->changes(). بوابة الصلاحية: log_read للقائمة. لا توجد أعمدة IP / user_agent مخزّنة محليًا.',
        ],

    ],
];
