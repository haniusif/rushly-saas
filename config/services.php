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
