<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Backend\City;
use App\Models\Backend\Deliverycategory;
use App\Models\Backend\IntegrationSetting;
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

        return view('backend.integrations.index', compact('integrations', 'parcelCounts'));
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
