<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Backend\City;
use App\Models\Backend\Deliverycategory;
use App\Models\Backend\IntegrationSetting;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcels_3pl;
use App\Models\Backend\SallaOrderLink;
use App\Models\Backend\WooCommerceOrderLink;
use App\Models\Backend\ZidOrderLink;
use App\Salla\Models\Merchant as SallaMerchant;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class IntegrationsController extends Controller
{
    private const PLATFORMS = ['salla', 'zid', 'shopify', 'woocommerce'];

    private const PLATFORM_HOSTS = [
        'salla'       => 'salla.sa',
        'zid'         => 'zid.sa',
        'shopify'     => 'shopify.com',
        'woocommerce' => 'woocommerce.com',
    ];

    public function index()
    {
        $rows = IntegrationSetting::whereIn('platform', self::PLATFORMS)
            ->get()
            ->keyBy('platform');

        $companyId = settings()->id ?? null;
        $companyMerchantIds = $companyId
            ? Merchant::where('company_id', $companyId)->pluck('id')->all()
            : [];

        $integrations = collect(self::PLATFORMS)->map(function (string $platform) use ($rows, $companyMerchantIds) {
            $s = $rows->get($platform) ?? IntegrationSetting::forPlatform($platform);
            $writeback = (string) ($s->writeback_token ?? '');
            $row = [
                'platform'         => $s->platform,
                'name'             => $s->displayName(),
                'host'             => self::PLATFORM_HOSTS[$s->platform] ?? '',
                'logo_url'         => $s->logoUrl() ?: null,
                'is_enabled'       => (bool) $s->is_enabled,
                'bridge_ready'     => $s->bridgeReady(),
                'app_url'          => $s->app_url,
                'api_base'         => $s->api_base,
                'writeback_set'    => $writeback !== '',
                'writeback_tail'   => $writeback !== '' ? substr($writeback, -4) : null,
                'parcels'          => $this->parcelCount($platform),
                'urls' => [
                    'edit' => route('integrations.edit', $platform),
                ],
            ];

            if ($platform === 'salla' && Schema::hasTable('salla_merchants')) {
                $row['stores_count']        = SallaMerchant::count();
                $row['stores_linked_count'] = SallaMerchant::whereIn('rushly_merchant_id', $companyMerchantIds)->count();
                $row['urls']['stores']      = route('salla.stores.index');
            }

            return $row;
        })->values();

        return Inertia::render('Admin/Integrations/Index', [
            'integrations' => $integrations,
            'three_pls'    => collect($this->buildThreePls())
                ->map(fn ($p) => array_merge($p, ['logo_url' => $this->partnerLogoUrl($p['key'])]))
                ->all(),
            'accounting'   => $this->buildAccounting($companyId),
            'erp'          => $this->buildErp($companyId),
            'payments'     => $this->buildPayments($companyId),
            'permissions'  => [
                'update' => hasPermission('integrations_update'),
            ],
            'urls' => [
                'index' => route('integrations.index'),
            ],
            't' => [
                'title'             => 'Integrations',
                'breadcrumb_settings' => __('menus.settings') ?: 'Settings',
                'ecommerce_title'   => 'E-commerce Integrations',
                'ecommerce_help'    => "Connect Rushly to merchant storefronts. Each integration runs as a standalone bridge app that talks to Rushly's v10 API. Edit the values below to point at your bridge instance and set defaults for parcels created from that platform.",
                'three_pl_title'    => '3PL (Courier) Integrations',
                'three_pl_help'     => 'Outbound courier handover. Credentials live in .env and apply across all tenants — see 3PL.md in the repo root for the current state and known issues.',
                'three_pl_note'     => '3PL credentials are global (not per-tenant). Edit .env on the server to change.',
                'connected'         => 'Connected',
                'needs_config'      => 'Needs config',
                'disabled'          => 'Disabled',
                'bridge_url'        => 'Bridge URL',
                'api_base'          => 'API base',
                'api_key'           => 'API key',
                'parcels_created'   => 'Parcels created',
                'parcels_assigned'  => 'Parcels assigned',
                'writeback_token'   => 'Writeback token',
                'not_set'           => 'not set',
                'configure'         => 'Configure',
                'open_bridge'       => 'Open bridge',
                'config_source'     => 'Config source',
                'stores'            => 'Stores',
                'manage_stores'     => 'Manage stores',
                'linked'            => 'linked',
                'accounting_title'  => 'Accounting',
                'accounting_help'   => 'Push Rushly invoices, payments and courier bills to your accounting system of record.',
                'erp_title'         => 'ERP',
                'erp_help'          => 'Push Rushly merchants, invoices, payments and courier bills into a full ERP. Best for tenants that already manage operations end-to-end in one system.',
                'payments_title'    => 'Payment Integrations',
                'payments_help'     => 'Accept online payments on merchant invoices and the public checkout. Credentials are per-tenant — each tenant configures its own gateway accounts.',
                'methods_label'     => 'Methods',
                'region_label'      => 'Region',
                'api_docs'          => 'API docs',
            ],
        ]);
    }

    private function buildErp(?int $companyId): array
    {
        $odoo = \App\Odoo\Models\Settings::where('company_id', $companyId)->first();
        return [[
            'key'     => 'odoo',
            'name'    => 'Odoo',
            'host'    => 'odoo.com',
            'enabled' => (bool) ($odoo->enabled ?? false),
            'ready'   => $odoo && $odoo->isReady(),
            'urls'    => [
                'settings' => route('odoo.settings.index'),
                'docs'     => 'https://www.odoo.com/documentation/17.0/developer/reference/external_api.html',
            ],
        ]];
    }

    /**
     * Payment gateways. Credentials live in the per-tenant Setting table
     * (key/value, scoped by company_id) — same place Stripe already stores
     * stripe_secret_key. Each card surfaces:
     *  - whether the gateway is configured (ready) and enabled
     *  - the country/region it operates in
     *  - the methods it brokers (Mada, Apple Pay, STC Pay, card, etc.)
     *  - configure URL + external API docs URL.
     *
     * STC Pay is offered as a method by Moyasar / HyperPay / ClickPay rather
     * than as a standalone gateway in most setups — its card therefore links
     * to the Moyasar config and explains the relationship.
     */
    private function buildPayments(?int $companyId): array
    {
        $payoutSetup = route('payout.setup.settings.index');

        $stripeReady   = filled(globalSettings('stripe_secret_key'));
        $stripeOn      = globalSettings('stripe_status') == \App\Enums\Status::ACTIVE;

        $moyasarReady  = filled(globalSettings('moyasar_secret_key'));
        $moyasarOn     = globalSettings('moyasar_status') == \App\Enums\Status::ACTIVE;

        $clickpayReady = filled(globalSettings('clickpay_server_key')) && filled(globalSettings('clickpay_profile_id'));
        $clickpayOn    = globalSettings('clickpay_status') == \App\Enums\Status::ACTIVE;

        return [
            [
                'key'     => 'moyasar',
                'name'    => 'Moyasar',
                'host'    => 'moyasar.com',
                'region'  => 'Saudi Arabia',
                'methods' => ['Mada', 'STC Pay', 'Apple Pay', 'Card'],
                'enabled' => $moyasarOn,
                'ready'   => $moyasarReady,
                'urls'    => [
                    'settings' => $payoutSetup,
                    'docs'     => 'https://docs.moyasar.com/api/api-introduction',
                ],
            ],
            [
                'key'     => 'stripe',
                'name'    => 'Stripe',
                'host'    => 'stripe.com',
                'region'  => 'Global',
                'methods' => ['Card', 'Apple Pay', 'Google Pay'],
                'enabled' => $stripeOn,
                'ready'   => $stripeReady,
                'urls'    => [
                    'settings' => $payoutSetup,
                    'docs'     => 'https://stripe.com/docs/api',
                ],
            ],
            [
                'key'     => 'clickpay',
                'name'    => 'ClickPay',
                'host'    => 'clickpay.com.sa',
                'region'  => 'Saudi Arabia',
                'methods' => ['Mada', 'STC Pay', 'Apple Pay', 'Card'],
                'enabled' => $clickpayOn,
                'ready'   => $clickpayReady,
                'urls'    => [
                    'settings' => $payoutSetup,
                    'docs'     => 'https://docs.clickpay.com.sa/api',
                ],
            ],
            [
                'key'     => 'stcpay',
                'name'    => 'STC Pay',
                'host'    => 'stcpay.com.sa',
                'region'  => 'Saudi Arabia',
                'methods' => ['STC Pay wallet'],
                'enabled' => $moyasarOn || $clickpayOn, // brokered by Moyasar/ClickPay
                'ready'   => $moyasarReady || $clickpayReady,
                'note'    => 'Offered as a method via Moyasar or ClickPay. Enable one of those above to accept STC Pay.',
                'urls'    => [
                    'settings' => $payoutSetup,
                    'docs'     => 'https://stcpay.com.sa/business',
                ],
            ],
        ];
    }

    private function buildAccounting(?int $companyId): array
    {
        $qoyod  = \App\Qoyod\Models\Settings::where('company_id', $companyId)->first();
        $daftra = \App\Daftra\Models\Settings::where('company_id', $companyId)->first();
        return [
            [
                'key'     => 'qoyod',
                'name'    => 'Qoyod',
                'host'    => 'qoyod.com',
                'enabled' => (bool) ($qoyod->enabled ?? false),
                'ready'   => $qoyod && $qoyod->isReady(),
                'urls'    => [
                    'settings' => route('qoyod.settings.index'),
                    'docs'     => 'https://apidoc.qoyod.com/',
                ],
            ],
            [
                'key'     => 'daftra',
                'name'    => 'Daftra',
                'host'    => 'daftra.com',
                'enabled' => (bool) ($daftra->enabled ?? false),
                'ready'   => $daftra && $daftra->isReady(),
                'urls'    => [
                    'settings' => route('daftra.settings.index'),
                    'docs'     => 'https://docs.daftara.dev/',
                ],
            ],
        ];
    }

    public function edit(string $platform)
    {
        abort_unless(in_array($platform, self::PLATFORMS, true), 404);

        $setting    = IntegrationSetting::forPlatform($platform);
        $cities     = City::orderBy('name')->get(['id', 'name']);
        $categories = Deliverycategory::orderBy('title')->get(['id', 'title']);

        $deliveryTypes = [
            1 => 'same_day',
            2 => 'next_day',
            3 => 'sub_city',
            4 => 'outside_City',
        ];

        return Inertia::render('Admin/Integrations/Edit', [
            'setting' => [
                'platform'                 => $setting->platform,
                'name'                     => $setting->displayName(),
                'logo_url'                 => $setting->logoUrl() ?: null,
                'is_enabled'               => (bool) $setting->is_enabled,
                'app_url'                  => $setting->app_url ?: '',
                'writeback_token'          => $setting->writeback_token ?: '',
                'api_base'                 => $setting->api_base ?: '',
                'default_city_id'          => $setting->default_city_id ? (string) $setting->default_city_id : '',
                'default_category_id'      => $setting->default_category_id ? (string) $setting->default_category_id : '',
                'default_delivery_type_id' => $setting->default_delivery_type_id ? (string) $setting->default_delivery_type_id : '',
            ],
            'lookups' => [
                'cities'         => $cities->map(fn ($c) => ['value' => (string) $c->id, 'label' => $c->name])->values(),
                'categories'     => $categories->map(fn ($c) => ['value' => (string) $c->id, 'label' => $c->title])->values(),
                'delivery_types' => collect($deliveryTypes)->map(fn ($k, $id) => [
                    'value' => (string) $id,
                    'label' => (__('deliveryType.' . $k) === 'deliveryType.' . $k)
                        ? str_replace('_', ' ', $k)
                        : (string) __('deliveryType.' . $k),
                ])->values(),
            ],
            'placeholders' => [
                'app_url'  => 'https://' . $platform . '.rushly.test',
                'api_base' => 'https://api.' . $platform . '.' . (in_array($platform, ['shopify', 'woocommerce']) ? 'com' : 'sa'),
            ],
            'env_keys' => [
                'app_url_env'   => 'RUSHLY_' . strtoupper($platform) . '_APP_URL',
                'writeback_env' => 'RUSHLY_' . strtoupper($platform) . '_WRITEBACK_TOKEN',
            ],
            'urls' => [
                'submit'  => route('integrations.update', $platform),
                'cancel'  => route('integrations.index'),
            ],
            't' => [
                'title'                  => $setting->displayName() . ' integration',
                'breadcrumb_settings'    => __('menus.settings') ?: 'Settings',
                'breadcrumb_integrations'=> 'Integrations',
                'is_enabled_label'       => 'Enabled — parcel writeback to this platform is active',
                'bridge_section'         => 'Bridge connection',
                'bridge_help'            => 'These values override the equivalent .env entries. Leave a field empty to fall back to .env.',
                'app_url'                => 'Bridge app URL',
                'app_url_hint'           => 'Where rushly-saas POSTs parcel status updates to ({URL}/internal/parcel-status).',
                'writeback_token'        => 'Writeback bearer token',
                'writeback_hint'         => "Must match RUSHLY_WRITEBACK_TOKEN on the bridge app's side.",
                'api_base'               => 'Platform API base URL',
                'api_base_hint'          => 'Only used for reference; the bridge app owns the actual API client.',
                'defaults_section'       => 'Defaults for parcels created via this platform',
                'defaults_help'          => 'When an order arrives without a city / category / delivery type, these values are used.',
                'default_city'           => 'Default city',
                'default_category'       => 'Default delivery category',
                'default_delivery_type'  => 'Default delivery type',
                'none_option'            => '— none —',
                'where_title'            => 'Where things live',
                'where_bridge_code'      => 'Bridge app code',
                'where_link_table'       => 'Link table',
                'where_external_endpoint'=> 'External parcel endpoint',
                'where_writeback_endpoint' => 'Status writeback endpoint on bridge',
                'save'                   => __('levels.save') ?: 'Save',
                'cancel'                 => __('levels.cancel') ?: 'Cancel',
                'back'                   => __('levels.back') ?: 'Back',
            ],
        ]);
    }

    public function update(Request $request, string $platform)
    {
        abort_unless(in_array($platform, self::PLATFORMS, true), 404);

        $data = $request->validate([
            'is_enabled'               => ['nullable', 'boolean'],
            'app_url'                  => ['nullable', 'url', 'max:255'],
            'writeback_token'          => ['nullable', 'string', 'max:255'],
            'api_base'                 => ['nullable', 'url', 'max:255'],
            'default_city_id'          => ['nullable', 'integer', 'exists:cities,id'],
            'default_category_id'      => ['nullable', 'integer', 'exists:deliverycategories,id'],
            'default_delivery_type_id' => ['nullable', 'integer'],
        ]);

        $data['is_enabled'] = (bool) ($data['is_enabled'] ?? false);

        $setting = IntegrationSetting::forPlatform($platform);
        $setting->fill($data)->save();

        Toastr::success($setting->displayName() . ' integration saved.', __('message.success'));

        return redirect()->route('integrations.index');
    }

    private function parcelCount(string $platform): int
    {
        return match ($platform) {
            'salla'       => Schema::hasTable('salla_order_links')  ? SallaOrderLink::count()       : 0,
            'zid'         => Schema::hasTable('zid_orders')         ? ZidOrderLink::count()         : 0,
            'woocommerce' => Schema::hasTable('woocommerce_orders') ? WooCommerceOrderLink::count() : 0,
            default       => 0,
        };
    }

    private function threePlCount(string $key): int
    {
        if (! Schema::hasTable('parcels_3pl')) return 0;
        return Parcels_3pl::where('parcel_3pl_name', $key)
            ->whereHas('parcel', fn ($q) => $q->where('company_id', settings()->id))
            ->count();
    }

    /**
     * Resolve a partner logo URL by walking public/images/partners/{key}.{ext}.
     * Mirrors IntegrationSetting::logoUrl() so 3PL cards pick up files like
     * aramex.webp / imile.webp / jet.webp / zid.png without needing the
     * IntegrationSetting Eloquent row.
     */
    private function partnerLogoUrl(string $key): ?string
    {
        foreach (['svg', 'png', 'webp', 'jpg'] as $ext) {
            if (file_exists(public_path("images/partners/{$key}.{$ext}"))) {
                return asset("images/partners/{$key}.{$ext}");
            }
        }
        return null;
    }

    private function buildThreePls(): array
    {
        $pandaKey   = (string) config('services.deliverypanda.key');
        $zajelKey   = (string) config('services.zajel.key');
        $zajelCC    = (string) config('services.zajel.customer_code');
        $aramexUser = (string) config('services.aramex.username');
        $aramexAcct = (string) config('services.aramex.account_number');
        $jetUser    = (string) config('services.jet.username');
        $jetKey     = (string) config('services.jet.api_key');
        $jetSecret  = (string) config('services.jet.secret_key');
        $jetOrder   = (string) config('services.jet.order_url');
        $imileKey   = (string) config('services.imile.api_key');
        $imileBase  = (string) config('services.imile.base_url');
        $logesBase  = (string) config('services.logestechs.base_url');
        $logesKey   = (string) config('services.logestechs.api_key');

        return [
            [
                'key' => 'panda', 'name' => 'DeliveryPanda', 'host' => 'deliverypanda.me',
                'base_url' => (string) config('services.deliverypanda.base_url'),
                'key_set' => $pandaKey !== '',
                'key_tail' => $pandaKey !== '' ? substr($pandaKey, -4) : null,
                'extras' => [],
                'parcels' => $this->threePlCount('panda'),
            ],
            [
                'key' => 'zajel', 'name' => 'Zajel', 'host' => 'zajel.com',
                'base_url' => (string) config('services.zajel.base_url'),
                'key_set' => $zajelKey !== '' && $zajelCC !== '',
                'key_tail' => $zajelKey !== '' ? substr($zajelKey, -4) : null,
                'extras' => array_filter([
                    'Customer code' => $zajelCC !== '' ? $zajelCC : null,
                    'Service type'  => (string) config('services.zajel.service_type_id'),
                    'Webhook URL'   => url('/api/zajel/webhook'),
                ]),
                'parcels' => $this->threePlCount('zajel'),
            ],
            [
                'key' => 'aramex', 'name' => 'Aramex', 'host' => 'aramex.com',
                'base_url' => (string) config('services.aramex.wsdl'),
                'key_set' => $aramexUser !== '' && $aramexAcct !== '',
                'key_tail' => $aramexUser !== '' ? substr($aramexUser, -4) : null,
                'extras' => array_filter([
                    'Account #'      => $aramexAcct !== '' ? $aramexAcct : null,
                    'Account entity' => (string) config('services.aramex.account_entity'),
                    'Country'        => (string) config('services.aramex.account_country_code'),
                    'Product'        => (string) config('services.aramex.product_group') . ' / ' . (string) config('services.aramex.product_type'),
                ]),
                'parcels' => $this->threePlCount('aramex'),
            ],
            [
                'key' => 'jet', 'name' => 'J&T (Jet)', 'host' => 'jet.co.id',
                'base_url' => $jetOrder !== '' ? $jetOrder : '—',
                'key_set' => $jetUser !== '' && $jetKey !== '' && $jetSecret !== '' && $jetOrder !== '',
                'key_tail' => $jetKey !== '' ? substr($jetKey, -4) : null,
                'extras' => array_filter([
                    'Username'       => $jetUser !== '' ? $jetUser : null,
                    'eccompanyid'    => (string) config('services.jet.eccompanyid'),
                    'Default origin' => (string) config('services.jet.default_origin_code'),
                    'Service / Type' => (string) config('services.jet.service_type') . ' (servicetype) / EZ (' . (string) config('services.jet.express_type') . ')',
                ]),
                'parcels' => $this->threePlCount('jet'),
            ],
            [
                'key' => 'logestechs', 'name' => 'Logestechs', 'host' => 'logestechs.com',
                'base_url' => $logesBase !== '' ? $logesBase : '— (STUB — Postman docs pending)',
                'key_set' => $logesBase !== '' && $logesKey !== '',
                'key_tail' => $logesKey !== '' ? substr($logesKey, -4) : null,
                'extras' => [
                    'Status'            => 'STUB — service code present, endpoints/payload await Postman docs',
                    'Target company id' => 'Per-shipment (chosen at assign time)',
                ],
                'parcels' => $this->threePlCount('logestechs'),
            ],
            [
                'key' => 'imile', 'name' => 'iMile', 'host' => 'imile.com',
                'base_url' => $imileBase !== '' ? $imileBase : '— (planned, NDA docs pending)',
                'key_set' => false,
                'key_tail' => $imileKey !== '' ? substr($imileKey, -4) : null,
                'extras' => array_filter([
                    'Status'        => 'Planned — paste iMile API docs to build',
                    'Customer code' => (string) config('services.imile.customer_code'),
                    'Country'       => (string) config('services.imile.country'),
                ]),
                'parcels' => $this->threePlCount('imile'),
            ],
        ];
    }
}
