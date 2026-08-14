<?php

namespace App\Http\Controllers\Backend\Fulfillment;

use App\Fulfillment\Models\Fulfillment;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FulfillmentController extends Controller
{
    public function __construct()
    {
        if (! app()->runningInConsole()) {
            abort_unless(config('features.commerce_layer'), 404);
        }
    }

    public function index(Request $request)
    {
        $companyId = (int) (settings()->id ?? 0);

        $q = Fulfillment::query()
            ->with(['order', 'route'])
            ->where('company_id', $companyId)
            ->orderByDesc('id');

        if ($s = $request->query('status'))   $q->where('status', $s);
        if ($str = $request->query('strategy')) $q->where('strategy', $str);

        $rows = $q->limit(200)->get();

        return Inertia::render('Admin/Oms/Fulfillments/Index', [
            'fulfillments' => $rows->map(fn (Fulfillment $f) => [
                'id'                     => $f->id,
                'order_id'               => $f->order_id,
                'order_remote'           => $f->order?->remote_order_number ?? $f->order?->remote_order_id,
                'strategy'               => $f->strategy,
                'status'                 => $f->status,
                'route_id'               => $f->route_id,
                'route_name'             => $f->route?->name,
                'parcel_id'              => $f->parcel_id,
                'wms_fulfillment_id'     => $f->wms_fulfillment_id,
                'shipping_connection_id' => $f->shipping_connection_id,
                'hub_id'                 => $f->hub_id,
                'external_reference'     => $f->external_reference,
                'last_error'             => $f->last_error ? mb_substr($f->last_error, 0, 140) : null,
                'started_at'             => optional($f->started_at)->toIso8601String(),
                'completed_at'           => optional($f->completed_at)->toIso8601String(),
                'failed_at'              => optional($f->failed_at)->toIso8601String(),
                'created_at'             => optional($f->created_at)->toIso8601String(),
            ])->values(),
            'filters' => [
                'status'   => $request->query('status', ''),
                'strategy' => $request->query('strategy', ''),
            ],
            'strategies' => array_keys((array) config('fulfillment.strategies', [])),
            'urls' => [
                'index'  => route('fulfillment.fulfillments.index'),
                'routes' => route('fulfillment.routes.index'),
            ],
            't' => [
                'page_title'              => 'Fulfillments',
                'breadcrumb_settings'     => __('menus.settings') ?: 'Settings',
                'breadcrumb_integrations' => 'Integrations',
                'breadcrumb_commerce'     => 'Commerce',
                'breadcrumb_oms'          => 'OMS',
                'breadcrumb_fulfillments' => 'Fulfillments',
                'help'                    => 'Every routed fulfillment attempt. Status reflects strategy execution. Failures show `last_error`; open the linked order for the full audit trail.',
                'no_rows'                 => 'No fulfillments yet. Once orders arrive and a route matches (or FULFILLMENT_DEFAULT_STRATEGY is set), rows land here.',
            ],
        ]);
    }
}
