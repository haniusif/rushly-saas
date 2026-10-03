<?php

/**
 * Fulfillment module config. Strategy registry, retry policy, log
 * retention. Adding a new strategy = adding a row here + writing the
 * strategy class implementing FulfillmentStrategyInterface.
 */
return [

    'strategies' => [
        'merchant_self' => [
            'class' => \App\Fulfillment\Strategies\MerchantSelfStrategy::class,
            'label' => 'Merchant self-fulfillment',
        ],
        'wms' => [
            'class' => \App\Fulfillment\Strategies\WmsFulfillmentStrategy::class,
            'label' => 'Warehouse Management (Rushly WMS)',
        ],
        'threepl_dropship' => [
            'class' => \App\Fulfillment\Strategies\ThreePlDropshipStrategy::class,
            'label' => '3PL dropship (via Shipping module)',
        ],
        // 'vendor_direct' => [ ... ]   — Phase 6.5+
    ],

    // Fallback strategy applied when the router finds no matching route.
    // null = no fallback (Fulfillment stays pending for manual assignment).
    // Set to 'merchant_self' if you want unrouted orders to silently no-op.
    'default_strategy' => env('FULFILLMENT_DEFAULT_STRATEGY'),

    // Queue routing for any future queued strategy jobs (Phase 6.5+).
    'queue' => [
        'connection' => env('FULFILLMENT_QUEUE_CONNECTION', config('queue.default')),
        'name'       => env('FULFILLMENT_QUEUE_NAME', 'fulfillment'),
    ],

];
