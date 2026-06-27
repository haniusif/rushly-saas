<?php

return [
    'label'    => 'Settings',
    'overview' => 'Tenant configuration: general profile, integrations, delivery charge matrices, SMS, notifications, payment gateways and more.',
    'sub_pages' => [

        'general' => [
            'icon'    => 'Sliders',
            'label'   => 'General Settings',
            'purpose' => 'Central configuration for brand identity, contact info, currency and UI theme customisation across the entire system.',
            'pages' => [
                ['path' => 'Index',  'desc' => 'View and edit all general settings.'],
                ['path' => 'Update', 'desc' => 'PUT to save changes.'],
            ],
            'fields' => [
                'name', 'copyright', 'phone', 'email', 'address',
                'currency', 'parcel_tracking_prefix', 'invoice_prefix',
                'primary_color', 'sidebar_color', 'topbar_color', 'accent_color',
                'font_family', 'border_radius', 'density',
                'logo_image', 'light_logo_image', 'favicon_image', 'show_landing_page',
            ],
            'cross_links' => 'Currency list (currency selection); Integrations (theme settings).',
            'notes'       => 'Brand assets (logos, favicon), colour schemes, login layout variants (split / centered / fullbleed), font families and sidebar styles. Theme fallback values provided for unset colours.',
        ],

        'integrations' => [
            'icon'    => 'Plug',
            'label'   => 'Integrations',
            'purpose' => 'Manage e-commerce storefronts (Salla, Zid, Shopify, WooCommerce), 3PL couriers, accounting / ERP systems, payment gateways and location services in one dashboard.',
            'pages' => [
                ['path' => 'Index',  'desc' => 'List all integration categories with status cards per platform and service.'],
                ['path' => 'Edit',   'desc' => 'Configure credentials, bridge URLs and defaults for a specific platform.'],
                ['path' => 'Update', 'desc' => 'Save platform-specific config (OAuth, webhook secrets, API endpoints, default city / category / delivery-type).'],
            ],
            'fields' => [
                'is_enabled', 'app_url', 'writeback_token', 'api_base',
                'default_city_id', 'default_category_id', 'default_delivery_type_id',
                'oauth_client_id', 'oauth_client_secret', 'oauth_redirect_uri',
                'webhook_secret', 'app_id', 'authorization_mode',
            ],
            'cross_links' => 'E-commerce bridges (Salla, Zid, Shopify, WooCommerce); 3PL (Aramex, Zajel, DeliveryPanda, Jet, iMile, Logestechs); Accounting (Qoyod, Daftra); ERP (Odoo); Payments (Stripe, Moyasar, ClickPay, STC Pay); Location (Google Maps, Saudi National Address).',
            'notes'       => '3PL / payment / ERP credentials can be per-tenant or global (.env). Bridge URLs can override the .env values. Salla uses OAuth; Zid / Shopify use the bridge pattern. Shows parcel count per platform.',
        ],

        'delivery-category' => [
            'icon'    => 'Tags',
            'label'   => 'Delivery Category',
            'purpose' => 'Define delivery service categories (e.g. Same-Day, Next-Day, Local, Express) with status control and display order.',
            'pages' => [
                ['path' => 'Index',  'desc' => 'Paginated list with status indicators and per-row actions.'],
                ['path' => 'Create', 'desc' => 'New category form.'],
                ['path' => 'Edit',   'desc' => 'Modify an existing category.'],
            ],
            'fields' => ['title', 'status', 'position'],
            'status_flow' => [
                ['label' => 'Active',   'tone' => 'ok'],
                ['label' => 'Inactive', 'tone' => 'bad'],
            ],
            'cross_links' => 'Delivery charges (category picker), Integrations (default_category_id).',
            'notes'       => 'Category ID 1 is locked (cannot be deleted). Status used in delivery-charge calculations.',
        ],

        'delivery-charge' => [
            'icon'    => 'DollarSign',
            'label'   => 'Delivery Charge',
            'purpose' => 'Configure pricing tiers based on weight ranges and delivery types (same-day, next-day, sub-city, outside-city).',
            'pages' => [
                ['path' => 'Index',  'desc' => 'Filterable list of charge rules with pagination.'],
                ['path' => 'Create', 'desc' => 'New charge rule form.'],
                ['path' => 'Edit',   'desc' => 'Modify a rule.'],
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
            'cross_links' => 'Delivery categories (lookup), Integrations (default_delivery_type_id).',
            'notes'       => 'Weight thresholds with per-type pricing. extra_weight_price applies above the threshold. Position controls display order. Currency shown from global settings.',
        ],

        'delivery-type' => [
            'icon'    => 'Truck',
            'label'   => 'Delivery Type',
            'purpose' => 'Enable / disable the four core delivery types (Same-Day, Next-Day, Sub-City, Outside-City) via toggle switches.',
            'pages' => [
                ['path' => 'Index',  'desc' => 'Read-only view of the four hard-coded toggles.'],
                ['path' => 'Status', 'desc' => 'POST endpoint to flip a type on/off.'],
            ],
            'fields' => ['same_day', 'next_day', 'sub_city', 'outside_city'],
            'cross_links' => 'Delivery charges (pricing per type), Integrations (default_delivery_type_id).',
            'notes'       => 'Four static entries (not CRUD). Persisted in the Config model. Status change is a direct POST (no form submit).',
        ],

        'liquid-fragile' => [
            'icon'    => 'AlertTriangle',
            'label'   => 'Liquid / Fragile',
            'purpose' => 'Single configurable surcharge for liquid / fragile shipments with enable/disable toggle.',
            'pages' => [
                ['path' => 'Index',  'desc' => 'View-only display of current charge and status.'],
                ['path' => 'Edit',   'desc' => 'Form to update the charge amount.'],
                ['path' => 'Status', 'desc' => 'Toggle the surcharge active / inactive.'],
            ],
            'fields' => ['charge', 'active'],
            'cross_links' => 'Delivery charges (pricing integration).',
            'notes'       => 'Single-record config in the Config table. Currency from global settings. Status endpoint is separate from Update.',
        ],

        'sms' => [
            'icon'    => 'MessageCircle',
            'label'   => 'SMS Settings',
            'purpose' => 'Configure multiple SMS provider credentials (REVE, Twilio, Nexmo, MSEGAT, Taqnyat, 4Jawaly, Unifonic) with per-provider enable / disable.',
            'pages' => [
                ['path' => 'Index',  'desc' => 'Display all SMS-provider cards with their fields and status toggles.'],
                ['path' => 'Update', 'desc' => 'Save credentials and status for a specific provider.'],
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
            'cross_links' => 'Notification settings (alternative delivery channel); used by Parcel SMS dispatchers.',
            'notes'       => 'Seven independent provider cards, each with its own submit URL. Sender IDs must be pre-approved on the provider side (MSEGAT, Taqnyat, 4Jawaly). Helper smsSettings() reads the global config row.',
        ],

        'notifications' => [
            'icon'    => 'BellRing',
            'label'   => 'Notification Settings',
            'purpose' => 'Configure Firebase Cloud Messaging (FCM) for push notifications.',
            'pages' => [
                ['path' => 'Index',  'desc' => 'View / edit form for FCM credentials.'],
                ['path' => 'Update', 'desc' => 'Save FCM settings.'],
            ],
            'fields' => ['fcm_secret_key', 'fcm_topic'],
            'cross_links' => 'Push Notifications module (consumes these credentials), SMS settings (alt channel), User devices (FCM targets).',
            'notes'       => 'Two required fields. Blade-rendered (not Inertia). Stored in NotificationSetting model.',
        ],

        'googlemap' => [
            'icon'    => 'MapPinned',
            'label'   => 'Google Map Settings',
            'purpose' => 'Store and validate Google Maps API key for geocoding, place lookup and routing features.',
            'pages' => [
                ['path' => 'Index',  'desc' => 'Form to paste the API key, with an external link to the Google Cloud console.'],
                ['path' => 'Update', 'desc' => 'Save the API key.'],
            ],
            'fields' => ['map_key'],
            'cross_links' => 'Integrations (Location section), Delivery routes (geocoding, place search), TMS (live map).',
            'notes'       => 'Required APIs: Maps JavaScript, Places, Geocoding, Routes. Key should be restricted to the tenant domain(s) in the Google Cloud console.',
        ],

        'social-login' => [
            'icon'    => 'KeyRound',
            'label'   => 'Social Login Settings',
            'purpose' => 'Enable Google and Facebook OAuth login with per-provider credentials and toggle switches.',
            'pages' => [
                ['path' => 'Index',  'desc' => 'Two side-by-side provider cards (Facebook, Google).'],
                ['path' => 'Update', 'desc' => 'Save credentials and status for a specific provider.'],
            ],
            'fields' => [
                'google_client_id', 'google_client_secret', 'google_status',
                'facebook_client_id', 'facebook_client_secret', 'facebook_status',
            ],
            'cross_links' => 'Auth system (socialRedirect / authGoogleLogin / authFacebookLogin routes).',
            'notes'       => 'Blade-rendered. globalSettings() reads / stores per-provider config. Status checkbox uses the on / off convention.',
        ],

        'payment-gateway' => [
            'icon'    => 'CreditCard',
            'label'   => 'Payment Gateway (Payout Setup)',
            'purpose' => 'Configure payment-method credentials (Stripe, PayPal, Razorpay, Skrill, SSL Commerz, Aamarpay, Bkash) with per-method enable / disable and test modes.',
            'pages' => [
                ['path' => 'Index',  'desc' => 'One card per payment method.'],
                ['path' => 'Update', 'desc' => 'Save credentials, test modes and status for a specific method.'],
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
            'cross_links' => 'Integrations (Payment Integrations dashboard), Merchant invoicing (payment processing), Wallet Request (merchant top-ups).',
            'notes'       => 'Seven payment methods on independent cards. Stored as global settings (globalSettings helper). PayPal has sandbox / live; Aamarpay has sandbox; Bkash / SSL Commerz have test mode. Used by merchants for checkout and invoice payment.',
        ],

        'packaging' => [
            'icon'    => 'Boxes',
            'label'   => 'Packaging',
            'purpose' => 'Define reusable packaging options with price, status and display position for parcel fulfillment.',
            'pages' => [
                ['path' => 'Index',  'desc' => 'Sortable list of packaging types with status and actions.'],
                ['path' => 'Create', 'desc' => 'New packaging option form.'],
                ['path' => 'Edit',   'desc' => 'Modify an existing option.'],
            ],
            'fields' => ['name', 'position', 'status', 'price', 'image'],
            'status_flow' => [
                ['label' => 'Active',   'tone' => 'ok'],
                ['label' => 'Inactive', 'tone' => 'bad'],
            ],
            'cross_links' => 'Parcels (packaging selection at create-time).',
            'notes'       => 'Blade-rendered CRUD. Image upload per packaging. Position controls display order in dropdowns. Price is per unit.',
        ],

        'assets-category' => [
            'icon'    => 'Tags',
            'label'   => 'Asset Category',
            'purpose' => 'Categorise company assets (vehicles, equipment, containers) for inventory management.',
            'pages' => [
                ['path' => 'Index',  'desc' => 'Paginated list of asset categories.'],
                ['path' => 'Create', 'desc' => 'New category form.'],
                ['path' => 'Edit',   'desc' => 'Modify an existing category.'],
            ],
            'fields' => ['title', 'position'],
            'cross_links' => 'Assets (used at asset creation / assignment).',
            'notes'       => 'Blade-rendered CRUD. Position for sort order. Used to classify the asset inventory.',
        ],

        'invoice-generate' => [
            'icon'    => 'FileText',
            'label'   => 'Invoice Generate Manually',
            'purpose' => 'Trigger manual invoice generation via the Artisan command for all pending merchant transactions.',
            'pages' => [
                ['path' => 'Index',    'desc' => 'Description + a single Generate button.'],
                ['path' => 'Generate', 'desc' => 'Executes php artisan invoice:generate on the server.'],
            ],
            'fields' => [],
            'cross_links' => 'Invoices, Paid Invoices (downstream views of the generated invoices).',
            'notes'       => 'Single-button action — no form inputs. Useful for batch invoice creation when the scheduled job hasn’t fired.',
        ],

    ],
];
