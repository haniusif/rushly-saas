<?php

/*
|--------------------------------------------------------------------------
| Internal service-to-service API (consumed by rushly-api)
|--------------------------------------------------------------------------
| These endpoints (/api/internal/v1/merchant/*) are NOT public. They are
| called only by the rushly-api façade with a strong service token. The
| defaults below are the explicit routing values used when creating a
| shipment from the public API (which does not expose internal ids).
*/

return [
    'token' => env('RUSHLY_INTERNAL_API_TOKEN'),

    'defaults' => [
        // Parcel category + delivery type used for API-created shipments.
        // Category 1 is the system/global default; delivery type 2 = Next Day.
        'category_id'      => (int) env('RUSHLY_API_DEFAULT_CATEGORY_ID', 1),
        'delivery_type_id' => (int) env('RUSHLY_API_DEFAULT_DELIVERY_TYPE_ID', 2),
    ],
];
