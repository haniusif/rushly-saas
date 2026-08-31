<?php

namespace App\Http\Controllers\Backend;

use App\Enums\ParcelStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Hub\StoreHubRequest;
use App\Http\Requests\Hub\UpdateHubRequest;
use App\Models\Backend\Hub;
use App\Models\Backend\Parcel;
use App\Repositories\Hub\HubInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
class HubController extends Controller
{
    protected $repo;
    public function __construct(HubInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request)
    {
        return $this->renderHubIndex($this->repo->all(), $request);
    }

    public function filter(Request $request)
    {
        return $this->renderHubIndex($this->repo->filter($request), $request);
    }

    private function renderHubIndex($paginator, Request $request)
    {
        $rows = collect($paginator->items())->map(fn ($h) => [
            'id'         => $h->id,
            'name'       => $h->name,
            'phone'      => $h->phone,
            'address'    => $h->address,
            'status'     => (int) $h->status,
            'urls'       => [
                'view'       => \Route::has('hub.view') ? route('hub.view', $h->id) : null,
                'edit'       => route('hubs.edit', $h->id),
                'incharge'   => \Route::has('hub-incharge.index') ? route('hub-incharge.index', $h->id) : null,
                'delete'     => route('hub.delete', $h->id),
            ],
        ])->values();

        return Inertia::render('Admin/Hub/Index', [
            'rows'        => $rows,
            'pagination'  => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
                'total'        => $paginator->total(),
                'prev_url'     => $paginator->previousPageUrl(),
                'next_url'     => $paginator->nextPageUrl(),
            ],
            'filters' => [
                'name'  => $request->input('name', ''),
                'phone' => $request->input('phone', ''),
            ],
            'permissions' => [
                'create'        => hasPermission('hub_create'),
                'update'        => hasPermission('hub_update'),
                'delete'        => hasPermission('hub_delete'),
                'view'          => hasPermission('hub_view'),
                'incharge_read' => hasPermission('hub_incharge_read'),
            ],
            'urls' => [
                'index'  => route('hubs.index'),
                'filter' => route('hubs.filter'),
                'create' => route('hubs.create'),
            ],
            't' => [
                'title'           => __('hub.title') ?: 'Hubs',
                'list'            => __('levels.list') ?: 'List',
                'add'             => __('levels.add') ?: 'Add',
                'edit'            => __('levels.edit') ?: 'Edit',
                'view'            => __('levels.view') ?: 'View',
                'delete'          => __('levels.delete') ?: 'Delete',
                'actions'         => __('levels.actions') ?: 'Actions',
                'filter'          => __('levels.filter') ?: 'Filter',
                'clear'           => __('levels.clear') ?: 'Clear',
                'name'            => __('levels.name') ?: 'Name',
                'phone'           => __('levels.phone') ?: 'Phone',
                'address'         => __('levels.address') ?: 'Address',
                'status'          => __('levels.status') ?: 'Status',
                'status_active'   => __('status.1') ?: 'Active',
                'status_inactive' => __('status.0') ?: 'Inactive',
                'incharge'        => __('hubs.hub_incharge') ?: 'Incharge',
                'delete_confirm'  => __('delete.hub') ?: 'Delete this hub?',
                'no_rows'         => __('levels.no_data_found') ?: 'No hubs found',
                'showing_results' => 'Showing :from – :to of :total',
            ],
        ]);
    }

    /**
     * Cities for the hub form, active first and alphabetical.
     *
     * The hub's city is the pickup origin a 3PL integration reads, so it is a
     * real selection rather than free text — matching a carrier's own city
     * list by typed name is what made the origin unreliable before.
     */
    private static function cityOptions(): array
    {
        return \App\Models\Backend\City::query()
            ->orderByRaw('is_active DESC')
            ->orderBy('en_name')
            ->get(['id', 'name', 'en_name', 'city_code'])
            ->map(fn ($c) => [
                'id'    => $c->id,
                'label' => trim(($c->en_name ?: $c->name) . ($c->city_code ? " ({$c->city_code})" : '')),
            ])
            ->values()
            ->all();
    }

    public function create()
    {
        return Inertia::render('Admin/Hub/Create', [
            'urls' => [
                'submit' => route('hubs.store'),
                'cancel' => route('hubs.index'),
            ],
            'lookups' => [
                'statuses' => [
                    ['value' => 1, 'label' => __('status.1') ?: 'Active'],
                    ['value' => 0, 'label' => __('status.0') ?: 'Inactive'],
                ],
                'cities' => self::cityOptions(),
            ],
            'google_maps_key' => googleMapSettingKey(),
            't' => [
                'title'       => 'New hub',
                'title_index' => __('hub.title') ?: 'Hubs',
                'name'        => __('levels.name') ?: 'Name',
                'phone'       => __('levels.phone') ?: 'Phone',
                'address'     => __('levels.address') ?: 'Address',
                'city'        => __('menus.city_single') ?: 'City',
                'city_hint'   => 'Used as the pickup origin when handing shipments to a 3PL carrier.',
                'address_hint'=> 'Start typing — autocomplete from Google Places',
                'lat'         => 'Latitude',
                'long'        => 'Longitude',
                'coord_hint'  => 'Click or drag the marker on the map, or type values directly.',
                'status'      => __('levels.status') ?: 'Status',
                'cancel'      => __('levels.cancel') ?: 'Cancel',
                'save'        => __('levels.save') ?: 'Save',
                'no_map_key'  => 'Google Maps API key is not configured. Set it in Settings → Google Maps.',
            ],
        ]);
    }

    public function store(StoreHubRequest $request)
    {
        if($this->repo->store($request)){
            Toastr::success(__('hub.added_msg'),__('message.success'));
            return redirect()->route('hubs.index');
        }else{
            Toastr::error(__('hub.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    public function edit($id)
    {
        $hub = $this->repo->get($id);
        if (!$hub) {
            return redirect()->route('hubs.index');
        }

        return Inertia::render('Admin/Hub/Create', [
            'mode' => 'edit',
            'hub'  => [
                'id'      => $hub->id,
                'name'    => $hub->name,
                'phone'   => $hub->phone,
                'address' => $hub->address,
                'city_id' => $hub->city_id,
                'lat'     => $hub->lat,
                'long'    => $hub->long,
                'status'  => (int) $hub->status,
            ],
            'urls' => [
                'submit' => route('hubs.update'),
                'cancel' => route('hubs.index'),
            ],
            'lookups' => [
                'statuses' => [
                    ['value' => 1, 'label' => __('status.1') ?: 'Active'],
                    ['value' => 0, 'label' => __('status.0') ?: 'Inactive'],
                ],
                'cities' => self::cityOptions(),
            ],
            'google_maps_key' => googleMapSettingKey(),
            't' => [
                'title'       => __('hub.edit_hub') ?: 'Edit hub',
                'title_index' => __('hub.title') ?: 'Hubs',
                'name'        => __('levels.name') ?: 'Name',
                'phone'       => __('levels.phone') ?: 'Phone',
                'address'     => __('levels.address') ?: 'Address',
                'city'        => __('menus.city_single') ?: 'City',
                'city_hint'   => 'Used as the pickup origin when handing shipments to a 3PL carrier.',
                'address_hint'=> 'Start typing — autocomplete from Google Places',
                'lat'         => 'Latitude',
                'long'        => 'Longitude',
                'coord_hint'  => 'Click or drag the marker on the map, or type values directly.',
                'status'      => __('levels.status') ?: 'Status',
                'cancel'      => __('levels.cancel') ?: 'Cancel',
                'save'        => __('levels.save') ?: 'Save',
                'no_map_key'  => 'Google Maps API key is not configured. Set it in Settings → Google Maps.',
            ],
        ]);
    }

    public function update(UpdateHubRequest $request)
    {
        if($this->repo->update($request->id, $request)){
            Toastr::success(__('hub.update_msg'),__('message.success'));
            return redirect()->route('hubs.index');
        }else{
            Toastr::error(__('hub.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    public function destroy($id)
    {
        $this->repo->delete($id);
        Toastr::success(__('hub.delete_msg'),__('message.success'));
        return back();
    }


    public function quickStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'    => ['required','string','max:191','unique:hubs,name'],
            'phone'   => ['required','string','max:20'],
            'address' => ['required','string','max:191'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok'     => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $hub = new Hub();
            $hub->company_id = settings()->id;
            $hub->name       = $request->input('name');
            $hub->phone      = $request->input('phone');
            $hub->address    = $request->input('address');
            $hub->status     = 1;
            $hub->save();

            return response()->json([
                'ok'  => true,
                'hub' => ['id' => $hub->id, 'name' => $hub->name],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => __('hub.error_msg')], 500);
        }
    }

    public function view(Request $request, $id)
    {
        $hub = $this->repo->get($id);
        if (!$hub) {
            return redirect()->route('hubs.index');
        }

        $base = $this->repo->parcelFilter($request, $id);
        $paginator = (clone $base)->paginate(15);
        $all       = (clone $base)->get();

        $deliveredSum = (clone $base)->where('status', ParcelStatus::DELIVERED)->sum('cash_collection');
        $partialSum   = (clone $base)->where('status', ParcelStatus::PARTIAL_DELIVERED)->sum('cash_collection');
        $inTransit    = (clone $base)->whereNotIn('status', [ParcelStatus::DELIVERED, ParcelStatus::PARTIAL_DELIVERED])->sum('cash_collection');

        // Status breakdown: [statusId => count]
        $statusGroups = $all->groupBy('status')->map(fn ($g, $sid) => [
            'id'    => (int) $sid,
            'label' => \App\Support\ParcelStatusHelper::label((int) $sid),
            'color' => \App\Support\ParcelStatusHelper::color((int) $sid),
            'count' => $g->count(),
        ])->values();

        $rows = collect($paginator->items())->map(fn ($p) => [
            'id'              => $p->id,
            'tracking_id'     => $p->tracking_id,
            'invoice_no'      => $p->invoice_no,
            'customer_name'   => $p->customer_name,
            'customer_phone'  => $p->customer_phone,
            'cash_collection' => (float) ($p->cash_collection ?? 0),
            'status'          => (int) $p->status,
            'status_label'    => \App\Support\ParcelStatusHelper::label((int) $p->status),
            'status_color'    => \App\Support\ParcelStatusHelper::color((int) $p->status),
            'updated_at'      => optional($p->updated_at)->format('Y-m-d H:i'),
            'url'             => route('parcel.details', $p->id),
        ])->values();

        return Inertia::render('Admin/Hub/View', [
            'hub' => [
                'id'        => $hub->id,
                'name'      => $hub->name,
                'phone'     => $hub->phone,
                'address'   => $hub->address,
                'city'      => optional($hub->city)->en_name ?: optional($hub->city)->name,
                'city_code' => optional($hub->city)->city_code,
                'lat'       => $hub->hub_lat ?? $hub->lat ?? null,
                'long'      => $hub->hub_long ?? $hub->long ?? null,
                'status'    => (int) $hub->status,
                'created_at'=> optional($hub->created_at)->format('Y-m-d'),
            ],
            'stats' => [
                'total_parcels'           => $all->count(),
                'total_cash'              => (float) $all->sum('cash_collection'),
                'delivered_cash'          => (float) $deliveredSum,
                'partial_delivered_cash'  => (float) $partialSum,
                'in_transit_cash'         => (float) $inTransit,
                'delivery_charges'        => (float) $all->sum('delivery_charge'),
                'vat_amount'              => (float) $all->sum('vat_amount'),
            ],
            'status_groups' => $statusGroups,
            'rows'          => $rows,
            'pagination'    => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
                'total'        => $paginator->total(),
                'prev_url'     => $paginator->previousPageUrl(),
                'next_url'     => $paginator->nextPageUrl(),
            ],
            'filters'  => ['parcel_date' => $request->input('parcel_date', '')],
            'currency' => settings()->currency,
            'urls' => [
                'index'        => route('hubs.index'),
                'self'         => route('hub.view', $id),
                'edit'         => route('hubs.edit', $id),
                'incharges'    => route('hub-incharge.index', $id),
            ],
            't' => [
                'title'            => __('hub.title') ?: 'Hubs',
                'view'             => __('levels.view') ?: 'View',
                'edit'             => __('levels.edit') ?: 'Edit',
                'incharges'        => __('incharge.title') ?: 'In-charges',
                'overview'         => 'Overview',
                'identity'         => 'Identity',
                'name'             => __('levels.name') ?: 'Name',
                'phone'            => __('levels.phone') ?: 'Phone',
                'address'          => __('levels.address') ?: 'Address',
                'city'             => __('menus.city_single') ?: 'City',
                'status'           => __('levels.status') ?: 'Status',
                'active'           => __('levels.active') ?: 'Active',
                'inactive'         => __('levels.inactive') ?: 'Inactive',
                'coordinates'      => 'Coordinates',
                'total_parcels'    => 'Total parcels',
                'cash_total'       => 'Total cash collection',
                'cash_delivered'   => 'Delivered cash',
                'cash_partial'     => 'Partial delivered cash',
                'cash_in_transit'  => 'In-transit cash',
                'delivery_charges' => 'Delivery charges',
                'vat'              => 'VAT',
                'status_breakdown' => 'Status breakdown',
                'recent_parcels'   => 'Recent parcels',
                'tracking_id'      => __('parcel.tracking_id') ?: 'Tracking ID',
                'customer'         => __('parcel.customer_name') ?: 'Customer',
                'cod'              => __('parcel.cash_collection') ?: 'COD',
                'updated_at'       => __('parcel.updated_on') ?: 'Updated',
                'no_parcels'       => 'No parcels for this hub yet.',
                'date_range'       => __('parcel.parcel_date') ?: 'Date',
                'filter'           => __('levels.filter') ?: 'Filter',
                'clear'            => __('levels.clear') ?: 'Clear',
                'prev'             => __('levels.previous') ?: 'Prev',
                'next'             => __('levels.next') ?: 'Next',
                'showing_results'  => 'Showing :from – :to of :total',
                'back'             => __('levels.list') ?: 'Hubs',
            ],
        ]);
    }

}
