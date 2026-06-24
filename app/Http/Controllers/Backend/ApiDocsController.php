<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ApiDocsController extends Controller
{
    /**
     * Admin-side merchant API docs (auth-gated). Renders the same Redoc
     * embed as the public version but inside AdminLayout.
     */
    public function merchant()
    {
        return $this->renderMerchant(false);
    }

    /**
     * Public version of the merchant docs — shareable link, no auth.
     */
    public function merchantPublic()
    {
        return $this->renderMerchant(true);
    }

    /**
     * OpenAPI 3.1 spec for the merchant surface. Source of truth is the
     * $sections array in {@see self::sections()}. Returned as JSON so any
     * standard tool (Redoc, Swagger UI, Postman import, openapi-generator)
     * can consume it. Public — no auth required.
     */
    public function merchantOpenApi()
    {
        $apiBase = rtrim(url('/api/v10'), '/');
        $spec = $this->buildOpenApi($apiBase, $this->sections());
        return response()->json($spec)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Cache-Control', 'public, max-age=300');
    }

    private function renderMerchant(bool $public)
    {
        $apiBase = rtrim(url('/api/v10'), '/');

        return Inertia::render('Admin/ApiDocs/Merchant', [
            'is_public' => $public,
            'api_base'  => $apiBase,
            'spec_url'  => route('api-docs.merchant.openapi'),
            't' => [
                'title'    => 'Merchant API documentation',
                'subtitle' => 'Reference for the apps and SDKs that integrate with the merchant surface.',
                'breadcrumb_settings' => __('menus.settings') ?: 'Settings',
                'loading'  => 'Loading API documentation…',
            ],
        ]);
    }

    /**
     * Endpoint catalogue. Keep in sync with routes/api.php for the merchant
     * surface. Schema is intentionally minimal — these tables don't have
     * server-side validation rules consistent enough to derive parameter
     * schemas from, so the OpenAPI spec sticks to path / method / summary /
     * security and leaves request bodies as application/json (free-form).
     */
    private function sections(): array
    {
        return [
            [
                'key'         => 'Authentication',
                'description' => 'Public sign-up + sign-in endpoints, then session endpoints behind the merchant Bearer token.',
                'endpoints'   => [
                    ['POST',   '/register',                'Create merchant account',            true],
                    ['POST',   '/signin',                  'Login — returns Sanctum bearer',     true],
                    ['POST',   '/otp-verification',        'Verify OTP',                          true],
                    ['POST',   '/resend-otp',              'Resend OTP',                          true],
                    ['POST',   '/password/email',          'Forgot-password — throttled 5/min',  true],
                    ['POST',   '/password/reset',          'Reset password with token',           true],
                    ['GET',    '/refresh',                 'Refresh current session',             false],
                    ['GET',    '/profile',                 'Current merchant profile',            false],
                    ['POST',   '/profile/update',          'Update profile',                      false],
                    ['PUT',    '/update-password',         'Change password',                     false],
                    ['POST',   '/sign-out',                'Logout — invalidates the token',     false],
                ],
            ],
            [
                'key'         => 'Dashboard',
                'description' => 'KPI tiles, balance card, charts.',
                'endpoints'   => [
                    ['GET',    '/dashboard',                       'KPI summary',                false],
                    ['GET',    '/dashboard/filter',                'KPIs with date filter',      false],
                    ['GET',    '/dashboard/balance-details',       'Current payable / on-hold / paid', false],
                    ['GET',    '/dashboard/available-parcels',     'Parcels ready for action',   false],
                    ['GET',    '/analytics',                       'Chart data',                 false],
                ],
            ],
            [
                'key'         => 'Parcels',
                'description' => 'Create / read / update / cancel parcels owned by the merchant.',
                'endpoints'   => [
                    ['GET',    '/parcel/index',                    'List merchant parcels',                false],
                    ['GET',    '/parcel/filter',                   'List with filters (date, status, …)', false],
                    ['GET',    '/parcel/create',                   'Lookups for the create form',          false],
                    ['POST',   '/parcel/store',                    'Create a parcel',                      false],
                    ['GET',    '/parcel/details/{id}',             'Read parcel',                          false],
                    ['GET',    '/parcel/edit/{id}',                'Lookups for the edit form',            false],
                    ['PUT',    '/parcel/update/{id}',              'Update parcel',                        false],
                    ['DELETE', '/parcel/delete/{id}',              'Delete parcel',                        false],
                    ['GET',    '/parcel/logs/{id}',                'Tracking timeline',                    false],
                    ['GET',    '/parcel/{id}/status/{statusId}',   'Apply a status transition',            false],
                    ['GET',    '/parcel/all/status',               'Parcel status enum',                   false],
                    ['GET',    '/status-wise/parcel/list/{status}','Filter parcels by status',             false],
                ],
            ],
            [
                'key'         => 'Shops',
                'description' => 'The merchant\'s storefronts.',
                'endpoints'   => [
                    ['GET',    '/shops/index',                  'List shops',         false],
                    ['POST',   '/shops/store',                  'Create shop',        false],
                    ['GET',    '/shops/edit/{id}',              'Lookups for edit',   false],
                    ['PUT',    '/shops/update/{id}',            'Update shop',        false],
                    ['DELETE', '/shops/delete/{id}',            'Delete shop',        false],
                ],
            ],
            [
                'key'         => 'Payment accounts',
                'description' => 'Payout destinations attached to this merchant.',
                'endpoints'   => [
                    ['GET',    '/payment-accounts/index',          'List payment accounts',  false],
                    ['POST',   '/payment-account/store',           'Add payment account',    false],
                    ['GET',    '/payment-account/edit/{id}',       'Lookups for edit',       false],
                    ['PUT',    '/payment-account/update',          'Update payment account', false],
                    ['DELETE', '/payment-account/delete/{id}',     'Delete payment account', false],
                ],
            ],
            [
                'key'         => 'Money',
                'description' => 'Invoices, statements, ledger.',
                'endpoints'   => [
                    ['GET',    '/invoice-list/index',              'List invoices',                  false],
                    ['GET',    '/invoice-details/{id}',            'Invoice + line items',           false],
                    ['GET',    '/account-transaction/index',       'Account transactions',           false],
                    ['POST',   '/account-transaction/filter',      'Account transactions, filtered', false],
                    ['GET',    '/statements/index',                'Statements list',                false],
                    ['POST',   '/statements/filter',               'Statements, filtered',           false],
                    ['POST',   '/statement-reports',               'Aggregated summary report',      false],
                ],
            ],
            [
                'key'         => 'Payment requests',
                'description' => 'Cash-out requests from merchant balance.',
                'endpoints'   => [
                    ['GET',    '/payment-request/index',           'List requests',         false],
                    ['GET',    '/payment-request/create',          'Lookups for create',    false],
                    ['POST',   '/payment-request/store',           'Submit request',        false],
                    ['GET',    '/payment-request/edit/{id}',       'Lookups for edit',      false],
                    ['PUT',    '/payment-request/update/{id}',     'Update request',        false],
                    ['DELETE', '/payment-request/delete/{id}',     'Cancel / delete',       false],
                ],
            ],
            [
                'key'         => 'Settings',
                'description' => 'Reference data used by the create-parcel form. Most are public so an unauthenticated landing page can still render the rate card.',
                'endpoints'   => [
                    ['GET',    '/general-settings',                'Public tenant config',     true],
                    ['GET',    '/all-currencies',                  'Currency list',            true],
                    ['GET',    '/hub',                             'Pickup hubs',              true],
                    ['GET',    '/delivery-charges',                'Public rate card',         true],
                    ['GET',    '/rejection_reasons',               'Rejection-reason enum',    true],
                    ['GET',    '/settings/cod-charges',            'COD charges',              false],
                    ['GET',    '/settings/delivery-charges',       'Merchant delivery charges',false],
                ],
            ],
            [
                'key'         => 'Support & fraud',
                'description' => 'Support tickets, fraud reports, news / offers.',
                'endpoints'   => [
                    ['GET',    '/support/index',                   'List tickets',          false],
                    ['GET',    '/support/create',                  'Lookups for create',    false],
                    ['POST',   '/support/store',                   'Open ticket',           false],
                    ['GET',    '/support/edit/{id}',               'Lookups for edit',      false],
                    ['PUT',    '/support/update/{id}',             'Update ticket',         false],
                    ['DELETE', '/support/delete/{id}',             'Delete ticket',         false],
                    ['GET',    '/support/view/{id}',               'Ticket + replies',      false],
                    ['POST',   '/support/reply',                   'Reply to ticket',       false],
                    ['GET',    '/fraud/index',                     'Fraud list',            false],
                    ['POST',   '/fraud/store',                     'Report fraud',          false],
                    ['GET',    '/fraud/edit/{id}',                 'Lookups for edit',      false],
                    ['PUT',    '/fraud/update/{id}',               'Update fraud entry',    false],
                    ['DELETE', '/fraud/delete/{id}',               'Delete fraud entry',    false],
                    ['POST',   '/fraud/check',                     'Quick fraud check by phone / tracking', false],
                    ['GET',    '/news-offer/index',                'Promo banners',         false],
                ],
            ],
            [
                'key'         => 'Push notifications',
                'description' => 'FCM device-token registration.',
                'endpoints'   => [
                    ['POST',   '/fcm-subscribe',                   'Register device token',    false],
                    ['POST',   '/fcm-unsubscribe',                 'Remove device token',      false],
                ],
            ],
            [
                'key'         => 'Public',
                'description' => 'Tracking and marketing endpoints — no Bearer, no apiKey required.',
                'endpoints'   => [
                    ['GET',    '/parcel/tracking/{tracking_id}',   'Public parcel timeline',    true],
                    ['POST',   '/contact-us',                      'Contact form',              true],
                    ['POST',   '/subscribe',                       'Newsletter signup',         true],
                ],
            ],
        ];
    }

    private function buildOpenApi(string $apiBase, array $sections): array
    {
        $paths = [];
        foreach ($sections as $sec) {
            foreach ($sec['endpoints'] as [$method, $path, $summary, $isPublic]) {
                $op = [
                    'tags'    => [$sec['key']],
                    'summary' => $summary,
                    'operationId' => $this->operationId($method, $path),
                    'parameters'  => $this->pathParameters($path),
                    'responses'   => [
                        '200' => ['description' => 'Successful response'],
                        '401' => ['description' => 'Unauthenticated'],
                        '422' => ['description' => 'Validation failed'],
                    ],
                ];

                if (! $isPublic) {
                    $op['security'] = [
                        ['apiKeyHeader' => [], 'bearerAuth' => []],
                    ];
                } else {
                    $op['security'] = [['apiKeyHeader' => []]];
                }

                if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
                    $op['requestBody'] = [
                        'description' => 'JSON request body',
                        'content'     => [
                            'application/json' => [
                                'schema' => ['type' => 'object', 'additionalProperties' => true],
                            ],
                        ],
                    ];
                }

                $paths[$path][strtolower($method)] = $op;
            }
        }

        $tags = collect($sections)->map(fn ($s) => [
            'name'        => $s['key'],
            'description' => $s['description'] ?? '',
        ])->values()->all();

        return [
            'openapi' => '3.1.0',
            'info' => [
                'title'   => 'Rushly Merchant API',
                'version' => '10.0.0',
                'description' => "Endpoints for the merchant mobile / web app.\n\n"
                    . "## Authentication\n\n"
                    . "Non-public endpoints require two headers:\n\n"
                    . "* `apiKey: <RUSHLY_API_KEY>` — the shared static header.\n"
                    . "* `Authorization: Bearer <token>` — Sanctum token minted for the merchant user.\n\n"
                    . "## Minting a bearer token\n\n"
                    . "Run on the tenant's server, replacing `MERCHANT_ID`:\n\n"
                    . "```sh\n"
                    . "php artisan tinker --execute='\n"
                    . "tenancy()->initialize(App\\Models\\Tenant::find(\"YOUR-TENANT-ID\"));\n"
                    . "\$u = App\\Models\\Backend\\Merchant::find(MERCHANT_ID)->user;\n"
                    . "echo \$u->createToken(\"merchant-app\")->plainTextToken . PHP_EOL;\n"
                    . "'\n"
                    . "```\n\n"
                    . "## Multi-tenancy\n\n"
                    . "Every host (`{tenant}.rushly.tech`) is its own data island. Tokens minted under one tenant won't work against another.",
            ],
            'servers' => [
                ['url' => $apiBase, 'description' => 'Current tenant'],
            ],
            'tags'  => $tags,
            'paths' => $paths,
            'components' => [
                'securitySchemes' => [
                    'apiKeyHeader' => [
                        'type'        => 'apiKey',
                        'in'          => 'header',
                        'name'        => 'apiKey',
                        'description' => 'Shared static API key — same for every integration on a tenant.',
                    ],
                    'bearerAuth' => [
                        'type'         => 'http',
                        'scheme'       => 'bearer',
                        'bearerFormat' => 'Sanctum',
                        'description'  => 'Sanctum bearer token minted for the merchant user.',
                    ],
                ],
            ],
        ];
    }

    private function operationId(string $method, string $path): string
    {
        $slug = Str::of($path)->replaceMatches('/\{|\}/', '')
            ->replaceMatches('/[^a-zA-Z0-9]+/', '_')
            ->trim('_')->lower();
        return strtolower($method) . '_' . $slug;
    }

    private function pathParameters(string $path): array
    {
        if (! preg_match_all('/\{([^}]+)\}/', $path, $m)) return [];
        $out = [];
        foreach ($m[1] as $name) {
            $out[] = [
                'name'     => $name,
                'in'       => 'path',
                'required' => true,
                'schema'   => ['type' => 'string'],
            ];
        }
        return $out;
    }
}
