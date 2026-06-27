<?php

return [
    'label'    => 'الإعدادات',
    'overview' => 'إعدادات المستأجر: الملف العام، التكاملات، جداول رسوم التوصيل، الرسائل القصيرة، الإشعارات، بوابات الدفع وغيرها.',
    'sub_pages' => [

        'general' => [
            'icon'    => 'Sliders',
            'label'   => 'الإعدادات العامة',
            'purpose' => 'تهيئة مركزية لهوية العلامة وبيانات الاتصال والعملة وتخصيص مظهر الواجهة لكامل النظام.',
            'pages' => [
                ['path' => 'القائمة', 'desc' => 'عرض وتعديل كل الإعدادات العامة.'],
                ['path' => 'تحديث',   'desc' => 'PUT لحفظ التغييرات.'],
            ],
            'fields' => [
                'name', 'copyright', 'phone', 'email', 'address',
                'currency', 'parcel_tracking_prefix', 'invoice_prefix',
                'primary_color', 'sidebar_color', 'topbar_color', 'accent_color',
                'font_family', 'border_radius', 'density',
                'logo_image', 'light_logo_image', 'favicon_image', 'show_landing_page',
            ],
            'cross_links' => 'قائمة العملات (اختيار العملة)؛ التكاملات (إعدادات المظهر).',
            'notes'       => 'أصول العلامة (شعارات، favicon)، أنظمة الألوان، أنماط شاشة الدخول (split / centered / fullbleed)، الخطوط وأنماط الشريط الجانبي. قيم احتياطية للألوان غير المضبوطة.',
        ],

        'integrations' => [
            'icon'    => 'Plug',
            'label'   => 'التكاملات',
            'purpose' => 'إدارة متاجر التجارة الإلكترونية (Salla، Zid، Shopify، WooCommerce)، شركات 3PL، أنظمة المحاسبة/ERP، بوابات الدفع، وخدمات الموقع في لوحة واحدة.',
            'pages' => [
                ['path' => 'القائمة', 'desc' => 'عرض كل فئات التكامل مع بطاقات حالة لكل منصة وخدمة.'],
                ['path' => 'تعديل',  'desc' => 'تهيئة الاعتمادات وعناوين الجسر والافتراضيات لمنصة معيّنة.'],
                ['path' => 'تحديث',  'desc' => 'حفظ إعدادات المنصة (OAuth، أسرار الـ webhook، نقاط API، المدينة/التصنيف/نوع التوصيل الافتراضي).'],
            ],
            'fields' => [
                'is_enabled', 'app_url', 'writeback_token', 'api_base',
                'default_city_id', 'default_category_id', 'default_delivery_type_id',
                'oauth_client_id', 'oauth_client_secret', 'oauth_redirect_uri',
                'webhook_secret', 'app_id', 'authorization_mode',
            ],
            'cross_links' => 'جسور التجارة الإلكترونية (Salla، Zid، Shopify، WooCommerce)؛ 3PL (Aramex، Zajel، DeliveryPanda، Jet، iMile، Logestechs)؛ محاسبة (Qoyod، Daftra)؛ ERP (Odoo)؛ مدفوعات (Stripe، Moyasar، ClickPay، STC Pay)؛ الموقع (Google Maps، العنوان الوطني).',
            'notes'       => 'اعتمادات 3PL/المدفوعات/ERP قد تكون لكل مستأجر أو عامة (.env). عناوين الجسر يمكن أن تتجاوز قيم .env. سلة تستخدم OAuth؛ زد/Shopify تستخدمان نمط الجسر. عدد الشحنات يظهر لكل منصة.',
        ],

        'delivery-category' => [
            'icon'    => 'Tags',
            'label'   => 'فئة التوصيل',
            'purpose' => 'تعريف فئات خدمة التوصيل (مثل: نفس اليوم، اليوم التالي، محلي، سريع) مع التحكم بالحالة وترتيب العرض.',
            'pages' => [
                ['path' => 'القائمة', 'desc' => 'قائمة بصفحات مع مؤشرات الحالة وإجراءات لكل صف.'],
                ['path' => 'إنشاء',  'desc' => 'نموذج فئة جديدة.'],
                ['path' => 'تعديل',  'desc' => 'تعديل فئة قائمة.'],
            ],
            'fields' => ['title', 'status', 'position'],
            'status_flow' => [
                ['label' => 'Active',   'tone' => 'ok'],
                ['label' => 'Inactive', 'tone' => 'bad'],
            ],
            'cross_links' => 'رسوم التوصيل (اختيار الفئة)، التكاملات (default_category_id).',
            'notes'       => 'الفئة رقم 1 مقفلة (لا يمكن حذفها). الحالة تُستخدم في حسابات رسوم التوصيل.',
        ],

        'delivery-charge' => [
            'icon'    => 'DollarSign',
            'label'   => 'رسوم التوصيل',
            'purpose' => 'تهيئة طبقات تسعير حسب مديات الوزن وأنواع التوصيل (نفس اليوم، اليوم التالي، داخل المدينة، خارج المدينة).',
            'pages' => [
                ['path' => 'القائمة', 'desc' => 'قائمة قابلة للفلترة بقواعد الرسوم مع صفحات.'],
                ['path' => 'إنشاء',  'desc' => 'نموذج قاعدة رسوم جديدة.'],
                ['path' => 'تعديل',  'desc' => 'تعديل قاعدة.'],
            ],
            'fields' => [
                'category_id', 'weight', 'extra_weight_price',
                'same_day', 'next_day', 'sub_city', 'outside_city',
                'position', 'status',
            ],
            'status_flow' => [
                ['label' => 'Active',   'tone' => 'ok'],
                ['label' => 'Inactive', 'tone' => 'bad'],
            ],
            'cross_links' => 'فئات التوصيل (البحث)، التكاملات (default_delivery_type_id).',
            'notes'       => 'حدود وزن بأسعار لكل نوع. extra_weight_price يُطبَّق فوق الحد. Position يتحكّم في ترتيب العرض. العملة من الإعدادات العامة.',
        ],

        'delivery-type' => [
            'icon'    => 'Truck',
            'label'   => 'نوع التوصيل',
            'purpose' => 'تفعيل/تعطيل أنواع التوصيل الأربعة الأساسية (نفس اليوم، اليوم التالي، داخل المدينة، خارج المدينة) عبر مفاتيح التبديل.',
            'pages' => [
                ['path' => 'القائمة',  'desc' => 'عرض للقراءة فقط لأربعة مفاتيح ثابتة.'],
                ['path' => 'الحالة',  'desc' => 'POST لتقليب نوع تشغيل/إيقاف.'],
            ],
            'fields' => ['same_day', 'next_day', 'sub_city', 'outside_city'],
            'cross_links' => 'رسوم التوصيل (تسعير لكل نوع)، التكاملات (default_delivery_type_id).',
            'notes'       => 'أربعة إدخالات ثابتة (وليست CRUD). تُحفَظ في نموذج Config. تغيير الحالة عبر POST مباشر (بدون submit).',
        ],

        'liquid-fragile' => [
            'icon'    => 'AlertTriangle',
            'label'   => 'سائل / قابل للكسر',
            'purpose' => 'رسم إضافي واحد قابل للتهيئة لشحنات السوائل/القابلة للكسر مع مفتاح تفعيل/تعطيل.',
            'pages' => [
                ['path' => 'القائمة', 'desc' => 'عرض للقراءة فقط للرسم الحالي والحالة.'],
                ['path' => 'تعديل',   'desc' => 'نموذج لتحديث قيمة الرسم.'],
                ['path' => 'الحالة',  'desc' => 'تبديل الرسم مفعّل/غير مفعّل.'],
            ],
            'fields' => ['charge', 'active'],
            'cross_links' => 'رسوم التوصيل (تكامل التسعير).',
            'notes'       => 'سجل واحد في Config. العملة من الإعدادات العامة. نقطة الحالة منفصلة عن التحديث.',
        ],

        'sms' => [
            'icon'    => 'MessageCircle',
            'label'   => 'إعدادات الرسائل القصيرة',
            'purpose' => 'تهيئة اعتمادات عدة مزوّدي SMS (REVE، Twilio، Nexmo، MSEGAT، Taqnyat، 4Jawaly، Unifonic) مع تفعيل/تعطيل لكل مزوّد.',
            'pages' => [
                ['path' => 'القائمة', 'desc' => 'عرض بطاقات كل مزوّدي SMS مع حقولهم ومفاتيح الحالة.'],
                ['path' => 'تحديث',   'desc' => 'حفظ الاعتمادات والحالة لمزوّد معيّن.'],
            ],
            'fields' => [
                'reve_api_key', 'reve_secret_key', 'reve_api_url', 'reve_username', 'reve_user_password',
                'twilio_sid', 'twilio_token', 'twilio_from',
                'nexmo_key', 'nexmo_secret_key',
                'msegat_user_name', 'msegat_api_key', 'msegat_sender',
                'taqnyat_token', 'taqnyat_sender',
                'jawaly4_app_id', 'jawaly4_app_sec', 'jawaly4_sender',
                'unifonic_app_sid', 'unifonic_sender',
                '{provider}_status',
            ],
            'cross_links' => 'إعدادات الإشعارات (قناة بديلة)؛ يستخدمها مرسلو SMS الخاص بالشحنات.',
            'notes'       => 'سبع بطاقات مزوّدين مستقلة، كل واحدة برابط حفظ منفصل. أسماء المرسلين تحتاج اعتمادًا مسبقًا لدى المزوّد (MSEGAT، Taqnyat، 4Jawaly). الـ helper smsSettings() يقرأ الإعدادات العامة.',
        ],

        'notifications' => [
            'icon'    => 'BellRing',
            'label'   => 'إعدادات الإشعارات',
            'purpose' => 'تهيئة Firebase Cloud Messaging (FCM) للإشعارات الفورية.',
            'pages' => [
                ['path' => 'القائمة', 'desc' => 'نموذج عرض/تعديل لاعتمادات FCM.'],
                ['path' => 'تحديث',   'desc' => 'حفظ إعدادات FCM.'],
            ],
            'fields' => ['fcm_secret_key', 'fcm_topic'],
            'cross_links' => 'وحدة الإشعارات الفورية (تستهلك هذه الاعتمادات)، إعدادات SMS (قناة بديلة)، أجهزة المستخدمين (أهداف FCM).',
            'notes'       => 'حقلان مطلوبان. مُقدَّم عبر blade (ليس Inertia). يُخزَّن في نموذج NotificationSetting.',
        ],

        'googlemap' => [
            'icon'    => 'MapPinned',
            'label'   => 'إعدادات خرائط Google',
            'purpose' => 'تخزين والتحقق من مفتاح Google Maps API لميزات الترميز الجغرافي والبحث عن الأماكن والمسارات.',
            'pages' => [
                ['path' => 'القائمة', 'desc' => 'نموذج للصق المفتاح مع رابط خارجي إلى Google Cloud console.'],
                ['path' => 'تحديث',   'desc' => 'حفظ المفتاح.'],
            ],
            'fields' => ['map_key'],
            'cross_links' => 'التكاملات (قسم الموقع)، مسارات التوصيل (الترميز الجغرافي والبحث)، TMS (الخريطة الحيّة).',
            'notes'       => 'الـ APIs المطلوبة: Maps JavaScript، Places، Geocoding، Routes. يُفضَّل تقييد المفتاح بنطاق المستأجر في Google Cloud console.',
        ],

        'social-login' => [
            'icon'    => 'KeyRound',
            'label'   => 'إعدادات الدخول الاجتماعي',
            'purpose' => 'تفعيل دخول Google وFacebook عبر OAuth مع اعتمادات ومفاتيح لكل مزوّد.',
            'pages' => [
                ['path' => 'القائمة', 'desc' => 'بطاقتان جنبًا إلى جنب (Facebook، Google).'],
                ['path' => 'تحديث',   'desc' => 'حفظ الاعتمادات والحالة لمزوّد معيّن.'],
            ],
            'fields' => [
                'google_client_id', 'google_client_secret', 'google_status',
                'facebook_client_id', 'facebook_client_secret', 'facebook_status',
            ],
            'cross_links' => 'نظام المصادقة (مسارات socialRedirect / authGoogleLogin / authFacebookLogin).',
            'notes'       => 'مُقدَّم blade. globalSettings() يقرأ/يحفظ الإعدادات لكل مزوّد. خانة الحالة تستخدم on/off.',
        ],

        'payment-gateway' => [
            'icon'    => 'CreditCard',
            'label'   => 'بوابة الدفع (إعداد المستحقات)',
            'purpose' => 'تهيئة اعتمادات وسائل الدفع (Stripe، PayPal، Razorpay، Skrill، SSL Commerz، Aamarpay، Bkash) مع تفعيل/تعطيل ووضع تجريبي لكل وسيلة.',
            'pages' => [
                ['path' => 'القائمة', 'desc' => 'بطاقة لكل وسيلة دفع.'],
                ['path' => 'تحديث',   'desc' => 'حفظ الاعتمادات والأوضاع التجريبية والحالة لوسيلة معيّنة.'],
            ],
            'fields' => [
                'stripe_publishable_key', 'stripe_secret_key', 'stripe_status',
                'paypal_client_id', 'paypal_client_secret', 'paypal_mode', 'paypal_status',
                'razorpay_key', 'razorpay_secret', 'razorpay_status',
                'skrill_merchant_email', 'skrill_status',
                'sslcommerz_store_id', 'sslcommerz_store_password', 'sslcommerz_testmode', 'sslcommerz_status',
                'aamarpay_store_id', 'aamarpay_signature_key', 'aamarpay_sendbox_mode', 'aamarpay_status',
                'bkash_app_id', 'bkash_app_secret', 'bkash_username', 'bkash_password', 'bkash_test_mode', 'bkash_status',
            ],
            'cross_links' => 'التكاملات (لوحة تكاملات الدفع)، فوترة التاجر (معالجة الدفع)، طلبات المحفظة (شحن التاجر).',
            'notes'       => 'سبع وسائل دفع مستقلة. تُخزَّن كإعدادات عامة (helper globalSettings). PayPal له sandbox/live؛ Aamarpay له sandbox؛ Bkash/SSL Commerz لهما test mode. يستخدمها التجار في الدفع وعند الفواتير.',
        ],

        'packaging' => [
            'icon'    => 'Boxes',
            'label'   => 'التغليف',
            'purpose' => 'تعريف خيارات تغليف قابلة لإعادة الاستخدام مع السعر والحالة وترتيب العرض في تجهيز الشحنات.',
            'pages' => [
                ['path' => 'القائمة', 'desc' => 'قائمة قابلة للترتيب لأنواع التغليف مع الحالة والإجراءات.'],
                ['path' => 'إنشاء',  'desc' => 'نموذج خيار تغليف جديد.'],
                ['path' => 'تعديل',  'desc' => 'تعديل خيار قائم.'],
            ],
            'fields' => ['name', 'position', 'status', 'price', 'image'],
            'status_flow' => [
                ['label' => 'Active',   'tone' => 'ok'],
                ['label' => 'Inactive', 'tone' => 'bad'],
            ],
            'cross_links' => 'الشحنات (اختيار التغليف عند الإنشاء).',
            'notes'       => 'CRUD مُقدَّم blade. رفع صورة لكل تغليف. Position يتحكّم بترتيب العرض في القوائم. السعر لكل وحدة.',
        ],

        'assets-category' => [
            'icon'    => 'Tags',
            'label'   => 'فئة الأصول',
            'purpose' => 'تصنيف أصول الشركة (مركبات، معدات، حاويات) لإدارة المخزون.',
            'pages' => [
                ['path' => 'القائمة', 'desc' => 'قائمة بصفحات لفئات الأصول.'],
                ['path' => 'إنشاء',  'desc' => 'نموذج فئة جديدة.'],
                ['path' => 'تعديل',  'desc' => 'تعديل فئة قائمة.'],
            ],
            'fields' => ['title', 'position'],
            'cross_links' => 'الأصول (تُستخدم عند إنشاء/إسناد الأصل).',
            'notes'       => 'CRUD مُقدَّم blade. Position لترتيب الفرز. تُستخدم لتصنيف مخزون الأصول.',
        ],

        'invoice-generate' => [
            'icon'    => 'FileText',
            'label'   => 'توليد فاتورة يدويًا',
            'purpose' => 'إطلاق توليد فواتير يدوي عبر أمر Artisan لكل معاملات التجار المعلّقة.',
            'pages' => [
                ['path' => 'القائمة', 'desc' => 'وصف + زر توليد واحد.'],
                ['path' => 'توليد',   'desc' => 'يُنفّذ php artisan invoice:generate على الخادم.'],
            ],
            'fields' => [],
            'cross_links' => 'الفواتير، الفواتير المدفوعة (عرض ما بعد التوليد).',
            'notes'       => 'إجراء بزر واحد — بدون مدخلات نموذج. مفيد للتوليد بالجملة عند عدم تنفيذ المهمة المجدولة.',
        ],

    ],
];
