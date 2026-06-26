<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Backend\City;
use App\Models\Backend\Deliverycategory;
use App\Models\Backend\Merchant;
use App\Models\MerchantShops;
use App\Salla\Models\Merchant as SallaMerchant;
use App\Salla\Models\Order as SallaOrder;
use App\Salla\Models\Settings as SallaSettings;
use App\Salla\Models\Shipment as SallaShipment;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SallaStoresController extends Controller
{
    private const DELIVERY_TYPES = [
        1 => 'same_day',
        2 => 'next_day',
        3 => 'sub_city',
        4 => 'outside_City',
    ];

    public function index()
    {
        $companyId = settings()->id ?? null;
        $companyMerchantIds = Merchant::where('company_id', $companyId)->pluck('id')->all();

        $stores = SallaMerchant::with('settings')
            ->orderByDesc('installed')
            ->orderBy('store_name')
            ->get()
            ->map(function (SallaMerchant $m) use ($companyMerchantIds) {
                $linkedMerchant = $m->rushly_merchant_id
                    ? Merchant::find($m->rushly_merchant_id)
                    : null;

                $belongsToCompany = $linkedMerchant
                    ? in_array($linkedMerchant->id, $companyMerchantIds, true)
                    : null; // null = unclaimed

                $settings = $m->settings;

                return [
                    'id'                 => $m->id,
                    'salla_merchant_id'  => (string) $m->salla_merchant_id,
                    'store_name'         => $m->store_name,
                    'store_domain'       => $m->store_domain,
                    'owner_email'        => $m->owner_email,
                    'installed'          => (bool) $m->installed,
                    'uninstalled_at'     => optional($m->uninstalled_at)->toIso8601String(),
                    'token_expired'      => $m->tokenIsExpired(),
                    'rushly_merchant_id' => $m->rushly_merchant_id,
                    'rushly_merchant'    => $linkedMerchant
                        ? ['id' => $linkedMerchant->id, 'name' => $linkedMerchant->business_name ?? $linkedMerchant->title]
                        : null,
                    'belongs_to_company' => $belongsToCompany,
                    'auto_create_parcel' => $settings ? (bool) $settings->auto_create_parcel : false,
                    'orders_count'       => SallaOrder::where('salla_merchant_id', $m->id)->count(),
                    'shipments_count'    => SallaShipment::whereIn('salla_order_id',
                        SallaOrder::where('salla_merchant_id', $m->id)->select('id')
                    )->count(),
                    'urls' => [
                        'edit' => route('salla.stores.edit', $m->id),
                    ],
                ];
            })->values();

        return Inertia::render('Admin/Integrations/Salla/Stores/Index', [
            'stores'      => $stores,
            'permissions' => [
                'update' => hasPermission('integrations_update'),
            ],
            'urls' => [
                'integrations' => route('integrations.index'),
            ],
            't' => [
                'title'              => 'Salla stores',
                'breadcrumb_settings'=> __('menus.settings') ?: 'Settings',
                'breadcrumb_integrations' => 'Integrations',
                'help'               => 'Each row is one Salla store that installed the bridge app. Link the store to a Rushly merchant to enable parcel creation. Stores without a Rushly merchant link are received but no parcels are created.',
                'empty'              => 'No Salla stores have installed the bridge yet.',
                'salla_merchant_id'  => 'Salla store ID',
                'store'              => 'Store',
                'rushly_merchant'    => 'Linked merchant',
                'auto_create'        => 'Auto-create parcel',
                'orders'             => 'Orders',
                'shipments'          => 'Shipments',
                'status'             => 'Status',
                'installed'          => 'Installed',
                'uninstalled'        => 'Uninstalled',
                'token_expired'      => 'Token expired',
                'not_linked'         => 'Not linked',
                'other_company'      => 'Linked to another company',
                'configure'          => 'Configure',
            ],
        ]);
    }

    public function edit(int $id)
    {
        $store = SallaMerchant::with('settings')->findOrFail($id);
        $settings = $store->settings ?: SallaSettings::firstOrCreate(['salla_merchant_id' => $store->id]);

        $companyId = settings()->id ?? null;

        $merchants = Merchant::where('company_id', $companyId)
            ->orderBy('business_name')
            ->get(['id', 'business_name']);

        $shops = $store->rushly_merchant_id
            ? MerchantShops::where('merchant_id', $store->rushly_merchant_id)
                ->orderBy('name')->get(['id', 'name'])
            : collect();

        $cities     = City::orderBy('name')->get(['id', 'name']);
        $categories = Deliverycategory::orderBy('title')->get(['id', 'title']);

        return Inertia::render('Admin/Integrations/Salla/Stores/Edit', [
            'store' => [
                'id'                => $store->id,
                'salla_merchant_id' => (string) $store->salla_merchant_id,
                'store_name'        => $store->store_name,
                'store_domain'      => $store->store_domain,
                'owner_email'       => $store->owner_email,
                'installed'         => (bool) $store->installed,
                'rushly_merchant_id'=> $store->rushly_merchant_id ? (string) $store->rushly_merchant_id : '',
                'rushly_shop_id'    => $store->rushly_shop_id ? (string) $store->rushly_shop_id : '',
            ],
            'settings' => [
                'auto_create_parcel'       => (bool) $settings->auto_create_parcel,
                'trigger_status'           => $settings->trigger_status ?: 'payment_pending',
                'default_rushly_shop_id'   => $settings->default_rushly_shop_id ? (string) $settings->default_rushly_shop_id : '',
                'default_city_id'          => $settings->default_city_id ? (string) $settings->default_city_id : '',
                'default_category_id'      => $settings->default_category_id ? (string) $settings->default_category_id : '',
                'default_delivery_type_id' => $settings->default_delivery_type_id ? (string) $settings->default_delivery_type_id : '',
                'support_email'            => $settings->support_email ?: '',
            ],
            'lookups' => [
                'merchants'      => $merchants->map(fn ($m) => [
                    'value' => (string) $m->id,
                    'label' => $m->business_name ?: $m->title ?: ('Merchant #' . $m->id),
                ])->values(),
                'shops'          => $shops->map(fn ($s) => ['value' => (string) $s->id, 'label' => $s->name])->values(),
                'cities'         => $cities->map(fn ($c) => ['value' => (string) $c->id, 'label' => $c->name])->values(),
                'categories'     => $categories->map(fn ($c) => ['value' => (string) $c->id, 'label' => $c->title])->values(),
                'delivery_types' => collect(self::DELIVERY_TYPES)->map(fn ($k, $id) => [
                    'value' => (string) $id,
                    'label' => str_replace('_', ' ', $k),
                ])->values(),
            ],
            'urls' => [
                'submit' => route('salla.stores.update', $store->id),
                'cancel' => route('salla.stores.index'),
            ],
            't' => [
                'title'                  => $store->store_name ?: ('Salla store ' . $store->salla_merchant_id),
                'breadcrumb_settings'    => __('menus.settings') ?: 'Settings',
                'breadcrumb_integrations'=> 'Integrations',
                'breadcrumb_stores'      => 'Salla stores',
                'link_section'           => 'Link to Rushly merchant',
                'link_help'              => 'Choose which Rushly merchant this Salla store maps to. Without a link the bridge ingests orders but does not create parcels.',
                'rushly_merchant'        => 'Rushly merchant',
                'rushly_shop_id'         => 'Default Rushly shop (per-merchant fallback)',
                'rushly_shop_hint'       => 'Used only if the per-store default below is empty.',
                'flow_section'           => 'Parcel creation defaults',
                'flow_help'              => 'Used to fill required fields when an order arrives without them.',
                'default_rushly_shop'    => 'Default Rushly shop',
                'default_city'           => 'Default city',
                'default_category'       => 'Default delivery category',
                'default_delivery_type'  => 'Default delivery type',
                'auto_create_parcel'     => 'Auto-create parcels when an order webhook arrives',
                'trigger_status'         => 'Trigger status (Salla)',
                'support_email'          => 'Support email',
                'none_option'            => '— none —',
                'save'                   => __('levels.save') ?: 'Save',
                'cancel'                 => __('levels.cancel') ?: 'Cancel',
                'back'                   => __('levels.back') ?: 'Back',
            ],
        ]);
    }

    public function update(Request $request, int $id)
    {
        $store = SallaMerchant::findOrFail($id);

        $data = $request->validate([
            'rushly_merchant_id'        => ['nullable', 'integer', 'exists:merchants,id'],
            'rushly_shop_id'            => ['nullable', 'integer', 'exists:merchant_shops,id'],
            'auto_create_parcel'        => ['nullable', 'boolean'],
            'trigger_status'            => ['nullable', 'string', 'max:64'],
            'default_rushly_shop_id'    => ['nullable', 'integer', 'exists:merchant_shops,id'],
            'default_city_id'           => ['nullable', 'integer', 'exists:cities,id'],
            'default_category_id'       => ['nullable', 'integer', 'exists:deliverycategories,id'],
            'default_delivery_type_id'  => ['nullable', 'integer', 'in:1,2,3,4'],
            'support_email'             => ['nullable', 'email', 'max:191'],
        ]);

        $store->fill([
            'rushly_merchant_id' => $data['rushly_merchant_id'] ?? null,
            'rushly_shop_id'     => $data['rushly_shop_id'] ?? null,
        ])->save();

        $settings = SallaSettings::firstOrNew(['salla_merchant_id' => $store->id]);
        $settings->fill([
            'auto_create_parcel'       => (bool) ($data['auto_create_parcel'] ?? false),
            'trigger_status'           => $data['trigger_status'] ?? 'payment_pending',
            'default_rushly_shop_id'   => $data['default_rushly_shop_id'] ?? null,
            'default_city_id'          => $data['default_city_id'] ?? null,
            'default_category_id'      => $data['default_category_id'] ?? null,
            'default_delivery_type_id' => $data['default_delivery_type_id'] ?? null,
            'support_email'            => $data['support_email'] ?? null,
        ])->save();

        Toastr::success('Salla store updated.', __('message.success') ?: 'Success');

        return redirect()->route('salla.stores.index');
    }
}
