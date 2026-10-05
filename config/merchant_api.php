<?php

/*
|--------------------------------------------------------------------------
| Rushly Merchant API — façade management API (rushly-api)
|--------------------------------------------------------------------------
| API keys are NOT stored in rushly-saas. They live in the separate
| rushly-api façade, which exposes a service-to-service "management API"
| (POST/GET/revoke under {url}/manage/v1/clients). rushly-saas calls that
| API over HTTP with a per-environment bearer token.
|
| An environment ("live" / "test") is only "available" when BOTH its url
| and token are set; otherwise it is skipped everywhere.
*/

return [
    'live' => [
        'url'   => env('MERCHANT_API_LIVE_URL'),
        'token' => env('MERCHANT_API_LIVE_TOKEN'),
    ],
    'test' => [
        'url'   => env('MERCHANT_API_TEST_URL'),
        'token' => env('MERCHANT_API_TEST_TOKEN'),
    ],
];
