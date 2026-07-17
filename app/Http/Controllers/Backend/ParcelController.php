<?php

namespace App\Http\Controllers\Backend;

use App\Enums\ParcelStatus;
use App\Enums\Status;
use App\Enums\UserType;
use App\Exports\ParcelSampleExport;
use App\Exports\ShipmentExport;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use App\Imports\ParcelImport;
use App\Models\Backend\DeliveryCharge;
use App\Models\Backend\Hub;
use App\Models\Backend\Merchant;
use App\Models\Backend\Area;
use Illuminate\Support\Str;
use App\Models\Backend\MerchantDeliveryCharge;
use App\Models\MerchantShops;
use App\Repositories\Merchant\MerchantInterface;
use App\Repositories\MerchantPanel\Shops\ShopsInterface;
use Illuminate\Http\Request;
use App\Http\Requests\Parcel\StoreRequest;
use App\Http\Requests\Parcel\UpdateRequest;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Parcel;
use App\Models\Backend\ParcelEvent;
use App\Models\User;
use App\Repositories\DeliveryMan\DeliveryManInterface;
use App\Repositories\Hub\HubInterface;
use App\Repositories\Parcel\ParcelInterface;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Brian2694\Toastr\Facades\Toastr;
use Maatwebsite\Excel\Facades\Excel;
use \Mpdf\Mpdf;
use App\Services\DeliveryPandaService;
use App\Services\ZajelService;
use App\Services\AramexService;
use App\Services\JetService;
use App\Services\LogestechsService;
use Carbon\Carbon;
use App\Models\Backend\Parcels_3pl;
use App\Models\Backend\RejectedParcel;


use App\Exports\MerchantParcelExport;
use App\Http\Resources\MerchantParcelExportResource;

