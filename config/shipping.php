<?php

/**
 * Generic Shipping module config. Provider registry, queue routing, retry
 * policy, logging policy. Adding a new provider = adding a row here + a
 * provider class.
 */
return [

    // Provider registry. ShippingProviderFactory uses this to resolve a
    // provider code to its concrete class. Order: keep code -> FQCN stable.
    'providers' => [
        'logestechs' => [
            'class'   => \App\Shipping\Providers\Logestechs\LogestechsProvider::class,
            'config'  => [
                // The Logestechs API base URL is global (one URL per region) so it
                // sits here, not on the per-tenant connection row. Tenants override
                // via env in dev/staging if pointing at a different cluster.
                'base_url'           => env('LOGESTECHS_BASE_URL', 'https://apisv2.logestechs.com/api'),
                'timeout'            => 30,
                'integration_source' => env('LOGESTECHS_INTEGRATION_SOURCE', 'API'),
            ],
        ],

        'ecoexpress' => [
            'class'  => \App\Shipping\Providers\EcoExpress\EcoExpressProvider::class,
            'config' => [
                // Plain HTTP on 8443 — that is what EcoExpress deployed, not a
                // typo. Their published spec points at staging.ecofreight.ae:9443
                // over HTTPS, which does not resolve. Override per environment.
                'base_url' => env('ECOEXPRESS_BASE_URL', 'http://staging1.focalsoft.ae:8443'),
                'timeout'  => 30,
            ],
        ],

        // Future: 'oto' => [...], 'aramex_v2' => [...]
    ],

    // Queue connection / queue name for shipping jobs. Use a dedicated queue
    // so a misbehaving provider doesn't starve other tenant work.
    'queue' => [
        'connection' => env('SHIPPING_QUEUE_CONNECTION', config('queue.default')),
        'name'       => env('SHIPPING_QUEUE_NAME', 'shipping'),
    ],

    // Retry policy applied uniformly by jobs that call provider APIs.
    'retry' => [
        'tries'         => 3,
        'backoff'       => [10, 30, 90],   // seconds — exponential-ish
        'timeout'       => 60,             // job-level timeout (seconds)
    ],

    // Tracking sync cadence.
    'sync' => [
        'cron'             => '*/5 * * * *',
        'batch_per_run'    => 200,
        'terminal_statuses' => [
            \App\Enums\ParcelStatus::DELIVERED,
            \App\Enums\ParcelStatus::PARTIAL_DELIVERED,
            \App\Enums\ParcelStatus::CANCELLED,
            \App\Enums\ParcelStatus::RETURN_RECEIVED_BY_MERCHANT,
        ],
    ],

    // API logging. Set false to disable. retention_days drives the prune job.
    'logging' => [
        'enabled'        => env('SHIPPING_LOG_API', true),
        'retention_days' => 30,
        // Headers that look credential-ish — masked in request_headers before write.
        'sensitive_headers' => ['authorization', 'company-id', 'x-api-key'],
    ],

];
