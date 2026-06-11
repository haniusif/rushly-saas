<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect'      => env('GOOGLE_REDIRECT_URL')
    ],
    'facebook' => [
        'client_id'     => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect'      => env('FACEBOOK_REDIRECT_URL')
    ],
    'stripe' => [
        'secret' => env('STRIPE_SECRET'),
    ],
    'paytm-wallet' => [
        'env' => env('PAYTM_ENVIRONMENT','local'), // values : (local | production)
        'merchant_id' => env('PAYTM_MERCHANT_ID'),
        'merchant_key' => env('PAYTM_MERCHANT_KEY'),
        'merchant_website' => env('PAYTM_MERCHANT_WEBSITE'),
        'channel' => env('PAYTM_CHANNEL'),
        'industry_type' => env('PAYTM_INDUSTRY_TYPE'),
    ],
    
    'deliverypanda' => [
    'key' => env('DELIVERY_PANDA_API_KEY'),
    'base_url' => 'https://app.deliverypanda.me/webservice/',
    'timeout' => 30
],

    // Zajel Merchant API. Production base: https://api.zajel.com:8443/services/integration
    // Staging base:    https://api-stg.zajel.com/services/integration
    'zajel' => [
        'key'             => env('ZAJEL_API_KEY'),
        'customer_code'   => env('ZAJEL_CUSTOMER_CODE'),
        'base_url'        => env('ZAJEL_BASE_URL', 'https://api-stg.zajel.com/services/integration'),
        'service_type_id' => env('ZAJEL_SERVICE_TYPE_ID', 'DDN'),
        'webhook_secret'  => env('ZAJEL_WEBHOOK_SECRET'),
        'timeout'         => 30,
    ],

    // Logestechs — outbound logistics platform handoff (Rushly -> Logestechs).
    // STUB: HTTP plumbing in place but endpoints/payload shape are placeholders
    // until the Postman collection is uploaded. See 3PL.md > Logestechs.
    //
    // Each shipment carries its own target_company_id chosen at assign time
    // (stored on parcels_3pl), so no per-tenant company_id env value is needed.
    'logestechs' => [
        'base_url' => env('LOGESTECHS_BASE_URL'),
        'api_key'  => env('LOGESTECHS_API_KEY'),
        'timeout'  => 30,
    ],

    // iMile (MENA courier). STUB — no service code yet; reserve env keys + show
    // a card on /admin/integrations until the NDA-gated API docs land.
    // Reference attempt: https://www.aftership.com/carriers/imile/api  (that page
    // is AfterShip's TRACKING integration for iMile, not iMile's own create API.)
    'imile' => [
        'api_key'       => env('IMILE_API_KEY'),
        'customer_code' => env('IMILE_CUSTOMER_CODE'),
        'base_url'      => env('IMILE_BASE_URL'),
        'country'       => env('IMILE_COUNTRY', 'AE'),
        'timeout'       => 30,
    ],

    // J&T Express Indonesia (jet.co.id). REST + form-urlencoded.
    // Auth: data_sign = base64(md5(data_param + secret_key)). Tracking uses
    // HTTP Basic with a separate trackPassword. Endpoint URLs are revealed in
    // the customer dashboard after signing the agreement; populate from there.
    'jet' => [
        'username'              => env('JET_USERNAME'),
        'api_key'               => env('JET_API_KEY'),
        'secret_key'            => env('JET_SECRET_KEY'),               // signature secret
        'eccompanyid'           => env('JET_ECCOMPANYID'),              // tracking endpoint
        'track_password'        => env('JET_TRACK_PASSWORD'),           // Basic auth for tracking
        'cus_name'              => env('JET_CUS_NAME'),                 // tariff endpoint
        'order_url'             => env('JET_ORDER_URL'),
        'track_url'             => env('JET_TRACK_URL'),
        'tariff_url'            => env('JET_TARIFF_URL'),
        'cancel_url'            => env('JET_CANCEL_URL'),
        'default_origin_code'   => env('JET_DEFAULT_ORIGIN_CODE', 'JKT'),
        'service_type'          => (int) env('JET_SERVICE_TYPE', 1),    // 1=Pickup, 6=Drop Off
        'express_type'          => env('JET_EXPRESS_TYPE', '1'),        // "1" = EZ (Regular)
        'timeout'               => 30,
    ],

    // Aramex Shipping Services API v1.0 (SOAP).
    // Test  WSDL: https://ws.dev.aramex.net/ShippingAPI.V2/Shipping/Service_1_0.svc?wsdl
    // Prod  WSDL: https://ws.aramex.net/ShippingAPI.V2/Shipping/Service_1_0.svc?wsdl
    'aramex' => [
        'username'             => env('ARAMEX_USERNAME'),
        'password'             => env('ARAMEX_PASSWORD'),
        'version'              => env('ARAMEX_VERSION', 'v1.0'),
        'account_number'       => env('ARAMEX_ACCOUNT_NUMBER'),
        'account_pin'          => env('ARAMEX_ACCOUNT_PIN'),
        'account_entity'       => env('ARAMEX_ACCOUNT_ENTITY', 'DXB'),
        'account_country_code' => env('ARAMEX_ACCOUNT_COUNTRY_CODE', 'AE'),
        'wsdl'                 => env('ARAMEX_WSDL', 'https://ws.dev.aramex.net/ShippingAPI.V2/Shipping/Service_1_0.svc?wsdl'),
        'product_group'        => env('ARAMEX_PRODUCT_GROUP', 'DOM'),       // DOM (domestic) / EXP (express international)
        'product_type'         => env('ARAMEX_PRODUCT_TYPE', 'OND'),        // OND, ONP, PDX, etc.
        'payment_type'         => env('ARAMEX_PAYMENT_TYPE', 'P'),          // P=Prepaid, C=Collect, 3=Third Party
        'timeout'              => 60,
    ],

    'salla' => [
        'app_url'         => env('RUSHLY_SALLA_APP_URL'),
        'writeback_token' => env('RUSHLY_SALLA_WRITEBACK_TOKEN'),
        'api_base'        => env('SALLA_API_BASE', 'https://api.salla.dev/admin/v2'),
    ],

    'zid' => [
        'app_url'         => env('RUSHLY_ZID_APP_URL'),
        'writeback_token' => env('RUSHLY_ZID_WRITEBACK_TOKEN'),
        'api_base'        => env('ZID_API_BASE', 'https://api.zid.sa/v1'),
    ],

    // WooCommerce is a WordPress plugin, not a hosted bridge — each merchant
    // runs their own WP. app_url / writeback_token only act as fallbacks for
    // single-tenant deployments; the real values live on the link row.
    'woocommerce' => [
        'app_url'         => env('RUSHLY_WOOCOMMERCE_APP_URL'),
        'writeback_token' => env('RUSHLY_WOOCOMMERCE_WRITEBACK_TOKEN'),
        'api_base'        => null,
    ],

];
