<?php

return [
    'label'    => 'الرئيسية',
    'overview' => 'الصفحة الرئيسية للوحة الإدارة: بطاقات المؤشرات، مخططات الإيرادات، توزيع حالات الشحن، وروابط سريعة لبقية أقسام النظام.',
    'sub_pages' => [

        'dashboard' => [
            'icon'    => 'LayoutDashboard',
            'label'   => 'لوحة المعلومات',
            'purpose' => 'مؤشرات لحظية وملخصات دفترية وحالة خط أنابيب الشحنات عبر المنصة اللوجستية بأكملها. نظرة عامة عالية المستوى لمقاييس الأعمال وتدفّقات الإيراد والشحنات الحديثة مع فلتر تاريخ.',
            'pages' => [
                ['path' => 'الرئيسية', 'desc' => 'ست بطاقات KPI (الشحنات / المستخدمون / التجار / المندوبون / الفروع / الحسابات)، شريط تقدّم لخط أنابيب الشحنات (مُسنَدة / جزئية / مُسلّمة)، ملخّص دفتري (إيراد / مصروف / صافي لـ Courier وCouriers والتجار والضريبة والبنك والفروع)، جدول الشحنات الأخيرة، ثلاث مخطّطات سبارك لـ 7 أيام (الإيراد مقابل المصروف، إيراد التجار، إيراد المندوبين)، ومخطّط أعمدة لأفضل الفروع.'],
            ],
            'fields' => [
                'parcels_count', 'users_count', 'merchants_count', 'deliverymen_count', 'hubs_count', 'accounts_count',
                'pipeline_assigned', 'pipeline_partial_delivered', 'pipeline_delivered',
                'courier_income', 'courier_expense', 'deliveryman_income', 'deliveryman_expense',
                'merchant_income', 'merchant_expense', 'vat_income', 'vat_expense',
                'bank_income', 'bank_expense', 'hub_income', 'hub_expense',
                'recent_parcel_tracking_id', 'recent_parcel_merchant_name', 'recent_parcel_status',
                'recent_parcel_cash_collection', 'recent_parcel_created_at', 'hub_parcels_count',
            ],
            'cross_links' => 'الشحنات (بطاقة المسار وجدول الحديثة)، التجار، المستخدمون، المندوبون، الفروع، الحسابات.',
            'notes'       => 'يمكن فلترة المدى الزمني عبر filter_date (افتراضيًا آخر 7 أيام). إسقاط حسب الدور — Super Admin يرى مقاييس الشركات/الاشتراك، التجار يرون بيانات شركتهم فقط، مديرو الشركة يرون الدفتر الموحّد. المخطّطات السبارك تعرض إيرادًا ومصروفًا بمحور مزدوج. جدول الشحنات الحديثة بصفحات (5). رموز الحالة في StatusPill: 1=معلّق، 2=مُلتقَط، 3=قيد النقل، 4=بالفرع، 5=مُسنَد، 6=خارج للتسليم، 9=مُسلَّم، 10=جزئي. مخطّط الفروع محدود بأعلى 4.',
        ],

    ],
];