class ParcelController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    protected $merchant;
    protected $repo;
    protected $shop;
   protected $deliveryPanda;
   protected ZajelService $zajel;
   protected AramexService $aramex;
   protected JetService $jet;
   protected LogestechsService $logestechs;

    public function __construct(
        ParcelInterface $repo,
        MerchantInterface $merchant,
        ShopsInterface $shop,
        DeliveryManInterface $deliveryman,
        HubInterface $hub,
        DeliveryPandaService $deliveryPanda,
        ZajelService $zajel,
        AramexService $aramex,
        JetService $jet,
        LogestechsService $logestechs
        )
    {
        $this->merchant     = $merchant;
        $this->repo         = $repo;
        $this->shop         = $shop;
        $this->deliveryman  = $deliveryman;
        $this->hub          = $hub;
        $this->deliveryPanda = $deliveryPanda;
        $this->zajel         = $zajel;
        $this->aramex        = $aramex;
        $this->jet           = $jet;
        $this->logestechs    = $logestechs;
    }
    public function index(Request $request)
    {
        if ($request->has('per_page')) {
            session(['per_page' => $request->per_page]);
        }
        $paginate = session('per_page', 10);

        $paginator = $this->repo->all($paginate);
        return $this->renderParcelIndex($paginator, $request, $paginate);
    }

    public function filter(Request $request)
    {
        if ($request->has('per_page')) {
            session(['per_page' => $request->per_page]);
        }
        $paginate = session('per_page', 10);

        $paginator = $this->repo->filter($request, $paginate);
        if (!$paginator) {
            return redirect()->back();
        }
        return $this->renderParcelIndex($paginator, $request, $paginate);
    }

    /**
     * Flatten the paginator into a row shape the React table renders, and
     * pass lookups (statuses, merchants, deliverymen) + filters back so the
     * client can rehydrate the filter bar.
     */
    private function renderParcelIndex($paginator, Request $request, $paginate)
    {
        $deliverymans = $this->deliveryman->all();
        $hubs         = $this->hub->all();
        $merchants    = \App\Models\Backend\Merchant::companywise()
            ->where('status', 1)
            ->orderBy('business_name')
            ->get(['id', 'business_name']);

        $rows = collect($paginator->items())->map(function ($p) {
            $statusId = (int) $p->status;
            $invoice  = $p->admin_parcel_invoice ?? null;
            $assignedDeliveryman = optional(optional($p->lastParcelEvent)->deliveryMan->user ?? null)->name;
            return [
                'id'                    => $p->id,
                'tracking_id'           => $p->tracking_id,
                'code'                  => $p->code,
                'customer_name'         => $p->customer_name,
                'customer_phone'        => $p->customer_phone,
                'customer_address'      => $p->customer_address,
                'city'                  => optional($p->city)->en_name ?? optional($p->city)->name,
                'area'                  => optional($p->area)->en_name ?? optional($p->area)->name,
                'merchant_name'         => optional($p->merchant)->business_name,
                'merchant_mobile'       => optional(optional($p->merchant)->user)->mobile,
                'merchant_address'      => optional($p->merchant)->address,
                'cash_collection'       => (float) ($p->cash_collection ?? 0),
                'total_delivery_amount' => (float) ($p->total_delivery_amount ?? 0),
                'vat_amount'            => (float) ($p->vat_amount ?? 0),
                'current_payable'       => (float) ($p->current_payable ?? 0),
                'status'                => $statusId,
                'status_label'          => \App\Support\ParcelStatusHelper::label($statusId),
                'status_color'          => \App\Support\ParcelStatusHelper::color($statusId),
                'partial_delivered'     => (bool) ($p->partial_delivered ?? false),
                'partial_delivered_label' => \App\Support\ParcelStatusHelper::label(\App\Enums\ParcelStatus::PARTIAL_DELIVERED),
                'priority'              => (int) ($p->priority_type_id ?? 2),
                'attempts'              => (int) ($p->number_of_attempts ?? 0),
                'invoice'               => $invoice ? [
                    'id'         => $invoice->invoice_id,
                    'status'     => $invoice->status,
                    'status_label' => __('invoice.' . $invoice->status),
                    'paid_at'    => $invoice->status == \App\Enums\InvoiceStatus::PAID
                        ? optional($invoice->updated_at)->format('Y-m-d')
                        : null,
                ] : null,
                'courier_name'          => $p->lastParcel3pl
                    ? (optional($p->lastParcel3pl)->company_name ?? optional($p->lastParcel3pl)->parcel_3pl_name)
                    : null,
                'assigned_deliveryman'  => $assignedDeliveryman,
                'created_at'            => optional($p->created_at)->toDateString(),
                'updated_at'            => optional($p->updated_at)->format('Y-m-d H:i'),
                'allowed_transitions'   => $this->allowedTransitions($statusId),
                'urls' => [
                    'view'           => route('parcel.details', $p->id),
                    'logs'           => route('parcel.logs', $p->id),
                    'clone'          => route('parcel.clone', $p->id),
                    'print'          => route('parcel.print', $p->id),
                    'print_label'    => route('parcel.print-label', $p->id),
                    'edit'           => route('parcel.edit', $p->id),
                    'delete'         => route('parcel.delete', $p->id),
                    'delivered_info' => $statusId === \App\Enums\ParcelStatus::DELIVERED ? route('parcel.deliveredInfo', $p->id) : null,
                ],
            ];
        })->values();

        $statuses = collect((new \ReflectionClass(\App\Enums\ParcelStatus::class))->getConstants())
            ->map(fn ($v, $k) => [
                'value' => (int) $v,
                'label' => \App\Support\ParcelStatusHelper::label((int) $v),
            ])
            ->values();

        // Per-status counts for the KPI chip strip above the table. Grouped
        // in one query so this is a single indexed count-by-status instead of
        // eleven separate calls. All counts are tenant-scoped via the global
        // Parcel scope.
        $statusCounts = \App\Models\Backend\Parcel::query()
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');
        $groupTotal = fn (array $codes) => (int) collect($codes)->sum(fn ($s) => (int) ($statusCounts[$s] ?? 0));
        $kpi_counts = [
            'total'     => (int) \App\Models\Backend\Parcel::query()->count(),
            'pending'   => (int) ($statusCounts[\App\Enums\ParcelStatus::PENDING] ?? 0),
            'assigned'  => (int) ($statusCounts[\App\Enums\ParcelStatus::PICKUP_ASSIGN] ?? 0),
            'picked_up' => (int) ($statusCounts[\App\Enums\ParcelStatus::RECEIVED_WAREHOUSE] ?? 0),
            'ofd'       => (int) ($statusCounts[\App\Enums\ParcelStatus::DELIVERY_MAN_ASSIGN] ?? 0),
            'delivered' => (int) ($statusCounts[\App\Enums\ParcelStatus::DELIVERED] ?? 0),
            'returned'  => (int) ($statusCounts[\App\Enums\ParcelStatus::RETURN_RECEIVED_BY_MERCHANT] ?? 0),
            'cancelled' => $groupTotal([
                \App\Enums\ParcelStatus::CANCELLED,
                \App\Enums\ParcelStatus::PICKUP_ASSIGN_CANCEL,
                \App\Enums\ParcelStatus::RECEIVED_WAREHOUSE_CANCEL,
                \App\Enums\ParcelStatus::DELIVERY_MAN_ASSIGN_CANCEL,
                \App\Enums\ParcelStatus::DELIVERY_RE_SCHEDULE_CANCEL,
                \App\Enums\ParcelStatus::DELIVERED_CANCEL,
            ]),
            'failed'    => (int) ($statusCounts[\App\Enums\ParcelStatus::DELIVERY_RE_SCHEDULE] ?? 0),
            'ndr'       => (int) ($statusCounts[\App\Enums\ParcelStatus::NDR_CREATED] ?? 0),
        ];

        return Inertia::render('Admin/Parcel/Index', [
            'rows'        => $rows,
            'kpi_counts'  => $kpi_counts,
            'pagination'  => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
                'total'        => $paginator->total(),
                'prev_url'     => $paginator->previousPageUrl(),
                'next_url'     => $paginator->nextPageUrl(),
                'per_page'     => $paginate,
            ],
            'filters' => [
                'parcel_date'           => $request->input('parcel_date', ''),
                'parcel_status'         => $request->input('parcel_status', ''),
                'parcel_merchant_id'    => $request->input('parcel_merchant_id', ''),
                'parcel_deliveryman_id' => $request->input('parcel_deliveryman_id', ''),
                'parcel_pickupman_id'   => $request->input('parcel_pickupman_id', ''),
                'invoice_id'            => $request->input('invoice_id', ''),
                'has_3pl'               => $request->input('has_3pl', ''),
                'search'                => $request->input('search', ''),
            ],
            'lookups' => [
                'statuses'     => $statuses,
                'merchants'    => $merchants->map(fn ($m) => [
                    'id'   => $m->id,
                    'name' => $m->business_name,
                ])->values(),
                'deliverymen'  => $deliverymans->map(fn ($d) => [
                    'id'   => $d->id,
                    'name' => optional($d->user)->name,
                ])->filter(fn ($r) => $r['name'])->values(),
                'hubs'         => $hubs->map(fn ($h) => [
                    'id'   => $h->id,
                    'name' => $h->name,
                ])->values(),
            ],
            'permissions' => [
                'create'         => hasPermission('parcel_create'),
                'update'         => hasPermission('parcel_update'),
                'delete'         => hasPermission('parcel_delete'),
                'status_update'  => hasPermission('parcel_status_update'),
                'finance_update' => hasPermission('parcel_finance_update'),
            ],
            'currency' => settings()->currency,
            'urls'     => [
                'index'                    => route('parcel.index'),
                'filter'                   => route('parcel.filter'),
                'create'                   => route('parcel.create'),
                'specific_search'          => route('parcel.specific.search'),
                'multiple_print_label'     => route('parcel.multiple.print-label'),
                'parcel_map'               => route('parcel.parcelDeliveryMan'),
                'export'                   => route('parcel.parcel-export'),
                'import'                   => route('parcel.parcel-import'),
                'priority_status'          => route('parcel.priority.status'),
                'bulk_pickup_assign'       => route('parcel.assign-pickup-bulk'),
                'bulk_transfer_to_hub'     => route('parcel.transfer-to-hub-multiple-parcel'),
                'bulk_deliveryman_assign'  => route('parcel.delivery-man-assign-multiple-parcel'),
                'tracking_json'            => route('parcel.tracking_json', ['id' => 0]),
                'status'                   => [
                    'pickup_assign'                => route('parcel.pickup.man-assigned'),
                    'pickup_assign_cancel'         => route('parcel.pickup.man-assigned-cancel'),
                    'pickup_re_schedule'           => route('parcel.pickup.re.schedule'),
                    'pickup_re_schedule_cancel'    => route('parcel.pickup.re-schedule-cancel'),
                    'received_warehouse'           => route('parcel.received.warehouse'),
                    'received_warehouse_cancel'    => route('parcel.received-warehouse-cancel'),
                    'transfer_to_hub'              => route('parcel.transfer-to-hub'),
                    'transfer_to_hub_cancel'       => route('parcel.transfer-to-hub-cancel'),
                    'received_by_hub'              => route('parcel.received-by.hub'),
                    'received_by_hub_cancel'       => route('parcel.received-by-hub-cancel'),
                    'delivery_man_assign'          => route('parcel.delivery-man-assign'),
                    'delivery_man_assign_cancel'   => route('parcel.delivery-man-assign-cancel'),
                    'delivery_re_schedule'         => route('parcel.delivery.reschedule'),
                    'delivery_re_schedule_cancel'  => route('parcel.delivery-re-schedule-cancel'),
                    'delivered'                    => route('parcel.delivered'),
                    'partial_delivered'            => route('parcel.partial-delivered'),
                    'return_to_courier'            => route('parcel.return-to-qourier'),
                    'return_to_courier_cancel'     => route('parcel.return-to-courier-cancel'),
                ],
            ],
            't' => $this->parcelIndexLabels(),
        ]);
    }

    /**
     * Map current status -> allowed next statuses for the React status dropdown.
     * Mirrors the rules in parcelStatus() global helper (resources/views uses
     * its HTML output; we return structured data so React can render it).
     */
    private function allowedTransitions(int $statusId): array
    {
        $PS = \App\Enums\ParcelStatus::class;
        $allowed = match (true) {
            $statusId === $PS::PENDING                  => [$PS::PICKUP_ASSIGN],
            $statusId === $PS::PICKUP_ASSIGN            => [$PS::PICKUP_ASSIGN_CANCEL, $PS::PICKUP_RE_SCHEDULE, $PS::RECEIVED_WAREHOUSE],
            $statusId === $PS::PICKUP_RE_SCHEDULE       => [$PS::PICKUP_RE_SCHEDULE_CANCEL, $PS::PICKUP_RE_SCHEDULE, $PS::RECEIVED_WAREHOUSE],
            $statusId === $PS::RECEIVED_WAREHOUSE       => [$PS::RECEIVED_WAREHOUSE_CANCEL, $PS::TRANSFER_TO_HUB, $PS::DELIVERY_MAN_ASSIGN],
            $statusId === $PS::RECEIVED_BY_HUB          => [$PS::RECEIVED_BY_HUB_CANCEL, $PS::TRANSFER_TO_HUB, $PS::DELIVERY_MAN_ASSIGN],
            $statusId === $PS::TRANSFER_TO_HUB          => [$PS::TRANSFER_TO_HUB_CANCEL, $PS::RECEIVED_BY_HUB],
            $statusId === $PS::DELIVERY_MAN_ASSIGN      => [$PS::DELIVERY_MAN_ASSIGN_CANCEL, $PS::DELIVERY_RE_SCHEDULE, $PS::RETURN_TO_COURIER, $PS::DELIVERED, $PS::PARTIAL_DELIVERED],
            $statusId === $PS::DELIVERY_RE_SCHEDULE     => [$PS::DELIVERY_RE_SCHEDULE_CANCEL, $PS::DELIVERY_RE_SCHEDULE, $PS::RETURN_TO_COURIER, $PS::DELIVERED, $PS::PARTIAL_DELIVERED],
            $statusId === $PS::RETURN_TO_COURIER        => [$PS::RETURN_TO_COURIER_CANCEL],
            default                                     => [],
        };
        return array_map(fn ($s) => [
            'value' => $s,
            'label' => \App\Support\ParcelStatusHelper::label($s),
            'color' => \App\Support\ParcelStatusHelper::color($s),
        ], $allowed);
    }

    private function parcelIndexLabels(): array
    {
        return [
            'title'             => __('parcel.title') ?: 'Parcels',
            'list'              => __('levels.list') ?: 'List',
            'add'               => __('levels.add') ?: 'Add',
            'edit'              => __('levels.edit') ?: 'Edit',
            'view'              => __('levels.view') ?: 'View',
            'delete'            => __('levels.delete') ?: 'Delete',
            'actions'           => __('levels.actions') ?: 'Actions',
            'filter'            => __('levels.filter') ?: 'Filter',
            'clear'             => __('levels.clear') ?: 'Clear',
            'tracking_id'       => __('parcel.tracking_id') ?: 'Tracking ID',
            'awb'               => 'AWB',
            'recipient_info'    => __('parcel.recipient_info') ?: 'Recipient',
            'merchant'          => __('parcel.merchant') ?: 'Merchant',
            'amount'            => __('parcel.amount') ?: 'Amount',
            'priority'          => __('parcel.priority') ?: 'Priority',
            'status'            => __('parcel.status') ?: 'Status',
            'courier_name'      => 'Courier / 3PL',
            'logs'              => __('levels.parcel_logs') ?: 'Logs',
            'clone'             => __('levels.clone') ?: 'Clone',
            'print'             => __('levels.print') ?: 'Print',
            'print_label'       => __('levels.print_label') ?: 'Print label',
            'delete_confirm'    => 'Delete this parcel?',
            'date_label'        => __('parcel.parcel_date') ?: 'Date',
            'status_label'      => __('parcel.status') ?: 'Status',
            'merchant_label'    => __('parcel.merchant') ?: 'Merchant',
            'deliveryman_label' => 'Courier',
            'invoice_id'        => 'Invoice / Tracking',
            'created_at'        => __('levels.created_at') ?: 'Created',
            'showing_results'   => 'Showing :from – :to of :total',
            'no_rows'           => __('levels.no_data_found') ?: 'No parcels found',
            'all'               => __('levels.all') ?: 'All',
            'per_page'          => 'Per page',
            'pickup_label'      => __('parcel.pickup_man') ?: 'Pickup courier',
            'three_pl'          => '3PL',
            'panda'             => 'Panda',
            'cod'               => 'COD',
            'total_charge'      => 'Total Charge Amount',
            'vat'               => 'VAT',
            'current_payable'   => 'Current Payable',
            'updated_on'        => __('parcel.updated_on') ?: 'Updated',
            'invoice'           => 'Invoice',
            'paid_at'           => 'Paid',
            'attempts'          => 'Attempts',
            'pod'               => 'POD',
            'export_label'      => 'Export :TOTAL Shipments',
            'import_label'      => __('parcel.import_parcel') ?: 'Import parcels',
            'map_label'         => __('parcel.map') ?: 'Map',
            'specific_search'   => __('levels.search') ?: 'Search',
            'status_update'     => __('parcel.status_update') ?: 'Status update',
            'change_status'     => 'Change status',
            'bulk_actions'      => __('levels.select_bulk_type') ?: 'Bulk action',
            'bulk_pickup'       => __('levels.assign_pickup') ?: 'Assign pickup',
            'bulk_hub_transfer' => __('levels.hub_transfer') ?: 'Transfer to hub',
            'bulk_hub_received' => __('levels.received_by_hub') ?: 'Received by hub',
            'bulk_dman_assign'  => __('levels.delivery_man_assign') ?: 'Delivery courier assign',
            'bulk_return_merch' => __('levels.assign_return_merchant') ?: 'Assign return to merchant',
            'apply'             => 'Apply',
            'delete_confirm'    => 'Delete this parcel?',
            'bulk_open_legacy'  => 'Open in legacy view to complete this bulk action',
            'confirm'           => __('levels.confirm') ?: 'Confirm',
            'cancel'            => __('levels.cancel') ?: 'Cancel',
            'submit'            => __('levels.submit') ?: 'Submit',
            'select_deliveryman'=> __('parcel.select_deliveryman') ?: 'Select courier',
            'select_hub'        => __('parcel.select_hub') ?: 'Select hub',
            'date'              => __('levels.date') ?: 'Date',
            'cash_collection'   => __('parcel.cash_collection') ?: 'Cash collection',
            'status_change_to'  => 'Change status to',
            'confirm_change'    => 'Confirm this status change',
            'required'          => __('parcel.required') ?: 'Required',
            'updating'          => __('levels.updating') ?: 'Updating…',
            'next'              => __('levels.next') ?: 'Next',
            'prev'              => __('levels.previous') ?: 'Prev',
            'awb_invoice'       => 'AWB / Invoice',
            'hub'               => __('levels.hub') ?: 'Hub',
            'bulk_select_first' => 'Select at least one parcel first.',
            'bulk_pick_courier' => 'Pick a courier.',
            'bulk_pick_hub'     => 'Pick a hub.',
            'bulk_pick_date'    => 'Pick courier + date.',
        ];
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function create()
    {
        $merchants          = \App\Models\Backend\Merchant::companywise()->where('status', 1)
            ->with('user')->orderBy('business_name')->get();
        $deliveryCategories = $this->repo->deliveryCategories();
        $packagings         = $this->repo->packaging();
        $deliveryTypes      = $this->repo->deliveryTypes();
        $cities             = $this->repo->cities()->load('areas');

        $merchantData = collect($merchants)->map(fn ($m) => [
            'id'            => $m->id,
            'name'          => $m->business_name,
            'vat'           => (float) ($m->vat ?? 0),
            'cod_charges'   => [
                'inside_city'  => (float) (data_get($m, 'cod_charges.inside_city') ?? 0),
                'sub_city'     => (float) (data_get($m, 'cod_charges.sub_city') ?? 0),
                'outside_city' => (float) (data_get($m, 'cod_charges.outside_city') ?? 0),
            ],
            'pickup_phone'   => optional($m->user)->mobile,
            'pickup_address' => $m->address,
        ])->values();

        $cityData = collect($cities)->map(fn ($c) => [
            'id'    => $c->id,
            'name'  => $c->en_name ?: $c->name,
            'areas' => collect($c->areas ?? [])->map(fn ($a) => [
                'id'   => $a->id,
                'name' => $a->en_name ?: $a->name,
            ])->values(),
        ])->values();

        return Inertia::render('Admin/Parcel/Create', [
            'merchants'        => $merchantData,
            'cities'           => $cityData,
            'categories'       => collect($deliveryCategories)->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
            ])->values(),
            'packagings'       => collect($packagings)->map(fn ($p) => [
                'id'    => $p->id,
                'name'  => $p->name,
                'price' => (float) $p->price,
            ])->values(),
            'delivery_types'   => collect($deliveryTypes)->map(fn ($d) => [
                'id'   => $d->id,
                'name' => $d->name ?? $d->key ?? $d->id,
            ])->values(),
            'settings' => [
                'currency'              => settings()->currency,
                'vat_tax'               => (float) (settings()->vat ?? 0),
                'fragile_liquid_charge' => (float) (function_exists('SettingHelper') ? SettingHelper('fragile_liquid_charge') : 0),
                // Consumed by the LocationPicker in ParcelForm.jsx. Empty string
                // when the tenant hasn't configured a key yet — the map falls
                // back to a config-your-key notice in that case.
                'google_maps_key'       => (string) googleMapSettingKey(),
            ],
            'permissions' => [
                'create_product_pick' => hasPermission('parcel_product_pick'),
            ],
            'urls' => [
                'store'           => route('parcel.store'),
                'cancel'          => route('parcel.index'),
                'merchant_shops'  => route('parcel.merchant.shops'),
                'merchant_cod'    => route('get.merchant.cod'),
            ],
            't' => $this->parcelCreateLabels(),
        ]);
    }

    private function parcelCreateLabels(): array
    {
        return [
            'title'             => __('parcel.title') ?: 'Parcels',
            'create'            => __('parcel.create_parcel') ?: 'Create parcel',
            'charge_details'    => __('parcel.charge_details') ?: 'Charge details',
            'merchant'          => __('merchant.title') ?: 'Merchant',
            'shop'              => __('parcel.shop') ?: 'Shop',
            'pickup_phone'      => __('parcel.pickup_phone') ?: 'Pickup phone',
            'pickup_address'    => __('parcel.pickup_address') ?: 'Pickup address',
            'cash_collection'   => __('parcel.cash_collection') ?: 'Cash collection',
            'selling_price'    => __('parcel.selling_price') ?: 'Selling price',
            'invoice_no'        => __('parcel.invoice') ?: 'Invoice number',
            'category'          => __('parcel.category') ?: 'Category',
            'weight'            => __('parcel.weight') ?: 'Weight (kg)',
            'delivery_type'     => __('parcel.delivery_type') ?: 'Delivery type',
            'customer_name'     => __('parcel.customer_name') ?: 'Customer name',
            'customer_phone'    => __('parcel.customer_phone') ?: 'Customer phone',
            'city'              => __('parcel.city') ?: 'City',
            'area'              => __('parcel.area') ?: 'Area',
            'customer_address'  => __('parcel.customer_address') ?: 'Customer address',
            'note'              => __('parcel.note') ?: 'Note',
            'packaging'         => __('parcel.packaging') ?: 'Packaging',
            'priority'          => __('parcel.priority') ?: 'Priority',
            'normal'            => __('parcel.normal') ?: 'Normal',
            'high'              => __('parcel.high') ?: 'High',
            'liquid_fragile'    => __('parcel.liquid_fragile') ?: 'Liquid / fragile',
            'cancel'            => __('levels.cancel') ?: 'Cancel',
            'save'              => __('levels.save') ?: 'Save',
            'select_merchant'   => 'Select merchant',
            'select_city_first' => 'Select city first',
            'cod_charge'        => 'COD charge',
            'delivery_charge'   => 'Delivery charge',
            'packaging_charge'  => 'Packaging charge',
            'liquid_charge'     => 'Liquid / fragile charge',
            'total_charge'      => 'Total charge',
            'vat'               => 'VAT',
            'net_payable'       => 'Net payable',
            'current_payable'   => 'Current payable',
        ];
    }
    
    
 

    
    

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequest $request)
    {
        // Flash keys go via ->with('error'|'success', …) so HandleInertia-
        // Requests exposes them as props.flash — the FlashBanner in AdminLayout
        // renders them. Toastr::* writes to a legacy session key the Inertia
        // frontend never reads, so those calls used to fail silently: admin saw
        // a 302 with no visible feedback.
        $parcel_count = Parcel::companywise()->count();
        if (! settings()->subscription) {
            return redirect()->route('subscription.index')
                ->with('error', __('Your workspace has no active subscription. Contact billing.'));
        }
        if (settings()->subscription->parcel_count <= $parcel_count) {
            return redirect()->back()
                ->with('error', __('You have reached your parcel limit. Upgrade your package to create more.'));
        }

        // Wallet-balance gate for merchants that pay per-shipment from their wallet.
        $merchant = Merchant::find($request->merchant_id);
        if ($merchant && $merchant->wallet_use_activation == Status::ACTIVE) {
            $chargeDetails = json_decode($request->chargeDetails);
            if (isset($chargeDetails->totalDeliveryChargeAmount)
                && $chargeDetails->totalDeliveryChargeAmount > $merchant->wallet_balance) {
                return redirect()->back()->withInput($request->all())
                    ->with('error', __('This merchant has insufficient wallet balance to cover the delivery charge.'));
            }
        }

        if ($this->repo->store($request)) {
            return redirect()->route('parcel.index')
                ->with('success', __('parcel.added_msg'));
        }

        return redirect()->back()->withInput($request->all())
            ->with('error', __('parcel.error_msg'));
    }


    public function duplicateStore(StoreRequest $request)
    {
        $parcel_count = Parcel::companywise()->count();
        if (! settings()->subscription) {
            return redirect()->back()
                ->with('error', __('Your workspace has no active subscription. Contact billing.'));
        }
        if (settings()->subscription->parcel_count <= $parcel_count) {
            return redirect()->back()
                ->with('error', __('You have reached your parcel limit. Upgrade your package to create more.'));
        }

        if ($this->repo->duplicateStore($request)) {
            return redirect()->route('parcel.index')
                ->with('success', __('parcel.added_msg'));
        }

        return redirect()->back()
            ->with('error', __('parcel.error_msg'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }


    // Parcel logs
    public function logs($id)
    {
        $parcel = $this->repo->get($id);
        if (! $parcel) {
            Toastr::error(__('Parcel not found.'));
            return redirect()->back();
        }
        $events = $this->repo->parcelEvents($id);

        $status = (int) $parcel->status;
        $PS = ParcelStatus::class;
        $stages = [
            [
                'key'    => 'pending',
                'label'  => __('parcel.pending') ?: 'Pending',
                'active' => $status >= $PS::PENDING,
            ],
            [
                'key'    => 'pickup',
                'label'  => __('parcel.in_progress') ?: 'In progress',
                'active' => $status >= $PS::PICKUP_ASSIGN || $status >= $PS::PICKUP_RE_SCHEDULE,
            ],
            [
                'key'    => 'warehouse',
                'label'  => __('parcel.warehouse') ?: 'Warehouse',
                'active' => $status >= $PS::RECEIVED_WAREHOUSE,
            ],
            [
                'key'    => 'dispatch',
                'label'  => __('parcel.deliveryman_assigned') ?: 'Out for delivery',
                'active' => $status >= $PS::DELIVERY_MAN_ASSIGN || $status >= $PS::DELIVERY_RE_SCHEDULE,
            ],
            [
                'key'    => $status >= $PS::RETURN_TO_COURIER ? 'returned' : 'delivered',
                'label'  => $status >= $PS::RETURN_TO_COURIER
                            ? (__('parcel.return_courier') ?: 'Returned to courier')
                            : (__('parcel.delivered') ?: 'Delivered'),
                'active' => $status >= $PS::DELIVERED || $status >= $PS::PARTIAL_DELIVERED,
            ],
        ];

        $rows = collect($events)->map(function ($ev) {
            $statusId = (int) $ev->parcel_status;
            return [
                'id'           => $ev->id,
                'status'       => $statusId,
                'label'        => __('parcelLogs.' . $statusId) ?: \App\Support\ParcelStatusHelper::label($statusId),
                'color'        => $ev->cancel_parcel_id ? 'red' : \App\Support\ParcelStatusHelper::color($statusId),
                'note'         => $ev->note,
                'pickupman'    => $ev->pickupman ? [
                    'name'   => optional($ev->pickupman->user)->name,
                    'mobile' => optional($ev->pickupman->user)->mobile,
                ] : null,
                'deliveryman'  => $ev->deliveryMan ? [
                    'name'   => optional($ev->deliveryMan->user)->name,
                    'mobile' => optional($ev->deliveryMan->user)->mobile,
                ] : null,
                'hub'          => $ev->hub ? [
                    'name'  => $ev->hub->name,
                    'phone' => $ev->hub->phone,
                ] : null,
                'created_at'   => optional($ev->created_at)->toDateTimeString(),
                'created_date' => optional($ev->created_at)->format('Y-m-d'),
                'created_time' => optional($ev->created_at)->format('h:i a'),
            ];
        })->values();

        return \Inertia\Inertia::render('Admin/Parcel/Logs', [
            'parcel' => [
                'id'           => $parcel->id,
                'tracking_id'  => $parcel->tracking_id,
                'status'       => $status,
                'status_label' => \App\Support\ParcelStatusHelper::label($status),
                'status_color' => \App\Support\ParcelStatusHelper::color($status),
            ],
            'stages' => $stages,
            'events' => $rows,
            'urls' => [
                'index'       => route('parcel.index'),
                'details'     => route('parcel.details', $parcel->id),
                'print_label' => route('parcel.print-label', $parcel->id),
            ],
            't' => [
                'title'         => 'Webhook logs',
                'title_index'   => 'Parcels',
                'back'          => 'Back to parcel',
                'pickup_man'    => __('parcel.pickup_man') ?: 'Pickup courier',
                'delivery_man'  => __('parcelLogs.delivery_man') ?: 'Delivery courier',
                'mobile'        => __('levels.mobile') ?: 'Mobile',
                'phone'         => __('levels.phone') ?: 'Phone',
                'hub_name'      => __('parcelLogs.hub_name') ?: 'Hub',
                'hub_phone'     => __('parcelLogs.hub_phone') ?: 'Hub phone',
                'note'          => __('levels.note') ?: 'Note',
                'no_events'     => 'No events recorded for this parcel.',
                'pipeline'      => 'Workflow',
                'timeline'      => 'Event log',
            ],
        ]);
    }

    // Parcel duplicate
    public function duplicate($id)
    {
        $parcel             = $this->repo->get($id);
        if (! $parcel) {
            abort(404);
        }
        $merchants          = \App\Models\Backend\Merchant::companywise()->where('status', 1)
            ->with('user')->orderBy('business_name')->get();
        $shops              = $this->shop->all($parcel->merchant_id);
        $deliveryCategories = $this->repo->deliveryCategories();
        $packagings         = $this->repo->packaging();
        $deliveryTypes      = $this->repo->deliveryTypes();
        $cities             = $this->repo->cities()->load('areas');

        $merchantData = collect($merchants)->map(fn ($m) => [
            'id'             => $m->id,
            'name'           => $m->business_name,
            'vat'            => (float) ($m->vat ?? 0),
            'cod_charges'    => [
                'inside_city'  => (float) (data_get($m, 'cod_charges.inside_city') ?? 0),
                'sub_city'     => (float) (data_get($m, 'cod_charges.sub_city') ?? 0),
                'outside_city' => (float) (data_get($m, 'cod_charges.outside_city') ?? 0),
            ],
            'pickup_phone'   => optional($m->user)->mobile,
            'pickup_address' => $m->address,
        ])->values();

        $cityData = collect($cities)->map(fn ($c) => [
            'id'    => $c->id,
            'name'  => $c->en_name ?: $c->name,
            'areas' => collect($c->areas ?? [])->map(fn ($a) => [
                'id'   => $a->id,
                'name' => $a->en_name ?: $a->name,
            ])->values(),
        ])->values();

        return Inertia::render('Admin/Parcel/Clone', [
            'parcel' => [
                'id'                    => $parcel->id,
                'tracking_id'           => $parcel->tracking_id,
                'merchant_id'           => $parcel->merchant_id,
                'shop_id'               => $parcel->merchant_shop_id,
                'pickup_phone'          => $parcel->pickup_phone,
                'pickup_address'        => $parcel->pickup_address,
                'cash_collection'       => $parcel->cash_collection,
                'selling_price'         => $parcel->selling_price,
                'invoice_no'            => $parcel->invoice_no,
                'category_id'           => $parcel->category_id,
                'weight'                => $parcel->weight,
                'delivery_type_id'      => $parcel->delivery_type_id,
                'customer_name'         => $parcel->customer_name,
                'customer_phone'        => $parcel->customer_phone,
                'city_id'               => $parcel->city_id,
                'area_id'               => $parcel->area_id,
                'customer_address'      => $parcel->customer_address,
                'note'                  => $parcel->note,
                'packaging_id'          => $parcel->packaging_id,
                'priority_type_id'      => $parcel->priority_type_id,
                'liquid_fragile_amount' => $parcel->liquid_fragile_amount,
                'cod_charge'            => $parcel->cod_charge ?? 0,
                'vat'                   => $parcel->vat ?? 0,
            ],
            'initial_shops' => $shops->getCollection()->map(fn ($s) => [
                'id'    => $s->id,
                'name'  => $s->name ?? $s->title ?? null,
                'title' => $s->title ?? null,
            ])->values(),
            'merchants'      => $merchantData,
            'cities'         => $cityData,
            'categories'     => collect($deliveryCategories)->map(fn ($c) => [
                'id'   => $c->id,
                'name' => $c->name,
            ])->values(),
            'packagings'     => collect($packagings)->map(fn ($p) => [
                'id'    => $p->id,
                'name'  => $p->name,
                'price' => (float) $p->price,
            ])->values(),
            'delivery_types' => collect($deliveryTypes)->map(fn ($d) => [
                'id'   => $d->id,
                'name' => $d->name ?? $d->key ?? $d->id,
            ])->values(),
            'settings' => [
                'currency'              => settings()->currency,
                'vat_tax'               => (float) (settings()->vat ?? 0),
                'fragile_liquid_charge' => (float) (function_exists('SettingHelper') ? SettingHelper('fragile_liquid_charge') : 0),
            ],
            'urls' => [
                'store'          => route('parcel.clone-store'),
                'cancel'         => route('parcel.index'),
                'merchant_shops' => route('parcel.merchant.shops'),
                'merchant_cod'   => route('get.merchant.cod'),
            ],
            't' => array_merge($this->parcelCreateLabels(), [
                'clone' => __('levels.duplicate') ?: 'Duplicate parcel',
            ]),
        ]);
    }
    
    
    
    
       public function inlineupdate(Request $request)
    {
         
        $id = $request->id;
        $cash_collection = $request->cash_collection;
         
         
        if($this->repo->updateCOD($id, $cash_collection)){

              $details =   $this->repo->details($id);
              
              return $details;
        }
        
       
        
        return null;
     
     
    }

    // Parcel details
    public function details($id)
    {
        $parcel         = $this->repo->details($id);
        
         
        
    // If parcel not found, redirect or abort safely
    if (!$parcel) {
        Toastr::error(__('Parcel not found.'));
        return redirect()->back();
       
    }
        $deliveryman    = $this->deliveryman->all();
        $data = [];
        if($parcel->lastParcel3pl){
          $lastParcel3pl = $parcel->lastParcel3pl;  
          
          $parcel_3pl_name =  $lastParcel3pl->parcel_3pl_name;
          $awb_number =  $lastParcel3pl->awb_number;
          if($parcel_3pl_name == 'panda'){
              
             
          $response = $this->deliveryPanda->getListTracking([$awb_number]);
          
 
                
         if (
        isset($response['success']) && $response['success'] == 1 &&
        isset($response['TrackResponse']) && is_array($response['TrackResponse'])
    ) {
        foreach ($response['TrackResponse'] as $item) {
            if (isset($item['Shipment'])) {
                $awb = $item['Shipment']['awb_number'] ?? null;
                $status = $item['Shipment']['current_status'] ?? null;
                $datetime = $item['Shipment']['status_datetime'] ?? null;

                if ($awb) {

                $data["AWB number"] = $awb;
                $data["Current status"] = $status;
                $data["Status datetime"] = $datetime;
                    Parcels_3pl::where('awb_number', $awb)->update([
                        'current_status' => $status,
                        'status_datetime' => $datetime,
                    ]);
                }
            }
        }}
          }
        }
        
        
        $parcel->loadMissing(['images', 'merchant.user', 'merchantShop', 'hub', 'city', 'area', 'deliveryCategory']);

        $events = ParcelEvent::where('parcel_id', $id)
            ->with(['hub', 'deliveryMan.user', 'pickupman.user', 'user'])
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $waLink = function ($phone) {
            $digits = preg_replace('/\D+/', '', (string) $phone);
            return $digits ? 'https://wa.me/' . $digits : null;
        };

        $attachments = [];
        foreach ($parcel->images ?? [] as $img) {
            $attachments[] = [
                'url'   => $img->image_url,
                'label' => ucfirst(str_replace('_', ' ', $img->type)),
                'date'  => optional($img->created_at)->format('Y-m-d H:i'),
                'contain' => false,
            ];
        }
        foreach ($events as $ev) {
            if ($ev->delivered_image) {
                $attachments[] = [
                    'url'   => static_asset($ev->delivered_image),
                    'label' => __('Delivered Photo'),
                    'date'  => optional($ev->created_at)->format('Y-m-d H:i'),
                    'contain' => false,
                ];
            }
            if ($ev->signature_image) {
                $attachments[] = [
                    'url'   => static_asset($ev->signature_image),
                    'label' => __('Signature'),
                    'date'  => optional($ev->created_at)->format('Y-m-d H:i'),
                    'contain' => true,
                ];
            }
        }

        $senderName  = optional($parcel->merchant)->business_name ?? optional($parcel->merchantShop)->name;
        $senderPhone = $parcel->pickup_phone ?: optional(optional($parcel->merchant)->user)->mobile;

        return \Inertia\Inertia::render('Admin/Parcel/Details', [
            'parcel' => [
                'id'                   => $parcel->id,
                'tracking_id'          => $parcel->tracking_id,
                'awb_label'            => $parcel->awb_label,
                'invoice_no'           => $parcel->invoice_no,
                'status'               => (int) $parcel->status,
                'status_label'         => \App\Support\ParcelStatusHelper::label((int) $parcel->status),
                'status_color'         => \App\Support\ParcelStatusHelper::color((int) $parcel->status),
                'created_at'           => optional($parcel->created_at)->format('Y-m-d H:i'),
                'updated_at'           => optional($parcel->updated_at)->format('Y-m-d H:i'),
                'cod_amount'           => (float) ($parcel->cod_amount ?? 0),
                'cash_collection'      => (float) ($parcel->cash_collection ?? 0),
                'selling_price'        => (float) ($parcel->selling_price ?? 0),
                'total_delivery_amount'=> (float) ($parcel->total_delivery_amount ?? 0),
                'vat_amount'           => (float) ($parcel->vat_amount ?? 0),
                'current_payable'      => (float) ($parcel->current_payable ?? 0),
                'weight'               => $parcel->weight,
                'weight_unit'          => optional($parcel->deliveryCategory)->title,
                'delivery_type'        => $parcel->delivery_type_name ?? null,
                'city'                 => optional($parcel->city)->name,
                'area'                 => optional($parcel->area)->name,
                'hub'                  => optional($parcel->hub)->name,
                'priority'             => (int) ($parcel->priority_type_id ?? 2),
                'note'                 => $parcel->note,
                'attempts'             => (int) ($parcel->number_of_attempts ?? 0),
            ],
            'sender' => [
                'name'     => $senderName,
                'address'  => $parcel->pickup_address,
                'phone'    => $senderPhone,
                'whatsapp' => $waLink($senderPhone),
            ],
            'recipient' => [
                'name'     => $parcel->customer_name,
                'address'  => $parcel->customer_address,
                'phone'    => $parcel->customer_phone,
                'whatsapp' => $waLink($parcel->customer_phone),
            ],
            'attachments' => $attachments,
            'events' => $events->map(function ($ev) {
                $actor = optional(optional($ev)->user)->name
                    ?? optional(optional($ev->deliveryMan)->user)->name
                    ?? optional(optional($ev->pickupman)->user)->name;
                $statusId = (int) $ev->parcel_status;
                return [
                    'id'        => $ev->id,
                    'status'    => $statusId,
                    'label'     => \App\Support\ParcelStatusHelper::label($statusId),
                    'color'     => $ev->cancel_parcel_id ? 'red' : \App\Support\ParcelStatusHelper::color($statusId),
                    'actor'     => $actor,
                    'hub'       => optional($ev->hub)->name,
                    'note'      => $ev->note,
                    'created_at'=> optional($ev->created_at)->format('Y-m-d H:i:s'),
                ];
            })->values(),
            'panda_3pl' => empty($data) ? null : [
                'awb'      => $data['AWB number']      ?? null,
                'status'   => $data['Current status']  ?? null,
                'datetime' => $data['Status datetime'] ?? null,
            ],
            'currency'    => settings()->currency,
            'permissions' => [
                'edit'          => hasPermission('parcel_update'),
                'status_update' => hasPermission('parcel_status_update'),
                'delete'        => hasPermission('parcel_delete'),
            ],
            'urls' => [
                'index'       => route('parcel.index'),
                'edit'        => route('parcel.edit', $parcel->id),
                'logs'        => route('parcel.logs', $parcel->id),
                'print'       => route('parcel.print', $parcel->id),
                'print_label' => route('parcel.print-label', $parcel->id),
                'clone'       => route('parcel.clone', $parcel->id),
            ],
            't' => [
                'title'              => 'Shipment details',
                'title_index'        => 'Parcels',
                'sender_info'        => __('levels.sender_info') ?: 'Sender',
                'recipient_info'     => __('levels.recipient_info') ?: 'Recipient',
                'attachment'         => __('levels.attachment') ?: 'Attachments',
                'no_attachments'     => 'No attachments',
                'edit'               => __('levels.edit') ?: 'Edit',
                'logs'               => 'Webhook logs',
                'print'              => __('levels.print') ?: 'Print',
                'print_with_tracking'=> 'Print with tracking',
                'tracking_id'        => 'Tracking ID',
                'booking_date'       => __('levels.booking_date') ?: 'Booking date',
                'cod'                => __('levels.cod') ?: 'COD',
                'cash_collection'    => __('parcel.cash_collection') ?: 'Cash collection',
                'price'              => __('levels.price') ?: 'Price',
                'invoice'            => __('invoice.invoice') ?: 'Invoice',
                'weight'             => __('levels.weight') ?: 'Weight',
                'delivery_type'      => __('levels.delivery_type') ?: 'Delivery type',
                'city'               => __('levels.city') ?: 'City',
                'area'               => __('levels.area') ?: 'Area',
                'hub'                => __('levels.hub') ?: 'Hub',
                'note'               => __('levels.note') ?: 'Note',
                'status'             => __('levels.status') ?: 'Status',
                'timeline'           => 'Timeline',
                'finance'            => 'Finance',
                'shipment_creation'  => __('parcel.parcel_create') ?: 'Shipment created',
                'attempts'           => 'Delivery attempts',
                'panda_tracking'     => 'Panda 3PL tracking',
                'awb'                => 'AWB',
                'current_status'     => 'Current status',
                'last_update'        => 'Last update',
                'back_to_list'       => 'Back to parcels',
                'clone'              => __('levels.clone') ?: 'Clone',
            ],
        ]);
    }

    // Tracking offcanvas — AJAX partial loaded from the parcel index page
    public function trackingOffcanvas($id)
    {
        $parcel = $this->repo->details($id);

        if (! $parcel) {
            return response(__('Parcel not found.'), 404);
        }

        $parcel->loadMissing(['images', 'merchant.user', 'merchantShop', 'hub', 'city', 'area', 'deliveryCategory']);

        $parcelevents = ParcelEvent::where('parcel_id', $id)
            ->with(['hub', 'deliveryMan.user', 'pickupman.user', 'user'])
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return view('backend.parcel.partials.tracking_offcanvas', compact('parcel', 'parcelevents'));
    }

    /**
     * JSON shape of the tracking drawer used by the React index.
     * Same data as trackingOffcanvas() but structured so React can render
     * it natively with Tailwind instead of relying on the Bootstrap blade.
     */
    public function trackingJson($id)
    {
        $parcel = $this->repo->details($id);
        if (! $parcel) {
            return response()->json(['error' => __('Parcel not found.')], 404);
        }

        $parcel->loadMissing(['images', 'merchant.user', 'merchantShop', 'hub', 'city', 'area', 'deliveryCategory']);

        $events = \App\Models\Backend\ParcelEvent::where('parcel_id', $id)
            ->with(['hub', 'deliveryMan.user', 'pickupman.user', 'user'])
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $waLink = function ($phone) {
            $digits = preg_replace('/\D+/', '', (string) $phone);
            return $digits ? 'https://wa.me/' . $digits : null;
        };

        // Attachments: parcel images + each event's delivered/signature photos.
        $attachments = [];
        foreach ($parcel->images ?? [] as $img) {
            $attachments[] = [
                'url'   => $img->image_url,
                'label' => ucfirst(str_replace('_', ' ', $img->type)),
                'date'  => optional($img->created_at)->format('Y-m-d H:i'),
                'contain' => false,
            ];
        }
        foreach ($events as $ev) {
            if ($ev->delivered_image) {
                $attachments[] = [
                    'url'   => static_asset($ev->delivered_image),
                    'label' => __('Delivered Photo'),
                    'date'  => optional($ev->created_at)->format('Y-m-d H:i'),
                    'contain' => false,
                ];
            }
            if ($ev->signature_image) {
                $attachments[] = [
                    'url'   => static_asset($ev->signature_image),
                    'label' => __('Signature'),
                    'date'  => optional($ev->created_at)->format('Y-m-d H:i'),
                    'contain' => true,
                ];
            }
        }

        $senderName  = optional($parcel->merchant)->business_name ?? optional($parcel->merchantShop)->name;
        $senderPhone = $parcel->pickup_phone ?: optional(optional($parcel->merchant)->user)->mobile;

        return response()->json([
            'parcel' => [
                'id'              => $parcel->id,
                'tracking_id'     => $parcel->tracking_id,
                'status'          => (int) $parcel->status,
                'status_label'    => \App\Support\ParcelStatusHelper::label((int) $parcel->status),
                'status_color'    => \App\Support\ParcelStatusHelper::color((int) $parcel->status),
                'created_at'      => optional($parcel->created_at)->format('Y-m-d H:i'),
                'cod_amount'      => (float) ($parcel->cod_amount ?? 0),
                'selling_price'   => (float) ($parcel->selling_price ?? 0),
                'invoice_no'      => $parcel->invoice_no,
                'weight'          => $parcel->weight,
                'weight_unit'     => optional($parcel->deliveryCategory)->title,
                'delivery_type'   => $parcel->delivery_type_name ?? null,
                'city'            => optional($parcel->city)->name,
                'area'            => optional($parcel->area)->name,
                'note'            => $parcel->note,
                'urls' => [
                    'edit'        => route('parcel.edit', $parcel->id),
                    'logs'        => route('parcel.logs', $parcel->id),
                    'print'       => route('parcel.print', $parcel->id),
                    'print_label' => route('parcel.print-label', $parcel->id),
                ],
            ],
            'sender' => [
                'name'    => $senderName,
                'address' => $parcel->pickup_address,
                'phone'   => $senderPhone,
                'whatsapp'=> $waLink($senderPhone),
            ],
            'recipient' => [
                'name'    => $parcel->customer_name,
                'address' => $parcel->customer_address,
                'phone'   => $parcel->customer_phone,
                'whatsapp'=> $waLink($parcel->customer_phone),
            ],
            'attachments' => $attachments,
            'events' => $events->map(function ($ev) use ($parcel) {
                $actor = optional(optional($ev)->user)->name
                    ?? optional(optional($ev->deliveryMan)->user)->name
                    ?? optional(optional($ev->pickupman)->user)->name;
                $statusId = (int) $ev->parcel_status;
                return [
                    'id'        => $ev->id,
                    'status'    => $statusId,
                    'label'     => \App\Support\ParcelStatusHelper::label($statusId),
                    'color'     => $ev->cancel_parcel_id ? 'red' : \App\Support\ParcelStatusHelper::color($statusId),
                    'actor'     => $actor,
                    'hub'       => optional($ev->hub)->name,
                    'note'      => $ev->note,
                    'created_at'=> optional($ev->created_at)->format('Y-m-d H:i:s'),
                ];
            })->values(),
            'creation_event' => [
                'actor'      => $senderName,
                'created_at' => optional($parcel->created_at)->format('Y-m-d H:i:s'),
                'label'      => __('parcel.parcel_create') ?: 'Parcel created',
            ],
            'currency' => settings()->currency,
            't' => [
                'sender_info'    => __('levels.sender_info') ?: 'Sender',
                'recipient_info' => __('levels.recipient_info') ?: 'Recipient',
                'attachment'     => __('levels.attachment') ?: 'Attachments',
                'no_attachments' => __('No attachments') ?: 'No attachments',
                'edit'           => __('levels.edit') ?: 'Edit',
                'logs'           => 'Webhook logs',
                'print'          => __('levels.print') ?: 'Print',
                'print_with_tracking' => 'Print with tracking',
                'tracking_id'    => 'Tracking ID',
                'booking_date'   => __('levels.booking_date') ?: 'Booking date',
                'cod'            => __('levels.cod') ?: 'COD',
                'price'          => __('levels.price') ?: 'Price',
                'invoice'        => __('invoice.invoice') ?: 'Invoice',
                'weight'         => __('levels.weight') ?: 'Weight',
                'delivery_type'  => __('levels.delivery_type') ?: 'Delivery type',
                'city'           => __('levels.city') ?: 'City',
                'area'           => __('levels.area') ?: 'Area',
                'note'           => __('levels.note') ?: 'Note',
                'status'         => __('levels.status') ?: 'Status',
                'timeline'       => 'Timeline',
            ],
        ]);
    }
    
    
     public function addNdr(Request $request, $id)
    {
        
        // التحقق من صحة البيانات
        $request->validate([
            'rejection_reason_id' => 'required|exists:rejection_reasons,id',
            'deliveryman_id' => 'required|exists:delivery_man,id',
            'comments' => 'nullable|string|max:500',
            'attachments.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
        ]);

        // تجهيز المرفقات
        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('ndr_attachments', 'public');
                $attachments[] = $path;
            }
        }

        // إنشاء السجل في قاعدة البيانات
        RejectedParcel::create([
            'parcel_id' => $id,
            'rejection_reason_id' => $request->rejection_reason_id,
            'comments' => $request->comments,
            'attachments' => $attachments,
            'deliveryman_id' => $request->deliveryman_id,
            'created_by_type' => 'user',
            'created_by' => auth()->id(),
            
        ]);

        return redirect()->back()->with('success', __('NDR added successfully.'));
    }
    
    
    public function ThirdPartyLogistics($id, Request $request)
{
    $company = $request->company;
    $parcel = $this->repo->details($id);
    
 
    if ($company == 'panda') {

          $data = [
            "AwbNumber" => $parcel->tracking_id ?? $parcel->id,
            
            "FromCompany" => "Rushly",
            "FromAddress" => "Dubai",
            "FromCity" => "Dubai",
            "FromLocation" => "Dubai",
            "FromCountry" => "UAE",
            "FromCperson" => "Rushly",
            "FromContactno" => "-",
            "FromMobileno" => "-",

    
            "ToCompany" => $parcel->customer_name ?? '-',
            "ToAddress" => $parcel->customer_address ?? 'Unknown',
            "ToCity" => strtoupper($parcel->city->en_name ?? 'Dubai'),
            "ToLocation" => strtoupper($parcel->area->en_name ?? 'Dubai'),
            "ToCountry" => "UAE",
            "ToCperson" => $parcel->customer_name ?? '-',
            "ToContactno" => $parcel->customer_phone ?? '',
            "ToMobileno" => $parcel->customer_phone ?? '',
            
            "ReferenceNumber" => $parcel->reference_number ?? 'REF-' . $parcel->id,
            "Weight" => number_format((float) $parcel->weight, 2, '.', ''),  
            "Pieces" => $parcel->number_of_boxes ?? 1,
            "PackageType" => "Domestic Parcel",
            "CurrencyCode" => "AED",
            "NcndAmount" => $parcel->cash_collection ?? 0,
            "ItemDescription" => $parcel->package_description ?? 'General goods',
            "SpecialInstruction" => $parcel->note ?? '',
            "BranchName" => "Dubai"
        ];

        $response = $this->deliveryPanda->createCustomerToCustomer($data);
        
 
       return $response;
      
  

        return response()->json(json_decode($response, true));
    }

    if ($company === 'zajel') {
        if (! $this->zajel->isConfigured()) {
            return response()->json(['error' => 'Zajel is not configured (missing ZAJEL_API_KEY / ZAJEL_CUSTOMER_CODE).'], 400);
        }

        $payload  = $this->zajel->buildShipmentPayload($parcel);
        $response = $this->zajel->createShipment($payload);

        if (! empty($response['_error']) || empty($response['success'])) {
            \App\Models\Backend\Parcels_3pl::create([
                'parcel_id'       => $parcel->id,
                'parcel_3pl_name' => 'zajel',
                'awb_number'      => null,
                'awb_pdf'         => null,
                'response'        => $response,
            ]);
            return response()->json([
                'error'   => 'Zajel rejected the shipment.',
                'details' => $response,
            ], 422);
        }

        $awb       = $response['referenceNumber'] ?? null;
        $labelInfo = $awb ? $this->zajel->getShipmentLabel((string) $awb) : null;
        $awbPdf    = is_array($labelInfo) && empty($labelInfo['_error'])
            ? ($labelInfo['url'] ?? $labelInfo['label_url'] ?? null)
            : null;

        \App\Models\Backend\Parcels_3pl::create([
            'parcel_id'       => $parcel->id,
            'parcel_3pl_name' => 'zajel',
            'awb_number'      => $awb,
            'awb_pdf'         => $awbPdf,
            'response'        => $response,
        ]);

        return response()->json($response);
    }

    if ($company === 'aramex') {
        if (! $this->aramex->isConfigured()) {
            return response()->json(['error' => 'Aramex is not configured (missing ARAMEX_USERNAME / ARAMEX_ACCOUNT_NUMBER).'], 400);
        }

        $shipment = $this->aramex->buildShipmentPayload($parcel);
        $response = $this->aramex->createShipments([$shipment]);

        $awb     = null;
        $labelUrl = null;
        $hasErr  = ! empty($response['_error']) || ! empty($response['HasErrors']);

        if (! $hasErr) {
            // Aramex returns Shipments.ProcessedShipment (single object OR array)
            $processed = $response['Shipments']['ProcessedShipment'] ?? null;
            if ($processed && isset($processed[0])) {
                $processed = $processed[0];
            }
            if ($processed) {
                $hasErr   = ! empty($processed['HasErrors']);
                $awb      = $processed['ID'] ?? null;
                $labelUrl = $processed['ShipmentLabel']['LabelURL'] ?? null;
            } else {
                $hasErr = true;
            }
        }

        \App\Models\Backend\Parcels_3pl::create([
            'parcel_id'       => $parcel->id,
            'parcel_3pl_name' => 'aramex',
            'awb_number'      => $hasErr ? null : $awb,
            'awb_pdf'         => $hasErr ? null : $labelUrl,
            'response'        => $response,
        ]);

        if ($hasErr) {
            return response()->json([
                'error'   => 'Aramex rejected the shipment.',
                'details' => $response,
            ], 422);
        }
        return response()->json($response);
    }

    if ($company === 'logestechs') {
        // Logestechs is handled through the generic Shipping module — no more
        // per-request email/password input. The admin picks a pre-configured
        // connection (or we pick the default). See /admin/shipping/connections.
        $connectionId = (int) $request->input('connection_id', 0);
        $connection   = $connectionId
            ? \App\Shipping\Models\ShippingConnection::query()
                ->with('provider')
                ->where('id', $connectionId)
                ->where('company_id', settings()->id ?? null)
                ->first()
            : app(\App\Shipping\Repositories\ShippingConnectionRepository::class)
                ->defaultForCompany((int) (settings()->id ?? 0), 'logestechs');

        if (! $connection) {
            return response()->json([
                'error' => 'No active Logestechs connection. Add one at /admin/shipping/connections first.',
            ], 422);
        }

        try {
            $shipment = app(\App\Shipping\Services\ShipmentService::class)->createNow($parcel, $connection);
            return response()->json([
                'success'    => true,
                'shipment_id'=> $shipment->id,
                'awb_number' => $shipment->awb_number,
                'awb_pdf'    => $shipment->awb_pdf_url,
                'response'   => $shipment->response_payload,
            ]);
        } catch (\App\Shipping\Exceptions\ProviderRejectedShipmentException $e) {
            return response()->json([
                'error'   => 'Logestechs rejected the shipment: ' . $e->getMessage(),
                'details' => $e->payload,
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'error'   => 'Logestechs request failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    if ($company === 'jet') {
        if (! $this->jet->isConfigured()) {
            return response()->json(['error' => 'Jet is not configured (missing JET_USERNAME / JET_API_KEY / JET_SECRET_KEY / JET_ORDER_URL).'], 400);
        }

        $orderPayload = $this->jet->buildOrderPayload($parcel);
        $response     = $this->jet->createOrder($orderPayload);

        $detail = $response['detail'] ?? null;
        if ($detail && isset($detail[0])) $detail = $detail[0];

        $statusOk = ! empty($response['success']) && is_array($detail)
                    && (($detail['status'] ?? '') === 'Sukses')
                    && ! empty($detail['awb_no']);

        \App\Models\Backend\Parcels_3pl::create([
            'parcel_id'       => $parcel->id,
            'parcel_3pl_name' => 'jet',
            'awb_number'      => $statusOk ? ($detail['awb_no'] ?? null) : null,
            'awb_pdf'         => null,
            'response'        => $response,
        ]);

        if (! $statusOk) {
            return response()->json([
                'error'   => 'Jet rejected the order.',
                'reason'  => $detail['reason'] ?? ($response['desc'] ?? ($response['message'] ?? 'unknown')),
                'details' => $response,
            ], 422);
        }
        return response()->json($response);
    }

    return response()->json(['error' => '3PL company not supported or not selected.'], 400);
}

    



    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function edit($id)
    {
        $parcel             = $this->repo->get($id);
        $merchants          = \App\Models\Backend\Merchant::companywise()->where('status', 1)
            ->with('user')->orderBy('business_name')->get();
        $shops              = $this->shop->all($parcel->merchant_id);
        $deliveryCategories = $this->repo->deliveryCategories();
        $packagings         = $this->repo->packaging();
        $deliveryTypes      = $this->repo->deliveryTypes();
        $cities             = $this->repo->cities()->load('areas');

        $merchantData = collect($merchants)->map(fn ($m) => [
            'id'             => $m->id,
            'name'           => $m->business_name,
            'vat'            => (float) ($m->vat ?? 0),
            'cod_charges'    => [
                'inside_city'  => (float) (data_get($m, 'cod_charges.inside_city') ?? 0),
                'sub_city'     => (float) (data_get($m, 'cod_charges.sub_city') ?? 0),
                'outside_city' => (float) (data_get($m, 'cod_charges.outside_city') ?? 0),
            ],
            'pickup_phone'   => optional($m->user)->mobile,
            'pickup_address' => $m->address,
        ])->values();

        $cityData = collect($cities)->map(fn ($c) => [
            'id'    => $c->id,
            'name'  => $c->en_name ?: $c->name,
            'areas' => collect($c->areas ?? [])->map(fn ($a) => [
                'id'   => $a->id,
                'name' => $a->en_name ?: $a->name,
            ])->values(),
        ])->values();

        return Inertia::render('Admin/Parcel/Edit', [
            'parcel' => [
                'id'                    => $parcel->id,
                'tracking_id'           => $parcel->tracking_id,
                'merchant_id'           => $parcel->merchant_id,
                'shop_id'               => $parcel->shop_id,
                'pickup_phone'          => $parcel->pickup_phone,
                'pickup_address'        => $parcel->pickup_address,
                'cash_collection'       => $parcel->cash_collection,
                'selling_price'         => $parcel->selling_price,
                'invoice_no'            => $parcel->invoice_no,
                'category_id'           => $parcel->category_id,
                'weight'                => $parcel->weight,
                'delivery_type_id'      => $parcel->delivery_type_id,
                'customer_name'         => $parcel->customer_name,
                'customer_phone'        => $parcel->customer_phone,
                'city_id'               => $parcel->city_id,
                'area_id'               => $parcel->area_id,
                'customer_address'      => $parcel->customer_address,
                'note'                  => $parcel->note,
                'packaging_id'          => $parcel->packaging_id,
                'priority_type_id'      => $parcel->priority_type_id,
                'liquid_fragile_amount' => $parcel->liquid_fragile_amount,
                'cod_charge'            => $parcel->cod_charge ?? 0,
                'vat'                   => $parcel->vat ?? 0,
            ],
            'initial_shops'    => $shops->getCollection()->map(fn ($s) => [
                'id'    => $s->id,
                'name'  => $s->name ?? $s->title ?? null,
                'title' => $s->title ?? null,
            ])->values(),
            'merchants'        => $merchantData,
            'cities'           => $cityData,
            'categories'       => collect($deliveryCategories)->map(fn ($c) => [
                'id'   => $c->id,
                'name' => $c->name,
            ])->values(),
            'packagings'       => collect($packagings)->map(fn ($p) => [
                'id'    => $p->id,
                'name'  => $p->name,
                'price' => (float) $p->price,
            ])->values(),
            'delivery_types'   => collect($deliveryTypes)->map(fn ($d) => [
                'id'   => $d->id,
                'name' => $d->name ?? $d->key ?? $d->id,
            ])->values(),
            'settings' => [
                'currency'              => settings()->currency,
                'vat_tax'               => (float) (settings()->vat ?? 0),
                'fragile_liquid_charge' => (float) (function_exists('SettingHelper') ? SettingHelper('fragile_liquid_charge') : 0),
            ],
            'urls' => [
                'update'         => route('parcel.update', $parcel->id),
                'cancel'         => route('parcel.index'),
                'merchant_shops' => route('parcel.merchant.shops'),
                'merchant_cod'   => route('get.merchant.cod'),
            ],
            't' => array_merge($this->parcelCreateLabels(), [
                'edit'   => __('parcel.edit_parcel') ?: 'Edit parcel',
                'update' => __('levels.update') ?: 'Update',
            ]),
        ]);
    }

    // Parcel update
    public function statusUpdate($id, $status_id)
    {
        if (! $this->repo->statusUpdate($id, $status_id)) {
            Toastr::error(__('Cannot update — shipment is cancelled.'), __('message.error'));
            return redirect()->back();
        }
        Toastr::success(__('parcel.update_msg'),__('message.success'));
        return redirect()->route('parcel.index');
    }

    public function cancelShipment(Request $request, $id)
    {
        $reason = trim((string) $request->input('reason'));
        if (! $this->repo->cancelShipment($id, $reason ?: null)) {
            Toastr::error(__('Cannot cancel — only newly created shipments can be cancelled. Once pickup has been assigned, use the return flow instead.'), __('message.error'));
            return redirect()->back();
        }
        Toastr::success(__('Shipment cancelled. No further updates or actions can be taken on it.'), __('message.success'));
        return redirect()->route('parcel.details', $id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRequest $request, $id)
    {
       // dd($request->all());
        if($this->repo->update($id, $request)){
            Toastr::success(__('parcel.update_msg'),__('message.success'));
            return redirect()->route('parcel.index');
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $this->repo->delete($id);
        Toastr::success(__('parcel.delete_msg'),__('message.success'));
        return back();
    }
    
    
  
    
    public function parcelExport(Request $request){
      
        try {
            if($request->type && $request->type == 'csv'):
                return Excel::download(new MerchantParcelExport($this->repo->parcelExport($request)),'Parcels Export-csv-file-'.Carbon::now()->format('d-m-Y His').'.csv',\Maatwebsite\Excel\Excel::CSV, [
                    'Content-Type' => 'text/csv',
                ]);
            else:
                return Excel::download(new MerchantParcelExport($this->repo->parcelExport($request)),'Parcels Export-excel-file-'.Carbon::now()->format('d-m-Y His').'.xlsx');
            endif;
        } catch (\Throwable $th) {
            Toastr::error(__('parcel.delete_error_message'),__('message.error'));
            return redirect()->back();
        }
    }
    

    public function parcelImportExport()
    {
        $deliveryCategories = $this->repo->deliveryCategories();

        // Collapse the deliveryType translation array to just the
        // integer-keyed enum entries (skip alias keys like 'same_day').
        $deliveryTypes = collect(trans('deliveryType'))
            ->filter(fn ($_v, $k) => is_int($k) || ctype_digit((string) $k))
            ->map(fn ($label, $id) => ['id' => (int) $id, 'label' => $label])
            ->values();

        // Validation failures from the most recent POST land in session as
        // an array<int rowNumber, array<string error>>. Surface them in React.
        $importErrors = collect(session('importErrors', []))
            ->map(fn ($errs, $row) => ['row' => $row, 'errors' => array_values((array) $errs)])
            ->values();

        return Inertia::render('Admin/Parcel/Import', [
            'categories' => collect($deliveryCategories)->map(fn ($c) => [
                'id'    => $c->id,
                'title' => $c->title,
            ])->values(),
            'delivery_types' => $deliveryTypes,
            'import_errors'  => $importErrors,
            'urls' => [
                'index'        => route('parcel.index'),
                'submit'       => route('parcel.file-import'),
                'sample_file'  => static_asset('sample-parcel/parcel/import-parcel.xlsx'),
            ],
            't' => [
                'title'             => __('parcel.title') ?: 'Parcels',
                'import'            => __('parcel.import') ?: 'Import',
                'dashboard'         => __('levels.dashboard') ?: 'Dashboard',
                'sample'            => __('parcel.sample') ?: 'Download sample',
                'submit'            => __('parcel.import') ?: 'Import',
                'select_file'       => __('levels.select_file') ?: 'Select Excel file',
                'instructions'      => __('levels.import_title') ?: 'Follow these rules to ensure successful import:',
                'instruction_2'     => __('levels.import_title_2') ?: 'Use the sample file as a template.',
                'instruction_3'     => __('levels.import_title_3') ?: 'Make sure all required columns are filled.',
                'instruction_4'     => __('levels.import_title_4') ?: 'Default value if not provided.',
                'categories'        => __('levels.category') ?: 'Categories',
                'delivery_types'    => __('menus.delivery_type') ?: 'Delivery types',
                'cash_collection'   => __('parcel.cash_collection') ?: 'Cash collection',
                'selling_price'     => __('parcel.selling_price') ?: 'Selling price',
                'validation_log'    => __('parcel.validation_log') ?: 'Validation log',
                'row_number'        => __('parcel.in_row_number') ?: 'Row',
                'file_required'     => 'Choose an Excel file before importing.',
                'no_errors'         => 'No errors from the last import.',
            ],
        ]);
    }

    public function parcelImport(Request $request)
    {
        $request->validate([
            'file' => 'required',
        ]);
        try {
            $import = new ParcelImport();
            $import->import($request->file('file'));
        } catch (ValidationException $e) {
            $failures     = $e->failures();
            $importErrors = [];
            foreach ($failures as $failure) {
                $failure->row(); // row that went wrong
                $failure->attribute(); // either heading key (if using heading row concern) or column index
                $failure->errors(); // Actual error messages from Laravel validator
                $failure->values(); // The values of the row that has failed.
                $importErrors[$failure->row()][] = $failure->errors()[0];
            }
            return back()->with('importErrors', $importErrors);
        }
        Toastr::success(__('parcel.added_msg'),__('message.success'));
        return redirect()->route('parcel.index');
    }

    public function getImportMerchant(Request $request){
        $search   = $request->search;
        
       
                    
        $response = array();
        if($request->searchQuery == 'true'){
            if($search == ''){
                $merchants = Merchant::companywise()->where('status',Status::ACTIVE)->orderby('business_name','asc')->select('id','business_name','vat')->where('business_name', 'like', '%' .$search . '%')->limit(10)->get();
            }else{
                $merchants = Merchant::companywise()->where('status',Status::ACTIVE)->orderby('business_name','asc')->select('id','business_name','vat')->where('business_name', 'like', '%' .$search . '%')->limit(10)->get();
            }

            foreach($merchants as $merchant){
                $response[] = array(
                    "id"=>$merchant->id,
                    "text"=>$merchant->id.' = '.$merchant->business_name,
                );
            }
            return response()->json($response);
        }

    }

    public function getMerchant(Request $request){
    
        $search   = $request->search;
        $response = array();
        if($request->searchQuery == 'true'){
            if($search == ''){
                $merchants = [];
            }else{
                $merchants = Merchant::companywise()->where('status',Status::ACTIVE)->orderby('business_name','asc')->select('id','business_name','vat')->where('business_name', 'like', '%' .$search . '%')->limit(10)->get();
            }

            foreach($merchants as $merchant){
                $response[] = array(
                    "id"=>$merchant->id,
                    "text"=>$merchant->business_name,
                );
            }
            return response()->json($response);
        }else {
            $merchant = Merchant::find($search);

            $response[] = array(
                "vat"         =>$merchant->vat?? 0,
                "cod_charges" =>$merchant->cod_charges,
            );
            return response()->json($response);
        }

    }


    // Hub search
    public function getHub(Request $request){
        $search   = $request->search;
        $response = array();
        if($request->searchQuery == 'true'){
            if($search == ''){
                $hubs = [];
            }
            else{
                $hubs = Hub::companywise()->where('status',Status::ACTIVE)->orderby('name','asc')->select('id','name')->where('name', 'like', '%' .$search . '%')->limit(10)->get();
            }
            foreach($hubs as $hub){
                $response[] = array(
                    "id"=>$hub->id,
                    "text"=>$hub->name,
                );
            }
            return response()->json($response);
        }
    }


    /**
     * AJAX: list the WMS products that belong to the selected merchant,
     * suitable for the product picker in the parcel-create form. Returns
     * { has_fulfillment, products: [{id, sku, name, unit, unit_price?}, ...] }.
     * Tenant-scoping comes from the global Parcel scope as a side benefit
     * (merchant_id is constrained to the current tenant via the merchants
     * scope on the row that was originally selected).
     */
    public function merchantProducts(Request $request)
    {
        if (! $request->ajax() && ! $request->wantsJson()) {
            abort(404);
        }
        $merchantId = (int) $request->query('merchant_id');
        $merchant = Merchant::where('id', $merchantId)->where('company_id', settings()->id)->first();
        if (! $merchant) {
            return response()->json(['has_fulfillment' => false, 'products' => []]);
        }
        if (! $merchant->hasService('fulfillment')) {
            return response()->json(['has_fulfillment' => false, 'products' => []]);
        }
        $products = \App\Models\Backend\Wms\WmsProduct::companywise()
            ->where('merchant_id', $merchant->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'sku', 'name', 'unit', 'barcode']);

        return response()->json([
            'has_fulfillment' => true,
            'products'        => $products,
        ]);
    }

    public function getMerchantCod(Request $request){


        if(request()->ajax()):
            $merchant = [];

            $merchant = Merchant::find($request->merchant_id);

            $merchant = [
                    'inside_city'  => $merchant->cod_charges['inside_city'],
                    'sub_city' => $merchant->cod_charges['sub_city'],
                    'outside_city' => $merchant->cod_charges['outside_city']
            ];
            
            
            
            
            
            return response()->json($merchant);
        endif;
        return '';


    }

    public function merchantShops(Request $request)
    {
        if (request()->ajax()) {
            if ($request->id && $request->shop == 'true') {
                $merchantShops          = [];
                $merchantShop           = MerchantShops::where(['merchant_id'=>$request->id,'default_shop'=>Status::ACTIVE])->first();
                $merchantShops[]        = $merchantShop;
                $merchantShopArray      = MerchantShops::where(['merchant_id'=>$request->id,'default_shop'=>Status::INACTIVE])->get();
                if(!blank($merchantShopArray)){
                    foreach ($merchantShopArray as $shop){
                        $merchantShops[] = $shop;
                    }
                }

                if (!blank($merchantShops)) {
                    return view('backend.parcel.shops', compact('merchantShops'));
                }
                return '';
            }else {
                $merchantShop = MerchantShops::find($request->id);
                if (!blank($merchantShop)) {
                    return $merchantShop;
                }
                return '';
            }
        }
        return '';
    }

    public function deliveryCharge(Request $request)
    {
       
       // merchant_id=16&category_id=1&weight=5&delivery_type_id=2
        if (request()->ajax()) {

            if ($request->merchant_id && $request->category_id && $request->weight !='0' && $request->delivery_type_id) {
                $charges = MerchantDeliveryCharge::where([
                        'merchant_id'=>$request->merchant_id,
                        'category_id'=>$request->category_id,
                        'weight'=>$request->weight
                    ])
                    ->orderby('id' ,'DESC')
                    ->first();

                if (blank($charges)) {
                    $charges = DeliveryCharge::where(['category_id'=>$request->category_id])->first();
                }

            } else {
                $charges     = MerchantDeliveryCharge::where(['merchant_id'=>$request->merchant_id,'category_id'=>$request->category_id,'weight'=>$request->weight])->first();
                if (blank($charges)) {
                    $charges = DeliveryCharge::where(['category_id'=>$request->category_id])->first();
                }
            }

            if (!blank($charges)) {
                if($request->delivery_type_id == '1'){
                    $chargeAmount = $charges->same_day;
                }elseif ($request->delivery_type_id == '2') {
                    $chargeAmount = $charges->next_day;
                }elseif ($request->delivery_type_id == '3') {
                    $chargeAmount = $charges->sub_city;
                }elseif ($request->delivery_type_id == '4') {
                    $chargeAmount = $charges->outside_city;
                }else {
                    $chargeAmount = 0;
                }
                return $chargeAmount;
            }
            return 0;
        }
        return 0;
    }


    public function deliveryWeight(Request $request)
    {
        if (request()->ajax()) {
            if ($request->category_id) {
                $deliveryCharges = DeliveryCharge::companywise()->where('category_id',$request->category_id)->get();

                if (!blank($deliveryCharges)) {
                    return view('backend.parcel.deliveryWeight', compact('deliveryCharges'));
                }
                return '';
            }
        }
        return '';
    }




    //delivery man search

    public function transferHub(Request $request){


        $parcelEvent = ParcelEvent::where(['parcel_id'=>$request->parcel_id,'parcel_status'=>ParcelStatus::RECEIVED_WAREHOUSE])->first();
        $hubs        = Hub::orderByDesc('id')->whereNotIn('id',[$parcelEvent->hub_id])->get();
             $response = '';
        foreach ($hubs as $hub){
            $response .= '<option value="'.$hub->id.'" selected> '.$hub->name.'</option>';
        }
        return $response;
    }


    public function deliverymanSearch(Request $request){

        $search = $request->search;
        if($request->single){
            $deliveryMan  = ParcelEvent::where([
                    'parcel_id'=>$request->parcel_id,
                    'parcel_status'=>$request->status
                ])->first();

            if(isset($deliveryMan->deliveryMan) && !blank($deliveryMan->deliveryMan)){
                $response = '<option value="'.$deliveryMan->delivery_man_id.'" selected> '.$deliveryMan->deliveryMan->user->name.'</option>';

            }else {
                $response = '<option value="'.$deliveryMan->pickup_man_id.'" selected> '.$deliveryMan->pickupman->user->name.'</option>';

            }
            return $response;
        }else{
            if($search == ''){
                $deliverymans = [];
            }else{
                $deliverymans = User::companywise()->where('status',Status::ACTIVE)
                                      ->orderby('name','asc')
                                      ->select('id','name')
                                      ->where('name', 'like', '%' .$search . '%')
                                      ->where('user_type',UserType::DELIVERYMAN)->limit(10)->get();
            }
            $response=[];

            foreach($deliverymans as $deliveryman){
                $response[] = array(
                    "id"  => $deliveryman->deliveryman->id,
                    "text"=> $deliveryman->name,
                );
            }
            return response()->json($response);
        }


    }

    //parcel search in recived by hub
    public function parcelRecivedByHubSearch(Request $request){

        if($request->ajax()){
            $hub      = $request->hub_id;
            $track_id = $request->track_id;

            if($track_id && $hub){
                        $parcel      = Parcel::companywise()->with(['merchant','merchant.user'])->where([
                                                    'tracking_id'     => $request->track_id,
                                                    'transfer_hub_id' => $hub,
                                                    'status'          => ParcelStatus::TRANSFER_TO_HUB
                                                ])->first();
                    if($parcel){
                        return response()->json($parcel);
                    }else{
                        return 0;
                    }
            }
        }

    }

    public function transfertohubSelectedHub(Request $request){
        $parcel          = Parcel::find($request->parcel_id);
        if($parcel){
            if($parcel->hub_id){
                return '<option selected disabled>'.$parcel->hub->name.'</option>';
            }else{
                return '<option selected disabled>Hub not found</option>';
            }
        }else{
                return '<option selected disabled>Hub not found</option>';

        }
    }

    public function PickupManAssigned(Request $request){


        $validator=Validator::make($request->all(),[
            'delivery_man_id'=>'required'
        ]);
        if($validator->fails()):
            Toastr::error(__('parcel.required'),__('message.error'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        endif;
        if($this->repo->pickupdatemanAssigned($request->parcel_id, $request)){
            Toastr::success(__('parcel.pickup_man_assigned'),__('message.success'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }else{

            Toastr::error(__('parcel.error_msg'),__('message.error'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }
    }

    public function PickupManAssignedCancel(Request $request){

        if($this->repo->pickupdatemanAssignedCancel($request->parcel_id, $request)){
            Toastr::success(__('parcel.pickup_man_assigned'),__('message.success'));
            return redirect()->back();
        }else{

            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    public function PickupReSchedule(Request $request){

        $validator=Validator::make($request->all(),[
            'delivery_man_id'=>'required',
            'date'=>'required',
        ]);
        if($validator->fails()):
            Toastr::error(__('parcel.required'),__('message.error'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        endif;

        if($this->repo->PickupReSchedule($request->parcel_id, $request)){
            Toastr::success(__('parcel.pickup_scheduled'),__('message.success'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }
    }

    public function PickupReScheduleCancel(Request $request){

        if($this->repo->PickupReScheduleCancel($request->parcel_id, $request)){
            Toastr::success(__('parcel.pickup_reschedule_canceled'),__('message.success'));
            return redirect()->back();
        }else{

            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }


    public function receivedBypickupman(Request $request){

        if($this->repo->receivedBypickupman($request->parcel_id, $request)){
            Toastr::success(__('parcel.received_by_pickup_success'),__('message.success'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }
    }



    public function receivedByHub(Request $request){

        if($this->repo->receivedByHub($request->parcel_id, $request)){
            Toastr::success(__('parcel.received_by_hub'),__('message.success'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }
    }



    public function receivedByHubCancel(Request $request){

        if($this->repo->receivedByHubCancel($request->parcel_id, $request)){
            Toastr::success(__('parcel.received_by_hub_cancel'),__('message.success'));
            return redirect()->back();
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }




    public function receivedBypickupmanCancel(Request $request){

        if($this->repo->receivedBypickupmanCancel($request->parcel_id, $request)){
            Toastr::success(__('parcel.received_by_pickup_cancel_success'),__('message.success'));
            return redirect()->back();
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }


    public function search(Request $data)
    {
     
        return $this->repo->search($data);
    }

    public function searchDeliveryManAssingMultipleParcel(Request $data)
    {
        return $this->repo->searchDeliveryManAssingMultipleParcel($data);
    }

    public function searchExpense(Request $data)
    {
        return $this->repo->searchExpense($data);
    }

    public function searchIncome(Request $data)
    {
        return $this->repo->searchIncome($data);
    }



    public function transferToHubMultipleParcel(Request $request){


        $validator=Validator::make($request->all(),[
            'hub_id'     => 'required',
            'parcel_ids' => 'required',
        ]);
        if($validator->fails()):
            Toastr::error(__('parcel.required'),__('message.error'));
            return redirect(paginate_redirect($request));
        endif;

        if($this->repo->transferToHubMultipleParcel($request)){
            Toastr::success(__('parcel.transfer_to_hub_success'),__('message.success'));

            $deliveryman    = $this->deliveryman->get($request->delivery_man_id);
            $parcels        = $this->repo->bulkParcels($request->parcel_ids);
            $bulk_type      = ParcelStatus::TRANSFER_TO_HUB;
            $transfered_hub = Hub::find($request->hub_id);
            return view('backend.parcel.bulk_print',compact('parcels','deliveryman','bulk_type','transfered_hub'));
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect(paginate_redirect($request));
        }

    }


    public function deliveryManAssignMultipleParcel(Request $request){
        $validator=Validator::make($request->all(),[
            'delivery_man_id' => 'required',
            'parcel_ids_'     => 'required',
        ]);
        if($validator->fails()):
            Toastr::error(__('parcel.required'),__('message.error'));
            return redirect(paginate_redirect($request));
        endif;

        if($this->repo->deliveryManAssignMultipleParcel($request)){
            Toastr::success(__('parcel.delivery_man_assign_success'),__('message.success'));
            $deliveryman= $this->deliveryman->get($request->delivery_man_id);
            $parcels    = $this->repo->bulkParcels($request->parcel_ids_);
            $bulk_type  = ParcelStatus::DELIVERY_MAN_ASSIGN;
            $report_name = __("Runsheet"); 
            return view('backend.parcel.bulk_print',compact('parcels','deliveryman','bulk_type' , 'report_name'));

        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect(paginate_redirect($request));
        }
    }


    public function ParcelBulkAssignPrint(Request $request){
        try {

            $deliveryman  = $this->deliveryman->get($request->delivery_man_id);
            
           
            $parcels      = $this->repo->bulkParcels($request->parcels);
            $bulk_type    = ParcelStatus::DELIVERY_MAN_ASSIGN;
            $report_name = __("Runsheet");
            $report_title = $report_name ."_".date('Y-m-d')."_".@$deliveryman->user->name;
            $reprint = true;
            return view('backend.parcel.bulk_print',compact('parcels','deliveryman','bulk_type','reprint' ,'report_name' , 'report_title'));

        } catch (\Throwable $th) {
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        }

    }




    public function transfertohub(Request $request){

        $validator=Validator::make($request->all(),[
            'hub_id'=>'required'
        ]);
        if($validator->fails()):
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        endif;

        if($this->repo->transfertohub($request->parcel_id, $request)){
            Toastr::success(__('parcel.transfer_to_hub_success'),__('message.success'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }
    }


    public function transfertoHubCancel(Request $request){

        if($this->repo->transfertoHubCancel($request->parcel_id, $request)){
            Toastr::success(__('parcel.transfer_to_hub_canceled'),__('message.success'));
            return redirect()->back();
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }





    public function deliverymanAssign(Request $request){


        $validator=Validator::make($request->all(),[
            'delivery_man_id'=>'required'
        ]);
        if($validator->fails()):
            Toastr::error(__('parcel.required'),__('message.error'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        endif;

        if($this->repo->deliverymanAssign($request->parcel_id, $request)){
            Toastr::success(__('parcel.delivery_man_assign_success'),__('message.success'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }
    }


    public function deliverymanAssignCancel(Request $request){

        if($this->repo->deliverymanAssignCancel($request->parcel_id, $request)){
            Toastr::success(__('parcel.deliveryman_assign_cancel'),__('message.success'));
            return redirect()->back();
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect()->back();
        }
    }



    public function deliveryReschedule(Request $request){

        $validator=Validator::make($request->all(),[
            'delivery_man_id'=>'required',
            'date'           => 'required'
        ]);
        if($validator->fails()):
            Toastr::error(__('parcel.required'),__('message.error'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        endif;

        if($this->repo->deliveryReschedule($request->parcel_id, $request)){
            Toastr::success(__('parcel.delivery_reschedule_success'),__('message.success'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }
    }


    public function deliveryReScheduleCancel(Request $request){

        if($this->repo->deliveryReScheduleCancel($request->parcel_id, $request)){
            Toastr::success(__('parcel.delivery_re_schedule_cancel'),__('message.success'));
            return redirect()->back();
        }else{

            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }



    public function receivedWarehouse(Request $request){
        $validator=Validator::make($request->all(),[
            'hub_id'=>'required'
        ]);
        if($validator->fails()):
            Toastr::error(__('parcel.required'),__('message.error'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        endif;

        if($this->repo->receivedWarehouse($request->parcel_id, $request)){
            Toastr::success(__('parcel.received_warehouse_success'),__('message.success'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }
    }


    public function receivedWarehouseCancel(Request $request){

        if($this->repo->receivedWarehouseCancel($request->parcel_id, $request)){
            Toastr::success(__('parcel.received_warehouse_cancel'),__('message.success'));
            return redirect()->back();
        }else{

            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }



    public function returntoQourier(Request $request){
        if($this->repo->returntoQourier($request->parcel_id, $request)){
            Toastr::success(__('parcel.return_to_qourier_success'),__('message.success'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }
    }



    public function returntoQourierCancel(Request $request){

        if($this->repo->returntoQourierCancel($request->parcel_id, $request)){
            Toastr::success(__('parcel.received_warehouse_cancel'),__('message.success'));
            return redirect()->back();
        }else{

            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }





    public function returnAssignToMerchant(Request $request){
        $validator=Validator::make($request->all(),[
            'delivery_man_id'=>'required',
            'date'           =>'required'

        ]);
        if($validator->fails()):
            toast(__('parcel.required'),'error');
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        endif;
        if($this->repo->returnAssignToMerchant($request->parcel_id, $request)){
            Toastr::success(__('parcel.return_assign_to_merchant_success'),__('message.success'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect(paginate_redirect($request));
        }
    }



    public function returnAssignToMerchantCancel(Request $request){

        if($this->repo->returnAssignToMerchantCancel($request->parcel_id, $request)){
            Toastr::success(__('parcel.return_assign_to_merchant_cancel_success'),__('message.success'));
            return redirect()->back();
        }else{

            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }
    public function returnAssignToMerchantReschedule(Request $request){

        $validator=Validator::make($request->all(),[
            'delivery_man_id'=>'required',
            'date'           =>'required'

        ]);
        if($validator->fails()):
            Toastr::error(__('parcel.required'),__('message.error'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        endif;

        if($this->repo->returnAssignToMerchantReschedule($request->parcel_id, $request)){
            Toastr::success(__('parcel.return_assign_to_merchant_reschedule_success'),__('message.success'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }
    }



    public function returnAssignToMerchantRescheduleCancel(Request $request){

        if($this->repo->returnAssignToMerchantRescheduleCancel($request->parcel_id, $request)){
            Toastr::success(__('parcel.return_assign_to_merchant_reschedule_cancel_success'),__('message.success'));
            return redirect()->back();
        }else{

            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }



    public function returnReceivedByMerchant(Request $request){
        if($this->repo->returnReceivedByMerchant($request->parcel_id, $request)){
            Toastr::success(__('parcel.return_received_by_merchant'),__('message.success'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }
    }


    public function returnReceivedByMerchantCancel(Request $request){

        if($this->repo->returnReceivedByMerchantCancel($request->parcel_id, $request)){
            Toastr::success(__('parcel.return_received_by_merchant_cancel_success'),__('message.success'));
            return redirect()->back();
        }else{

            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }






    public function parcelDelivered(Request $request){
        if($this->repo->parcelDelivered($request->parcel_id, $request)){
            Toastr::success(__('parcel.delivered_success'),__('message.success'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }
    }


    public function parcelDeliveredCancel(Request $request){

        if($this->repo->parcelDeliveredCancel($request->parcel_id, $request)){
            Toastr::success(__('parcel.delivered_cancel'),__('message.success'));
            return redirect()->back();
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }




    public function parcelPartialDelivered(Request $request){

        $validator = Validator::make($request->all(),[
            'cash_collection'       => 'required',
        ]);

        if($validator->fails()){
            Toastr::error(__('parcel.required'),__('message.error'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }

        if($this->repo->parcelPartialDelivered($request->parcel_id, $request)){
            Toastr::success(__('parcel.partial_delivered_success'),__('message.success'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            if($request->filter == 'on'):
                return redirect()->back();
            endif;
            return redirect(paginate_redirect($request));
        }
    }


    public function parcelPartialDeliveredCancel(Request $request){
        if($this->repo->parcelPartialDeliveredCancel($request->parcel_id, $request)){
            Toastr::success(__('parcel.partial_delivered_cancel'),__('message.success'));
            return redirect()->back();
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }


    public function parcelPrint($id)
    {
        $parcel = $this->repo->get($id);
        if (!$parcel) {
            Toastr::error(__('Parcel not found.'));
            return redirect()->back();
        }
        $parcel->loadMissing(['merchant.user', 'deliveryCategory']);

        return \Inertia\Inertia::render('Admin/Parcel/Print', [
            'parcel' => [
                'id'                    => $parcel->id,
                'tracking_id'           => $parcel->tracking_id,
                'invoice_no'            => $parcel->invoice_no,
                'created_at'            => optional($parcel->created_at)->format('Y-m-d'),
                'pickup_date'           => optional($parcel->pickup_date)->format('Y-m-d'),
                'delivery_date'         => optional($parcel->delivery_date)->format('Y-m-d'),
                'delivery_type'         => $parcel->delivery_type_name ?? null,
                'weight'                => $parcel->weight,
                'category'              => optional($parcel->deliveryCategory)->title,
                'cash_collection'       => (float) ($parcel->cash_collection ?? 0),
                'total_delivery_amount' => (float) ($parcel->total_delivery_amount ?? 0),
                'current_payable'       => (float) ($parcel->current_payable ?? 0),
                'customer_name'         => $parcel->customer_name,
                'customer_phone'        => $parcel->customer_phone,
                'customer_address'      => $parcel->customer_address,
            ],
            'merchant' => [
                'business_name' => optional($parcel->merchant)->business_name,
                'unique_id'     => optional($parcel->merchant)->merchant_unique_id,
                'mobile'        => optional(optional($parcel->merchant)->user)->mobile,
                'email'         => optional(optional($parcel->merchant)->user)->email,
                'address'       => optional($parcel->merchant)->address,
            ],
            'company' => [
                'name'     => settings()->business_name ?? config('app.name'),
                'logo'     => settings()->logo ?? null,
                'currency' => settings()->currency,
            ],
            'urls' => [
                'index'   => route('parcel.index'),
                'details' => route('parcel.details', $parcel->id),
            ],
            't' => [
                'title'           => 'Print invoice',
                'title_index'     => 'Parcels',
                'back'            => 'Back',
                'print'           => 'Print',
                'invoice'         => __('invoice.invoice') ?: 'Invoice',
                'date'            => 'Date',
                'from'            => 'From',
                'to'              => 'To',
                'tracking_id'     => 'Tracking ID',
                'delivery_type'   => 'Delivery type',
                'pickup_date'     => 'Pickup date',
                'delivery_date'   => 'Delivery date',
                'category'        => 'Category',
                'weight'          => 'Weight',
                'qty'             => 'Qty',
                'total'           => 'Total',
                'delivery_amount' => 'Delivery amount',
                'current_payable' => 'Current payable',
                'cash_collection' => 'Cash collection',
                'phone'           => 'Phone',
                'email'           => 'Email',
                'address'         => 'Address',
            ],
        ]);
    }

 
    
    public function parcelPrintLabel($id)
{
    $parcel = $this->repo->get($id);
  
    // Wrap single parcel in a collection or array
    return $this->printMultipleParcelLabels(collect([$parcel]));
}


    //multiple parcel label print
      public function parcelMultiplePrintLabel(Request $request){
        $validator=Validator::make($request->all(),[
            'parcels' =>'required'
        ]);
        if($validator->fails()):
            Toastr::error('Must be select parcel.',__('message.error'));
            return redirect()->back();
        endif;
        $parcels = $this->repo->parcelMultiplePrintLabel($request);
        
        return $this->printMultipleParcelLabels($parcels);
        
         
        
        return view('backend.parcel.multiple-print-label',compact('parcels'));
    }

public function printMultipleParcelLabels($parcels)
{
    $mpdfTempDir = storage_path('app/mpdf');
    if (!is_dir($mpdfTempDir)) {
        @mkdir($mpdfTempDir, 0775, true);
    }

    $resolver = app(\App\Services\Label\LabelTemplateResolver::class);
    // Use the first parcel's effective template to pick the paper format.
    $firstParcel = $parcels->first();
    $defaultTpl  = $firstParcel
        ? $resolver->forParcel($firstParcel)
        : $resolver->tenantDefault();

   $mpdf = new Mpdf([
        'tempDir'           => $mpdfTempDir,
        'format'            => $defaultTpl->format(),
        'default_font_size' => 10,
        'default_font'      => 'sans-serif',
        'display_mode'      => 'fullpage',
        'margin_left'       => 0,
        'margin_right'      => 0,
        'margin_top'        => 0,
        'margin_bottom'     => 0,
    ]);

    $mpdf->autoScriptToLang = true;
    $mpdf->autoLangToFont   = true;
    $mpdf->showImageErrors  = true;
    $mpdf->autoPageBreak    = false;

    foreach ($parcels as $parcel) {
      
        // cash_collection	
        $merchant = $parcel->merchant;
        $merchant_shop = $parcel->merchantShop;

        $order_reference = $parcel->tracking_id ?? "-";
        $reference_number = $parcel->reference_number ;

        $sender_name = $merchant->business_name ?? "-";
        $sender_addressLine1 = $parcel->pickup_address ?? "-";
        $sender_addressLine2 = $merchant_shop->address ?? "-";
        $sender_country = "UAE";
        $sender_city = $parcel->city->en_name ?? "-"; 
        $sender_neighbourhood = $parcel->area->en_name ?? "-"; 
        $sender_phone = $parcel->pickup_phone ?? "-";

        $receiver_name = $parcel->customer_name ?? "-";
        $receiver_addressLine1 = $parcel->customer_address ?? "-";
        $receiver_addressLine2 = "-";
        $receiver_country = "UAE";
        $receiver_city = $parcel->city->en_name ?? "-"; 
        $receiver_city_code = $parcel->city->city_code ?? "-"; 
        $receiver_state = $parcel->area->en_name ?? "-"; 
        $receiver_neighbourhood = $parcel->area->en_name ?? "-"; 
        $receiver_phone = $parcel->customer_phone ?? "-";

        $codAmount = $parcel->cash_collection ?? 0;
        $dropoff_time = $parcel->delivery_date ?? date('Y-m-d');
        $isCod = ($codAmount >= 1);

        $description = (!empty($parcel->note) && strlen($parcel->note) >= 1)
            ? Str::limit($parcel->note, 150)
            : "شحنة - فئة: " . ($parcel->delivery_category->title ?? "-") . " - الوزن: " . ($parcel->weight ?? "0") . " كجم";

        $number_of_boxes = $parcel->number_of_boxes ?? 1;
        
        $watermarkText = $isCod ? 'COD' : 'CC';
        $mpdf->SetWatermarkText($watermarkText);
        $mpdf->showWatermarkText = true;

 

        // Loop based on number of boxes
        for ($x = 1; $x <= $number_of_boxes; $x++) {
            $data = [];
            $data['totalPages'] = $number_of_boxes;
            $data['currentPage'] = $x;
            $data['sender']['name'] = $sender_name;
            $data['sender']['addressLine1'] = $sender_addressLine1;
            $data['sender']['addressLine2'] = $sender_addressLine2;
            $data['sender']['country'] = $sender_country;
            $data['sender']['phone'] = $sender_phone;
            $data['receiver']['name'] = $receiver_name;
            $data['receiver']['addressLine1'] = $receiver_addressLine1;
            $data['receiver']['addressLine2'] = $receiver_addressLine2;
            $data['receiver']['country'] = $receiver_country;
            $data['receiver']['city'] = $receiver_city;
            $data['receiver']['city_code'] = $receiver_city_code;
            $data['receiver']['state'] = $receiver_state;
            $data['receiver']['phone'] = $receiver_phone;
            $data['isCod'] = $isCod;
            $data['codAmount'] = $codAmount;
            $data['awb'] = (string) $parcel->id;
            $data['rushlyAwb'] = (string) $parcel->id;
            $data['date'] = $dropoff_time;
            $data['description'] = $description;
            $data['orderNumber'] = $order_reference;
            $data['reference_number'] = $reference_number;
            

            $tpl = $resolver->forParcel($parcel);
            $html = view($tpl->view(), compact('data'))->render();

            $mpdf->AddPage();
            $mpdf->WriteHTML($html);
        }
    }

         $firstId = $parcels->first()->id ?? '0';
         $lastId  = $parcels->last()->id ?? '0';

         $filename = "bulk_parcel_labels_{$firstId}_{$lastId}.pdf";

        return $mpdf->Output($filename, 'I');
}

    public function parcelReceivedByMultipleHub(Request $request){
            if($this->repo->parcelReceivedByMultipleHub($request->parcel_id,$request)){
                Toastr::success(__('parcel.received_by_multiple_hub'),__('message.success'));
                return redirect(paginate_redirect($request));
            }else{
                Toastr::error(__('parcel.error_msg'),__('message.error'));
                return redirect(paginate_redirect($request));
            }
    }





    //Assign pickup bulk

    public function AssignPickupParcelSearch(Request $request){
        if($request->ajax()){
            $merchant_id      = $request->merchant_id;
            $tracking_id      = $request->tracking_id;

            if($merchant_id !== null && $tracking_id !== null){

                        $parcel      = Parcel::companywise()->with(['merchant','merchant.user'])->where([
                                                    'merchant_id'     => $merchant_id,
                                                    'tracking_id'     => $tracking_id,
                                                    'status'          => ParcelStatus::PENDING
                                                ])->first();

                    if($parcel){
                        return response()->json($parcel);
                    }else{
                        return 0;
                    }
            }else{

               return 0;
            }
        }

        return 0;
    }




    //assign pickup bulk store
    public function AssignPickupBulk(Request $request){
        $validator = Validator::make($request->all(),[
            'merchant_id'       => 'required',
            'delivery_man_id'   => 'required'
        ]);

        if($validator->fails()){
            Toastr::error(__('parcel.feild_required'),__('message.error'));
            return redirect(paginate_redirect($request));
        }

        if($this->repo->pickupdatemanAssignedBulk($request)){
            Toastr::success(__('parcel.pickup_man_assigned'),__('message.success'));
            return redirect(paginate_redirect($request));
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect(paginate_redirect($request));
        }

    }


    //assign return to merchant

    //return to courier percel will be show
    public function AssignReturnToMerchantParcelSearch(Request $request){
        if($request->ajax()){
            $merchant_id      = $request->merchant_id;
            $tracking_id      = $request->tracking_id;

            if($merchant_id !== null && $tracking_id !== null){

                        $parcel      = Parcel::companywise()->with(['merchant','merchant.user'])->where([
                                                    'merchant_id'     => $merchant_id,
                                                    'tracking_id'     => $tracking_id,
                                                    'status'          => ParcelStatus::RETURN_TO_COURIER
                                                ])->first();

                    if($parcel){
                        return response()->json($parcel);
                    }else{
                        return 0;
                    }
            }else{

               return 0;
            }
        }

        return 0;
    }


    //assign return to merchant bulk store
    public function AssignReturnToMerchantBulk(Request $request){

            $validator = Validator::make($request->all(),[
                'merchant_id'       => 'required',
                'delivery_man_id'   => 'required',
                'date'              => 'required'
            ]);

            if($validator->fails()){
                Toastr::error(__('parcel.feild_required'),__('message.error'));
                return redirect(paginate_redirect($request));
            }


            if($this->repo->AssignReturnToMerchantBulk($request)){
                Toastr::success(__('parcel.return_assign_to_merchant_success'),__('message.success'));

                $deliveryman    = $this->deliveryman->get($request->delivery_man_id);
                $parcels        = $this->repo->bulkParcels($request->parcel_ids);
                $bulk_type      = ParcelStatus::RETURN_ASSIGN_TO_MERCHANT;
                $report_name = __("RTC Runsheet");
                return view('backend.parcel.bulk_print',compact('parcels','deliveryman','bulk_type' , 'report_name'));

            }else{
                Toastr::error(__('parcel.error_msg'),__('message.error'));
                return redirect(paginate_redirect($request));
            }

    }


    //received warehouse hub auto selected
    public function warehouseHubSelected(Request $request){
        $hubs_list  = "";
        $hubs_list .= "<option>".__("menus.select")." ". __("hub.title") ."</option>";

        if($request->hub_id):
            $hubs=Hub::all();
            foreach ($hubs as $hub) {

                if($hub->id == $request->hub_id){
                    $hubs_list .= "<option selected value=".$hub->id." >".$hub->name."</option>";
                }else{
                    $hubs_list .= "<option   value='".$hub->id."' >".$hub->name."</option>";
                }
            }
          else:
            $hubs=Hub::all();
            foreach ($hubs as $key => $hub) {

                $hubs_list .= "<option   value='".$hub->id."' >".$hub->name."</option>";

            }
          endif;

          return $hubs_list;
    }


    public function ParcelSearchs(Request $request)
    {
        $paginate  = session('per_page', 10);
        $paginator = $this->repo->parcelSearchs($request);
        if (!$paginator) {
            return redirect()->back();
        }
        return $this->renderParcelIndex($paginator, $request, $paginate);
    }


    //parcel sample export
    public function parcelSampleExport(){
          return Excel::download(new ParcelSampleExport,'invoice.xlsx');
    }

public function exportShipments(Request $request)
{
    ini_set('memory_limit', '-1');
    $date = Carbon::now()->format('Y-m-d');
    $filename = "shipments_{$date}.xlsx";

    return Excel::download(new ShipmentExport($request, $this->repo), $filename);
}

    



    public function priorityUpdate(Request $request){

        $parcel = Parcel::where(['id'=>$request->id])->first();
        if(1 == (int)$request->priority){
            $parcel->priority_type_id      =  2;
        }else {
            $parcel->priority_type_id      =  1;
        }
        $parcel->save();

        return $parcel;
    }
    // Parcel parcelDeliveryMan
    public function parcelDeliveryMan(Request $request)
    {
        // $parcelEvents = ParcelEvent::with('parcel')->whereNotNull('delivery_man_id')->where('parcel_status',ParcelStatus::DELIVERY_MAN_ASSIGN)->get();
        // $mapParcels = [];
        // $mapParcelslocations = [];
        // if(!blank($parcelEvents)) {
        //     foreach($parcelEvents as $key => $parcelEvent) {
        //         $mapParcelslocations[] = ['location'=> $parcelEvent->parcel->customer_address];

        //         $mapParcels[$key]['deliveryMan'] = optional($parcelEvent->deliveryMan->user)->name;
        //         $mapParcels[$key]['deliveryPhone'] = optional($parcelEvent->deliveryMan->user)->mobile;
        //         $mapParcels[$key]['deliveryImage'] = optional($parcelEvent->deliveryMan->user)->image;
        //         // $mapParcels[$key]['lat'] = $parcelEvent->delivery_lat;
        //         // $mapParcels[$key]['long'] = $parcelEvent->delivery_long;
        //         $mapParcels[$key]['lat'] = $parcelEvent->parcel->customer_lat;
        //         $mapParcels[$key]['long'] = $parcelEvent->parcel->customer_long;
        //         $mapParcels[$key]['customer_name'] = $parcelEvent->parcel->customer_name;
        //         $mapParcels[$key]['customer_address'] = $parcelEvent->parcel->customer_address;
        //         $mapParcels[$key]['customer_phone'] = $parcelEvent->parcel->customer_phone;
        //         $mapParcels[$key]['merchant_business_name'] = $parcelEvent->parcel->merchant->business_name;
        //         $mapParcels[$key]['merchant_phone'] = $parcelEvent->parcel->merchant->user->mobile;
        //         $mapParcels[$key]['merchant_address'] = $parcelEvent->parcel->merchant->address;
        //         $mapParcels[$key]['current_payable'] = $parcelEvent->parcel->current_payable;
        //         $mapParcels[$key]['tracking_id'] = $parcelEvent->parcel->tracking_id;
        //         $mapParcels[$key]['url'] = route('parcel.logs',$parcelEvent->parcel->id);
        //     }
        // }
        
        // $parcelsLocations = $mapParcelslocations;


        //parcel location 
            $mapParcels = [];
            $mapParcelslocations = [];
            $parcels = Parcel::where('status',[ParcelStatus::DELIVERY_MAN_ASSIGN,ParcelStatus::DELIVERY_RE_SCHEDULE])->get();
            if(!blank($parcels)) {
                foreach($parcels as $key => $parcel) {
                    $mapParcelslocations[] = ['location'=> $parcel->customer_address];
    
                    $mapParcels[$key]['lat'] = $parcel->customer_lat;
                    $mapParcels[$key]['long'] = $parcel->customer_long;
                    $mapParcels[$key]['customer_name'] = $parcel->customer_name;
                    $mapParcels[$key]['customer_address'] = $parcel->customer_address;
                    $mapParcels[$key]['customer_phone'] = $parcel->customer_phone;
                    $mapParcels[$key]['merchant_business_name'] = $parcel->merchant->business_name ?? "-";
                    $mapParcels[$key]['merchant_phone'] = $parcel->merchant->user->mobile ?? "-";
                    $mapParcels[$key]['merchant_address'] = $parcel->merchant->address  ?? "-";
                    $mapParcels[$key]['current_payable'] = $parcel->current_payable;
                    $mapParcels[$key]['tracking_id'] = $parcel->tracking_id;
                    $mapParcels[$key]['url'] = route('parcel.logs',$parcel->id);

                }
            }
        $parcelsLocations = $mapParcelslocations;
        //end parcel locations

        $points = collect($mapParcels)
            ->filter(fn ($p) => is_numeric($p['lat'] ?? null) && is_numeric($p['long'] ?? null))
            ->map(fn ($p) => [
                'lat'              => (float) $p['lat'],
                'lng'              => (float) $p['long'],
                'tracking_id'      => $p['tracking_id'] ?? null,
                'customer_name'    => $p['customer_name'] ?? null,
                'customer_phone'   => $p['customer_phone'] ?? null,
                'customer_address' => $p['customer_address'] ?? null,
                'merchant'         => $p['merchant_business_name'] ?? null,
                'merchant_phone'   => $p['merchant_phone'] ?? null,
                'current_payable'  => (float) ($p['current_payable'] ?? 0),
                'url'              => $p['url'] ?? null,
            ])
            ->values();

        return Inertia::render('Admin/Parcel/Map', [
            'points'          => $points,
            'total'           => count($mapParcels),
            'plotted'         => $points->count(),
            'google_maps_key' => googleMapSettingKey(),
            'currency'        => settings()->currency,
            'urls' => [
                'index'    => route('parcel.index'),
            ],
            't' => [
                'title'        => __('parcel.title') ?: 'Parcels',
                'map'          => __('parcel.map') ?: 'Delivery map',
                'no_points'    => 'No deliveries are currently out for delivery.',
                'no_map_key'   => 'Google Maps API key is not configured.',
                'tracking_id'  => __('parcel.tracking_id') ?: 'Tracking ID',
                'customer'     => __('parcel.customer_name') ?: 'Customer',
                'phone'        => __('parcel.customer_phone') ?: 'Phone',
                'address'      => __('parcel.customer_address') ?: 'Address',
                'merchant'     => __('parcel.merchant') ?: 'Merchant',
                'amount'       => __('parcel.amount') ?: 'Amount',
                'open_logs'    => __('levels.parcel_logs') ?: 'Open logs',
                'plotted'      => 'Plotted',
                'no_coords'    => 'Missing coords',
                'fullscreen'   => 'Fullscreen',
                'exit_fullscreen' => 'Exit fullscreen',
                'loading_map'     => 'Loading map…',
                'map_load_failed' => 'Failed to load Google Maps.',
            ],
        ]);
    }

    public function deliveredInfo($id)
    {
        $parcel = $this->repo->get($id);
        if (! $parcel) {
            Toastr::error(__('Parcel not found.'));
            return redirect()->back();
        }

        // Proof-of-Delivery view: only the DELIVERED / PARTIAL_DELIVERED
        // events carry the recipient photo + signature, so filter the full
        // events feed down to those.
        $events = collect($this->repo->parcelEvents($id))
            ->whereIn('parcel_status', [\App\Enums\ParcelStatus::DELIVERED, \App\Enums\ParcelStatus::PARTIAL_DELIVERED])
            ->map(fn ($e) => [
                'id'               => (int) $e->id,
                'parcel_status'    => (int) $e->parcel_status,
                'status_label'     => __('parcelLogs.'.$e->parcel_status) ?: 'Status #'.$e->parcel_status,
                'note'             => (string) ($e->note ?? ''),
                'created_at'       => (string) $e->created_at,
                'created_at_date'  => optional($e->created_at)->format('d M Y'),
                'created_at_time'  => optional($e->created_at)->format('h:i a'),
                // static_asset() prefixes with the CDN / storage root; matches the
                // convention already used everywhere else for these two fields.
                'delivered_image'  => $e->delivered_image  ? static_asset($e->delivered_image)  : null,
                'signature_image'  => $e->signature_image  ? static_asset($e->signature_image)  : null,
            ])
            ->values();

        // Parcel-level attached images. `image_path` lives under the public
        // storage disk, so the browser URL is /storage/<path> (Laravel's
        // storage:link makes public/storage → storage/app/public). The old
        // Blade double-nested the path via url('storage/app/public/...'),
        // which returned a URL that only worked when the operator was
        // running under a specific route prefix — brittle and wrong.
        $parcelImages = collect($parcel->images ?? [])
            ->map(fn ($img) => [
                'id'   => (int) $img->id,
                'type' => (string) ($img->type ?? ''),
                'url'  => \Storage::disk('public')->url((string) $img->image_path),
            ])
            ->values();

        return \Inertia\Inertia::render('Admin/Parcel/DeliveredInfo', [
            'parcel' => [
                'id'          => (int) $parcel->id,
                'tracking_id' => (string) ($parcel->tracking_id ?? ''),
                'status_label'=> \App\Support\ParcelStatusHelper::label((int) $parcel->status),
                'status_color'=> \App\Support\ParcelStatusHelper::color((int) $parcel->status),
            ],
            'events'         => $events,
            'parcel_images'  => $parcelImages,
            'urls' => [
                'parcel_index'   => route('parcel.index'),
                'parcel_details' => route('parcel.details', $parcel->id),
                'print_label'    => route('parcel.print-label', $parcel->id),
            ],
            't' => [
                'title'             => __('View Proof of Delivery'),
                'back_to_details'   => __('Back to shipment'),
                'no_pod'            => __('No proof of delivery available for this shipment yet.'),
                'delivered_photo'   => __('Delivered photo'),
                'signature'         => __('Signature'),
                'note'              => __('levels.note') ?: 'Note',
                'parcel_images'     => __('Parcel images'),
                'print_label'       => __('Print label'),
            ],
        ]);
    }
    
    
    public function getAreasByCity(Request $request)
{
    $areas = Area::where('city_id', $request->city_id)
                 ->where('is_active', 1)
                 ->orderBy('sorting')
                 ->get();

    return response()->json($areas);
}

}

