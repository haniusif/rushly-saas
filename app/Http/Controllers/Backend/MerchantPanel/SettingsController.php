<?php

namespace App\Http\Controllers\Backend\MerchantPanel;

use App\Http\Controllers\Controller;
use App\Models\Backend\CodCharge;
use App\Models\Backend\Merchant;
use App\Repositories\MerchantDeliveryCharge\MerchantDeliveryChargeInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SettingsController extends Controller
{
    protected $deliveryCrarge;
    public function __construct(MerchantDeliveryChargeInterface $deliveryCrarge)
    {
        $this->deliveryCrarge=$deliveryCrarge;
    }

    public function CODcharges()
    {
        $merchant = Merchant::where('user_id', Auth::user()->id)->first();
        $rows = [];
        $i = 1;
        foreach ((array) ($merchant->cod_charges ?? []) as $key => $charge) {
            $rows[] = [
                'id'       => $i++,
                'location' => __('merchant.' . $key) ?: $key,
                'charge'   => (float) $charge,
            ];
        }

        return Inertia::render('Merchant/Settings/CodCharges', [
            'rows' => $rows,
            't'    => [
                'title'       => __('delivery_charge.cod_charges') ?: 'COD charges',
                'list'        => __('levels.list') ?: 'List',
                'dashboard'   => __('levels.dashboard') ?: 'Dashboard',
                'settings'    => __('menus.settings') ?: 'Settings',
                'id'          => __('levels.id') ?: 'ID',
                'location'    => __('settings.location') ?: 'Location',
                'charge'      => (__('settings.charges') ?: 'Charges') . ' (%)',
                'empty'       => __('levels.no_data_found') ?: 'No COD charges configured.',
            ],
        ]);
    }

    public function deliveryCharges()
    {
        $merchant = Merchant::where('user_id', Auth::user()->id)->first();
        $paginator = $this->deliveryCrarge->all($merchant->id);
        $currency  = settings()->currency;

        $rows = collect($paginator->items())->map(function ($d) {
            $dc = $d->deliveryCharge;
            return [
                'id'            => $d->id,
                'category'      => optional(optional($dc)->category)->title,
                'weight'        => (float) (optional($dc)->weight ?? 0),
                'same_day'      => (float) ($d->same_day ?? 0),
                'next_day'      => (float) ($d->next_day ?? 0),
                'sub_city'      => (float) ($d->sub_city ?? 0),
                'outside_city'  => (float) ($d->outside_city ?? 0),
                'status_label'  => strip_tags((string) $d->my_status),
                'status_active' => str_contains((string) $d->my_status, 'success') || str_contains((string) $d->my_status, 'active'),
            ];
        })->values();

        return Inertia::render('Merchant/Settings/DeliveryCharges', [
            'rows'       => $rows,
            'currency'   => $currency,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
                'links'        => $paginator->linkCollection()->map(fn ($l) => [
                    'url'    => $l['url'],
                    'label'  => $l['label'],
                    'active' => (bool) $l['active'],
                ])->values(),
            ],
            't' => [
                'title'        => __('delivery_charge.title') ?: 'Delivery charges',
                'list'         => __('levels.list') ?: 'List',
                'dashboard'    => __('levels.dashboard') ?: 'Dashboard',
                'settings'     => __('menus.settings') ?: 'Settings',
                'id'           => __('levels.id') ?: 'ID',
                'category'     => __('levels.category') ?: 'Category',
                'weight'       => __('levels.weight') ?: 'Weight',
                'same_day'     => __('levels.same_day') ?: 'Same day',
                'next_day'     => __('levels.next_day') ?: 'Next day',
                'sub_city'     => __('levels.sub_city') ?: 'Sub city',
                'outside_city' => __('levels.outside_city') ?: 'Outside city',
                'status'       => __('levels.status') ?: 'Status',
                'empty'        => __('levels.no_data_found') ?: 'No delivery charges configured.',
            ],
        ]);
    }
}
