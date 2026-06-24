<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class ApiDocsController extends Controller
{
    /**
     * Renders the merchant-app API reference. The endpoint list is the source
     * of truth for what the merchant SDK / mobile client should hit — keep it
     * in sync with routes/api.php when adding/removing endpoints.
     */
    public function merchant()
    {
        return $this->renderMerchant(false);
    }

    /**
     * Public version of the merchant docs — same content, no auth, no admin
     * chrome. Useful as a shareable link for integrators evaluating the API.
     */
    public function merchantPublic()
    {
        return $this->renderMerchant(true);
    }

    private function renderMerchant(bool $public)
    {
        $apiBase = rtrim(url('/api/v10'), '/');

        $sections = [
            [
                'key'   => 'auth',
                'title' => 'Authentication',
                'help'  => 'Public endpoints (no Bearer needed) for sign-up + sign-in, then session endpoints behind the merchant Bearer token.',
                'endpoints' => [
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
                'key'   => 'dashboard',
                'title' => 'Dashboard',
                'help'  => 'KPI tiles, balance card, charts.',
                'endpoints' => [
                    ['GET',    '/dashboard',                       'KPI summary',                false],
                    ['GET',    '/dashboard/filter',                'KPIs with date filter',      false],
                    ['GET',    '/dashboard/balance-details',       'Current payable / on-hold / paid', false],
                    ['GET',    '/dashboard/available-parcels',     'Parcels ready for action',   false],
                    ['GET',    '/analytics',                       'Chart data',                 false],
                ],
            ],
            [
                'key'   => 'parcels',
                'title' => 'Parcels (orders)',
                'help'  => 'Create / read / update / cancel parcels owned by this merchant.',
                'endpoints' => [
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
                'key'   => 'shops',
                'title' => 'Shops (storefronts)',
                'endpoints' => [
                    ['GET',    '/shops/index',                  'List the merchant\'s shops', false],
                    ['POST',   '/shops/store',                  'Create shop',                 false],
                    ['GET',    '/shops/edit/{id}',              'Lookups for edit',           false],
                    ['PUT',    '/shops/update/{id}',            'Update shop',                 false],
                    ['DELETE', '/shops/delete/{id}',            'Delete shop',                 false],
                ],
            ],
            [
                'key'   => 'payment_accounts',
                'title' => 'Payment accounts (payout destinations)',
                'endpoints' => [
                    ['GET',    '/payment-accounts/index',          'List payment accounts',  false],
                    ['POST',   '/payment-account/store',           'Add payment account',    false],
                    ['GET',    '/payment-account/edit/{id}',       'Lookups for edit',       false],
                    ['PUT',    '/payment-account/update',          'Update payment account', false],
                    ['DELETE', '/payment-account/delete/{id}',     'Delete payment account', false],
                ],
            ],
            [
                'key'   => 'money',
                'title' => 'Invoices, statements, ledger',
                'endpoints' => [
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
                'key'   => 'payment_requests',
                'title' => 'Payment requests (cash-out)',
                'endpoints' => [
                    ['GET',    '/payment-request/index',           'List requests',         false],
                    ['GET',    '/payment-request/create',          'Lookups for create',    false],
                    ['POST',   '/payment-request/store',           'Submit request',        false],
                    ['GET',    '/payment-request/edit/{id}',       'Lookups for edit',      false],
                    ['PUT',    '/payment-request/update/{id}',     'Update request',        false],
                    ['DELETE', '/payment-request/delete/{id}',     'Cancel / delete',       false],
                ],
            ],
            [
                'key'   => 'settings',
                'title' => 'Settings & reference data',
                'help'  => 'Reference data used by the create-parcel form. Most are public so an unauthenticated landing page can still render the rate card.',
                'endpoints' => [
                    ['GET',    '/general-settings',                'Public tenant config',  true],
                    ['GET',    '/all-currencies',                  'Currency list',         true],
                    ['GET',    '/hub',                             'Pickup hubs',           true],
                    ['GET',    '/delivery-charges',                'Public rate card',      true],
                    ['GET',    '/rejection_reasons',               'Rejection-reason enum', true],
                    ['GET',    '/settings/cod-charges',            'COD charges',           false],
                    ['GET',    '/settings/delivery-charges',       'Merchant delivery charges', false],
                ],
            ],
            [
                'key'   => 'support_fraud',
                'title' => 'Support, fraud check, news',
                'endpoints' => [
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
                'key'   => 'push',
                'title' => 'Push notifications (FCM)',
                'endpoints' => [
                    ['POST',   '/fcm-subscribe',                   'Register device token',    false],
                    ['POST',   '/fcm-unsubscribe',                 'Remove device token',      false],
                ],
            ],
            [
                'key'   => 'public',
                'title' => 'Public — tracking & marketing',
                'help'  => 'No Bearer, no apiKey. Use these for the public tracking page and contact forms.',
                'endpoints' => [
                    ['GET',    '/parcel/tracking/{tracking_id}',   'Public parcel timeline (apiKey not required)', true],
                    ['POST',   '/contact-us',                      'Contact form',  true],
                    ['POST',   '/subscribe',                       'Newsletter signup', true],
                ],
            ],
        ];

        // Shape per endpoint: [method, path, purpose, is_public]
        $sections = collect($sections)->map(function ($s) {
            $s['endpoints'] = collect($s['endpoints'])->map(fn ($e) => [
                'method'    => $e[0],
                'path'      => $e[1],
                'purpose'   => $e[2],
                'is_public' => (bool) ($e[3] ?? false),
            ])->values();
            return $s;
        })->values();

        return Inertia::render('Admin/ApiDocs/Merchant', [
            'sections' => $sections,
            'api_base' => $apiBase,
            'is_public' => $public,
            'api_key_hint' => 'Your RUSHLY_API_KEY (shared static header — same for every integration on this tenant).',
            't' => [
                'title'             => 'Merchant API documentation',
                'subtitle'          => 'Reference for the apps and SDKs that integrate with the merchant surface.',
                'breadcrumb_settings' => __('menus.settings') ?: 'Settings',
                'auth_section'      => 'Authentication',
                'auth_lead'         => 'All non-public endpoints require two headers:',
                'apikey_label'      => 'Shared API key',
                'bearer_label'      => 'Merchant bearer token',
                'mint_section'      => 'Mint a bearer token (server-side)',
                'mint_help'         => 'Run on the tenant\'s server, replacing MERCHANT_ID with the target merchant. Sanctum tokens scope every call to the user that owns them.',
                'try_section'       => 'Curl example',
                'try_help'          => 'Replace {token} and copy into your terminal.',
                'search_placeholder'=> 'Filter endpoints — by path or purpose',
                'public_badge'      => 'public',
                'auth_badge'        => 'auth',
                'no_results'        => 'No endpoints match this filter.',
                'copy'              => 'Copy',
                'copied'            => 'Copied',
            ],
        ]);
    }
}
