<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Backend\City;
use App\Models\Backend\Deliverycategory;
use App\Models\Backend\IntegrationSetting;
use App\Models\Backend\Parcels_3pl;
use App\Models\Backend\SallaOrderLink;
use App\Models\Backend\WooCommerceOrderLink;
use App\Models\Backend\ZidOrderLink;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class IntegrationsController extends Controller
{
    // Platforms displayed on the index page. Order is the render order.
    private const PLATFORMS = ['salla', 'zid', 'shopify', 'woocommerce'];

    public function index()
    {
        $rows = IntegrationSetting::whereIn('platform', self::PLATFORMS)
            ->get()
            ->keyBy('platform');

        // Ensure all three rows exist even on fresh installs.
        $integrations = collect(self::PLATFORMS)->map(function (string $platform) use ($rows) {
            return $rows->get($platform) ?? IntegrationSetting::forPlatform($platform);
        });

        $parcelCounts = [
            'salla'       => Schema::hasTable('salla_orders')       ? SallaOrderLink::count()       : 0,
            'zid'         => Schema::hasTable('zid_orders')         ? ZidOrderLink::count()         : 0,
            'shopify'     => 0,
            'woocommerce' => Schema::hasTable('woocommerce_orders') ? WooCommerceOrderLink::count() : 0,
        ];

        // 3PL (courier) integrations — config-based, no per-tenant settings row.
        // Parcel counts are tenant-scoped via the `parcels` relation.
        $pandaKey  = (string) config('services.deliverypanda.key');
        $zajelKey  = (string) config('services.zajel.key');
        $zajelCC   = (string) config('services.zajel.customer_code');
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
        $threePLs = [
            [
                'key'      => 'panda',
                'name'     => 'DeliveryPanda',
                'host'     => 'deliverypanda.me',
                'base_url' => (string) config('services.deliverypanda.base_url'),
                'key_set'  => $pandaKey !== '',
                'key_tail' => $pandaKey !== '' ? substr($pandaKey, -4) : null,
                'extras'   => [],
                'parcels'  => Schema::hasTable('parcels_3pl')
                    ? Parcels_3pl::where('parcel_3pl_name', 'panda')
                        ->whereHas('parcel', fn ($q) => $q->where('company_id', settings()->id))
                        ->count()
                    : 0,
            ],
            [
                'key'      => 'zajel',
                'name'     => 'Zajel',
                'host'     => 'zajel.com',
                'base_url' => (string) config('services.zajel.base_url'),
                'key_set'  => $zajelKey !== '' && $zajelCC !== '',
                'key_tail' => $zajelKey !== '' ? substr($zajelKey, -4) : null,
                'extras'   => [
                    'Customer code' => $zajelCC !== '' ? $zajelCC : null,
                    'Service type'  => (string) config('services.zajel.service_type_id'),
                    'Webhook URL'   => url('/api/zajel/webhook'),
                ],
                'parcels'  => Schema::hasTable('parcels_3pl')
                    ? Parcels_3pl::where('parcel_3pl_name', 'zajel')
                        ->whereHas('parcel', fn ($q) => $q->where('company_id', settings()->id))
                        ->count()
                    : 0,
            ],
            [
                'key'      => 'aramex',
                'name'     => 'Aramex',
                'host'     => 'aramex.com',
                'base_url' => (string) config('services.aramex.wsdl'),
                'key_set'  => $aramexUser !== '' && $aramexAcct !== '',
                'key_tail' => $aramexUser !== '' ? substr($aramexUser, -4) : null,
                'extras'   => [
                    'Account #'      => $aramexAcct !== '' ? $aramexAcct : null,
                    'Account entity' => (string) config('services.aramex.account_entity'),
                    'Country'        => (string) config('services.aramex.account_country_code'),
                    'Product'        => (string) config('services.aramex.product_group') . ' / ' . (string) config('services.aramex.product_type'),
                ],
                'parcels'  => Schema::hasTable('parcels_3pl')
                    ? Parcels_3pl::where('parcel_3pl_name', 'aramex')
                        ->whereHas('parcel', fn ($q) => $q->where('company_id', settings()->id))
                        ->count()
                    : 0,
            ],
            [
                'key'      => 'jet',
                'name'     => 'J&T (Jet)',
                'host'     => 'jet.co.id',
                'base_url' => $jetOrder !== '' ? $jetOrder : '—',
                'key_set'  => $jetUser !== '' && $jetKey !== '' && $jetSecret !== '' && $jetOrder !== '',
                'key_tail' => $jetKey !== '' ? substr($jetKey, -4) : null,
                'extras'   => [
                    'Username'       => $jetUser !== '' ? $jetUser : null,
                    'eccompanyid'    => (string) config('services.jet.eccompanyid'),
                    'Default origin' => (string) config('services.jet.default_origin_code'),
                    'Service / Type' => (string) config('services.jet.service_type') . ' (servicetype) / EZ (' . (string) config('services.jet.express_type') . ')',
                ],
                'parcels'  => Schema::hasTable('parcels_3pl')
                    ? Parcels_3pl::where('parcel_3pl_name', 'jet')
                        ->whereHas('parcel', fn ($q) => $q->where('company_id', settings()->id))
                        ->count()
                    : 0,
            ],
            [
                'key'      => 'logestechs',
                'name'     => 'Logestechs',
                'host'     => 'logestechs.com',
                'base_url' => $logesBase !== '' ? $logesBase : '— (STUB — Postman docs pending)',
                'key_set'  => $logesBase !== '' && $logesKey !== '',
                'key_tail' => $logesKey !== '' ? substr($logesKey, -4) : null,
                'extras'   => [
                    'Status'           => 'STUB — service code present, endpoints/payload await Postman docs',
                    'Target company id'=> 'Per-shipment (chosen at assign time)',
                ],
                'parcels'  => Schema::hasTable('parcels_3pl')
                    ? Parcels_3pl::where('parcel_3pl_name', 'logestechs')
                        ->whereHas('parcel', fn ($q) => $q->where('company_id', settings()->id))
                        ->count()
                    : 0,
            ],
            [
                'key'      => 'imile',
                'name'     => 'iMile',
                'host'     => 'imile.com',
                'base_url' => $imileBase !== '' ? $imileBase : '— (planned, NDA docs pending)',
                'key_set'  => false, // stub — no service yet, never "connected"
                'key_tail' => $imileKey !== '' ? substr($imileKey, -4) : null,
                'extras'   => [
                    'Status'        => 'Planned — paste iMile API docs to build',
                    'Customer code' => (string) config('services.imile.customer_code'),
                    'Country'       => (string) config('services.imile.country'),
                ],
                'parcels'  => Schema::hasTable('parcels_3pl')
                    ? Parcels_3pl::where('parcel_3pl_name', 'imile')
                        ->whereHas('parcel', fn ($q) => $q->where('company_id', settings()->id))
                        ->count()
                    : 0,
            ],
        ];

        return view('backend.integrations.index', compact('integrations', 'parcelCounts', 'threePLs'));
    }

    public function edit(string $platform)
    {
        abort_unless(in_array($platform, self::PLATFORMS, true), 404);

        $setting = IntegrationSetting::forPlatform($platform);

        $cities     = City::orderBy('name')->get(['id', 'name']);
        $categories = Deliverycategory::orderBy('title')->get(['id', 'title']);

        // Delivery types are not a table — they're a hardcoded 4-value list,
        // mapped to int IDs the same way `resources/views/backend/parcel/`
        // forms do (see ParcelRepository::deliveryTypes()). Keep this aligned
        // with that mapping so the same id flows through to created parcels.
        $deliveryTypes = [
            1 => 'same_day',
            2 => 'next_day',
            3 => 'sub_city',
            4 => 'outside_City',
        ];

        return view('backend.integrations.edit', compact('setting', 'cities', 'categories', 'deliveryTypes'));
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

        Toastr::success($setting->displayName().' integration saved.', __('message.success'));

        return redirect()->route('integrations.index');
    }
}
