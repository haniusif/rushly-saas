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
            'three_pls'    => $this->buildThreePls(),
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
            ],
        ]);
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
