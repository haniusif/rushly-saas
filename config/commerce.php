<?php

/**
 * Generic Commerce module config. Provider registry, queue routing, retry
 * policy, logging policy. Adding a new provider = adding a row here + a
 * provider class implementing CommerceProviderInterface.
 *
 * The registry stays empty in Phase 1 — no concrete providers exist yet.
 * Each subsequent phase populates it incrementally (Salla in the first
 * provider migration, Zid second, and so on).
 */
return [

    // Provider registry. CommerceProviderFactory resolves a provider code
    // to its concrete class. Order: keep code -> FQCN stable.
    'providers' => [
        'salla' => [
            'class'        => \App\Commerce\Providers\Salla\SallaProvider::class,
            // Per-provider webhook business-logic handler. Resolved by
            // IngestWebhookJob via container::make so it can constructor-inject.
            'handler'      => \App\Commerce\Providers\Salla\SallaWebhookHandler::class,
            // Per-provider Order normalizer (Phase 4). Resolved by
            // App\Oms\Normalization\OrderNormalizer via container::make so
            // it can inject PayloadValidator + AddressResolver.
            'order_mapper' => \App\Oms\Normalization\Providers\SallaOrderMapper::class,
            'config'       => [
                'base_url'            => env('SALLA_API_BASE', 'https://api.salla.dev/admin/v2'),
                'oauth_authorize_url' => env('SALLA_OAUTH_AUTHORIZE_URL', 'https://accounts.salla.sa/oauth2/auth'),
                'oauth_token_url'     => env('SALLA_OAUTH_TOKEN_URL', 'https://accounts.salla.sa/oauth2/token'),
                'timeout'             => 30,
            ],
        ],
    ],

    // Queue connection / queue name for commerce jobs. Use a dedicated queue
    // so a misbehaving storefront doesn't starve other tenant work.
    'queue' => [
        'connection' => env('COMMERCE_QUEUE_CONNECTION', config('queue.default')),
        'name'       => env('COMMERCE_QUEUE_NAME', 'commerce'),
    ],

    // Retry policy applied uniformly by jobs that call provider APIs.
    'retry' => [
        'tries'   => 3,
        'backoff' => [10, 30, 90],   // seconds — exponential-ish
        'timeout' => 60,             // job-level timeout (seconds)
    ],

    // API logging. Set false to disable. retention_days drives the prune
    // job (Phase 8). sensitive_headers covers every auth scheme we expect
    // to see across providers.
    'logging' => [
        'enabled'           => env('COMMERCE_LOG_API', true),
        'retention_days'    => 30,
        'sensitive_headers' => [
            'authorization',
            'x-api-key',
            'x-manager-token',
            'x-shopify-access-token',
            'x-salla-signature',
            'x-zid-signature',
        ],
    ],

];
