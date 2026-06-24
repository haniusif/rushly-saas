<?php

return [
    'oauth' => [
        'client_id'     => env('SALLA_OAUTH_CLIENT_ID'),
        'client_secret' => env('SALLA_OAUTH_CLIENT_SECRET'),
        'redirect_uri'  => env('SALLA_OAUTH_CLIENT_REDIRECT_URI'),
    ],

    'webhook_secret' => env('SALLA_WEBHOOK_SECRET'),

    'authorization_mode' => env('SALLA_AUTHORIZATION_MODE', 'easy'),

    'api_base' => env('SALLA_API_BASE', 'https://api.salla.dev/admin/v2'),

    'app_id' => env('SALLA_APP_ID'),

    // Where the OAuth callback should send the browser after a successful
    // (or Easy-Mode) install. Defaults to the app root.
    'landing_url' => env('SALLA_LANDING_URL'),
];
