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

            // Connection-form spec. The form and its validation are both built
            // from this, so a provider's credential shape lives in one place
            // instead of being hardcoded in the Blade/JSX and again in the
            // controller's validate() call.
            //
            // name        'foo' is a column on shipping_connections;
            //             'settings.foo' goes into the JSON settings bag.
            // secret      never echoed back to the browser; blank on edit means
            //             "keep the stored value".
            // resolve     renders the domain lookup button next to the field.
            'form' => [
                ['name' => 'domain',            'label' => 'Domain',     'type' => 'text',     'placeholder' => 'salesksa.logestechs.com', 'resolve' => true,
                 'hint' => 'Paste the tenant domain and resolve to fill the company id.'],
                ['name' => 'remote_company_id', 'label' => 'Company ID', 'type' => 'text',     'placeholder' => '496', 'mono' => true],
                ['name' => 'email',             'label' => 'Email',      'type' => 'email',    'required' => true, 'placeholder' => 'account@example.com'],
                ['name' => 'password',          'label' => 'Password',   'type' => 'password', 'required' => true, 'secret' => true],
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

            // EcoExpress authenticates with OAuth2 client credentials and has
            // no username/password at all, which is why the form had to become
            // provider-driven: the shared form required a password this
            // provider does not have.
            'form' => [
                ['name' => 'settings.client_id',     'label' => 'Client ID',      'type' => 'text',     'required' => true, 'mono' => true,
                 'placeholder' => '17bcbc8e-…'],
                ['name' => 'settings.client_secret', 'label' => 'Client secret',  'type' => 'password', 'required' => true, 'secret' => true],
                ['name' => 'settings.account_no',    'label' => 'Account number', 'type' => 'text',     'required' => true, 'mono' => true,
                 'placeholder' => 'T000020',
                 'hint' => 'The EcoExpress account the shipments are billed to.'],
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
