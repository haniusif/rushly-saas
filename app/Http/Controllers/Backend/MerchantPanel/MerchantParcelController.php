<?php

namespace App\Http\Controllers\Backend\MerchantPanel;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Imports\ParcelImport;
use App\Imports\MParcelImport;
use App\Models\Backend\DeliveryCharge;
use App\Models\Backend\Merchant;
use App\Models\Backend\Area;

use App\Models\Backend\MerchantDeliveryCharge;
use App\Models\MerchantShops;
use App\Repositories\Merchant\MerchantInterface;
use App\Repositories\MerchantPanel\MerchantParcel\MerchantParcelInterface;
use App\Repositories\MerchantPanel\Shops\ShopsInterface;
use Illuminate\Http\Request;
use App\Http\Requests\MerchantPanel\Parcel\StoreRequest;
use App\Http\Requests\MerchantPanel\Parcel\UpdateRequest;
use App\Models\Backend\DeliveryMan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Enums\ParcelStatus;
use App\Exports\MerchantParcelExport;
use App\Http\Resources\MerchantParcelExportResource;
use App\Models\Backend\ParcelEvent; 
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Inertia\Inertia;


   use Illuminate\Support\Facades\Storage;
 
class MerchantParcelController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    protected $merchant;
    protected $repo;
    protected $shop;
    public function __construct(MerchantParcelInterface $repo, MerchantInterface $merchant, ShopsInterface $shop)
    {
        $this->merchant = $merchant;
        $this->repo = $repo;
        $this->shop = $shop;
    }

    /**
     * Look up the merchant row for the current user, or short-circuit the
     * request when there isn't one. Non-merchant users (e.g. an admin who
     * lands on /merchant/parcel/index by accident) used to dereference
     * $merchant->id and 500. Now they get a clean redirect + flash.
     */
    private function currentMerchant()
    {
        $merchant = $this->repo->getMerchant(Auth::id());
        if (! $merchant) {
            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                redirect()->route('dashboard.index')
                    ->with('error', __('No merchant profile is linked to this account.'))
            );
        }
        return $merchant;
    }
    public function index(Request $request)
    {
        $userID   = Auth::user()->id;
        $merchant = $this->currentMerchant();
        $parcels  = $this->repo->all($merchant->id);
        return $this->renderParcelList('Merchant/Parcel/Index', $request, $parcels, $merchant, [
            'title_key' => 'menus.parcel',
            'page_kind' => 'index',
        ]);
    }

    public function parcelBank(Request $request)
    {
        $userID   = Auth::user()->id;
        $merchant = $this->currentMerchant();
        $parcels  = $this->repo->parcelBank($merchant->id);
        return $this->renderParcelList('Merchant/Parcel/Index', $request, $parcels, $merchant, [
            'title_key' => 'menus.parcel_bank',
            'page_kind' => 'bank',
        ]);
    }

    /**
     * Per-status counts for the chip strip above the merchant's parcel list.
     *
     * One grouped query rather than a count per chip, and always scoped to
     * THIS merchant — the admin equivalent counts the whole company, which
     * would leak other merchants' volumes onto a merchant-facing page.
     *
     * The parcel-bank page counts only banked parcels so its chips agree with
     * the rows below them.
     */
    private function statusCounts(int $merchantId, string $pageKind): array
    {
        $base = \App\Models\Backend\Parcel::query()->where('merchant_id', $merchantId);
        if ($pageKind === 'bank') {
            $base->where('parcel_bank', 'on');
        }

        $counts = (clone $base)
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $one = fn ($status) => (int) ($counts[$status] ?? 0);

        return [
            'total'     => (int) $counts->sum(),
            'pending'   => $one(\App\Enums\ParcelStatus::PENDING),
            'assigned'  => $one(\App\Enums\ParcelStatus::PICKUP_ASSIGN),
            'picked_up' => $one(\App\Enums\ParcelStatus::RECEIVED_WAREHOUSE),
            'ofd'       => $one(\App\Enums\ParcelStatus::DELIVERY_MAN_ASSIGN),
            'delivered' => $one(\App\Enums\ParcelStatus::DELIVERED),
            'returned'  => $one(\App\Enums\ParcelStatus::RETURN_RECEIVED_BY_MERCHANT),
            // Deliberately counts ONLY status CANCELLED, not the whole family of
            // *_CANCEL codes. Clicking the chip filters on a single status
            // (MerchantParcelRepository::filter does `where status = ?`), so an
            // aggregated count would advertise a number the filtered list can
            // never show. The admin list has that mismatch; not reproducing it.
            'cancelled' => $one(\App\Enums\ParcelStatus::CANCELLED),
            'failed'    => $one(\App\Enums\ParcelStatus::DELIVERY_RE_SCHEDULE),
        ];
    }

    private function renderParcelList(string $component, Request $request, $paginator, $merchant, array $cfg)
    {
        $i = (($paginator->currentPage() - 1) * $paginator->perPage()) + 1;
        $statusList = (array) trans('merchantParcelStatusFilter');
        $currency   = settings()->currency;
        $kpiCounts  = $this->statusCounts($merchant->id, $cfg['page_kind']);

        $rows = collect($paginator->items())->map(function ($p) use (&$i, $statusList) {
            return [
                'serial'        => $i++,
                'id'            => $p->id,
                'tracking_id'   => $p->tracking_id,
                'invoice_id'    => $p->invoice_id ?? null,
                'customer_name' => $p->customer_name,
                'customer_phone'=> $p->customer_phone,
                'amount'        => (float) ($p->cash_collection ?? 0),
                'status'        => (int) $p->status,
                'status_label'  => $statusList[$p->status] ?? (string) $p->status,
                // Same curated hex the admin list renders its pills from, so
                // both pages colour a given status identically.
                'status_color'  => \App\Support\ParcelStatusHelper::color((int) $p->status),
                'payment_label' => strip_tags((string) ($p->payment_status_string ?? '')),
                'created_at'    => optional($p->created_at)->toDateTimeString(),
                'details_url'   => route('merchant-panel.parcel.details', $p->id),
                'logs_url'      => route('merchant-panel.parcel.logs', $p->id),
            ];
        })->values();

        $statusOptions = collect($statusList)->map(fn ($label, $key) => [
            'value' => (string) $key,
            'label' => (string) $label,
            'color' => \App\Support\ParcelStatusHelper::color((int) $key),
        ])->values();

        return Inertia::render($component, [
            'rows'       => $rows,
            'kpi_counts' => $kpiCounts,
            'currency'   => $currency,
            'filters'    => [
                'parcel_date'           => $request->parcel_date,
                'parcel_status'         => $request->parcel_status,
                'parcel_customer'       => $request->parcel_customer,
                'parcel_customer_phone' => $request->parcel_customer_phone,
                'invoice_id'            => $request->invoice_id,
            ],
            'lookups'    => [
                'statuses' => $statusOptions,
            ],
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
            'urls' => [
                'create'       => route('merchant-panel.parcel.create'),
                'filter'       => route('merchant-panel.parcel.filter'),
                'reset'        => $cfg['page_kind'] === 'bank'
                    ? route('merchant-panel.parcel-bank.index')
                    : route('merchant-panel.parcel.index'),
                'import'       => route('merchant-panel.parcel.parcel-import'),
                'export_xlsx'  => route('merchant-panel.parcel.file-export'),
                'export_csv'   => route('merchant-panel.parcel.file-export', ['type' => 'csv']),
            ],
            't' => [
                'title'         => __($cfg['title_key']) ?: ($cfg['page_kind'] === 'bank' ? 'Parcel bank' : 'Parcels'),
                'list'          => __('levels.list') ?: 'List',
                'add'           => __('levels.add') ?: 'Add',
                'import'        => __('parcel.import') ?: 'Import',
                'export'        => __('menus.export') ?: 'Export',
                'export_xlsx'   => __('parcel.export_xlsx') ?: 'Excel',
                'export_csv'    => __('parcel.export_csv') ?: 'CSV',
                'dashboard'     => __('levels.dashboard') ?: 'Dashboard',
                'tracking_id'   => __('parcel.tracking_id') ?: 'Tracking ID',
                'recipient_info'=> __('parcel.recipient_info') ?: 'Recipient',
                'amount'        => __('parcel.amount') ?: 'Amount',
                'status'        => __('parcel.status') ?: 'Status',
                'payment'       => __('parcel.payment') ?: 'Payment',
                'date'          => __('parcel.date') ?: 'Date',
                'date_ph'       => __('merchantPlaceholder.date') ?: 'YYYY-MM-DD ~ YYYY-MM-DD',
                'status_ph'     => __('merchantPlaceholder.status') ?: 'All statuses',
                'customer'      => __('parcel.customer') ?: 'Customer',
                'customer_ph'   => __('merchantPlaceholder.customer') ?: 'Customer name',
                'customer_phone'=> __('parcel.customer_phone') ?: 'Customer phone',
                'phone_ph'      => __('merchantPlaceholder.phone') ?: 'Phone',
                'invoice_id'    => __('invoice.id') ?: 'Invoice ID',
                'invoice_ph'    => __('merchantPlaceholder.invoice_id') ?: 'Invoice ID',
                'filter'        => __('levels.filter') ?: 'Filter',
                'clear'         => __('levels.clear') ?: 'Clear',
                'view'          => __('levels.view') ?: 'View',
                'logs'          => __('parcel.logs') ?: 'Logs',
                'empty'         => __('levels.no_data_found') ?: ($cfg['page_kind'] === 'bank' ? 'No parcels in bank.' : 'No parcels yet.'),
                'list'          => __('levels.list') ?: 'List',
                'all'           => __('levels.all') ?: 'All',
                'date_label'    => __('parcel.date') ?: 'Date',
                'status_label'  => __('parcel.status') ?: 'Status',
                'showing'       => __('levels.showing') ?: 'Showing',
                'of'            => __('levels.of') ?: 'of',
                'active'        => __('levels.active') ?: 'active',
                'view_list'     => __('levels.list') ?: 'List',
                'view_cards'    => __('levels.cards') ?: 'Cards',
                // Chip strip above the table. Same keys the admin list uses.
                'chip_total'     => __('parcel.chip_total')     ?: 'Total',
                'chip_pending'   => __('parcel.chip_pending')   ?: 'Pending',
                'chip_assigned'  => __('parcel.chip_assigned')  ?: 'Assigned',
                'chip_picked_up' => __('parcel.chip_picked_up') ?: 'Picked up',
                'chip_ofd'       => __('parcel.chip_ofd')       ?: 'OFD',
                'chip_delivered' => __('parcel.chip_delivered') ?: 'Delivered',
                'chip_returned'  => __('parcel.chip_returned')  ?: 'Returned',
                'chip_cancelled' => __('parcel.chip_cancelled') ?: 'Cancelled',
                'chip_failed'    => __('parcel.chip_failed')    ?: 'Failed',
            ],
        ]);
    }

    public function filter(Request $request)
    {
        $userID = Auth::user()->id;
        $merchant = $this->currentMerchant();
        $parcels  = $this->repo->filter($merchant->id, $request);
        if (! $parcels) {
            return redirect()->back();
        }
        return $this->renderParcelList('Merchant/Parcel/Index', $request, $parcels, $merchant, [
            'title_key' => 'menus.parcel',
            'page_kind' => 'index',
        ]);
    }

    public function create()
    {
        $userID = Auth::user()->id;
        $merchant = $this->currentMerchant();
        return Inertia::render('Merchant/Parcel/Create', $this->buildParcelFormProps($merchant));
    }

    /**
     * Shared prop bag for Create and Edit. Extracted so edit() can render the
     * same Inertia component with a `parcel` prefill and `urls.update`.
     */
    protected function buildParcelFormProps($merchant): array
    {
        // Normalize shops to a stable {id, name, phone, address, lat, long} shape.
        // getShops() pads index 0 with the default shop, which may be null.
        $shops = collect($this->repo->getShops($merchant->id))
            ->filter()
            ->map(fn ($s) => [
                'id'      => $s->id,
                'name'    => $s->name,
                'phone'   => $s->contact_no,
                'address' => $s->address,
                'lat'     => $s->merchant_lat,
                'long'    => $s->merchant_long,
                'is_default' => (bool) ($s->default_shop ?? false),
            ])
            ->values();

        // Categories: deliveryCharges() returns the list of category_ids that have
        // a configured rate; deliveryCategories() is the [id => CategoryObj] lookup.
        $categoryMap   = $this->repo->deliveryCategories();
        $chargeableIds = collect($this->repo->deliveryCharges())->all();
        $availableCats = collect($chargeableIds)
            ->map(fn ($id) => isset($categoryMap[$id]) ? [
                'id'   => $categoryMap[$id]->id,
                'name' => $categoryMap[$id]->title ?? $categoryMap[$id]->name ?? '',
            ] : null)
            ->filter()
            ->values();

        // Delivery types: the existing Blade hardcodes 1=same_day, 2=next_day,
        // 3=sub_city, 4=outside_City and labels via the deliveryType.* lang file.
        $deliveryTypeMap = [
            'same_day'     => 1,
            'next_day'     => 2,
            'sub_city'     => 3,
            'outside_City' => 4, // matches legacy capitalization in lang file
        ];
        $deliveryTypes = collect($this->repo->deliveryTypes())
            ->map(function ($dt) use ($deliveryTypeMap) {
                $id = $deliveryTypeMap[$dt->key] ?? null;
                if (!$id) return null;
                return ['id' => $id, 'name' => __('deliveryType.' . $dt->key)];
            })
            ->filter()
            ->values();

        // Cities: surface en_name alongside name so the React side can pick per locale.
        $cities = collect($this->repo->cities())
            ->map(fn ($c) => [
                'id'   => $c->id,
                'name' => app()->getLocale() === 'ar' ? ($c->name ?: $c->en_name) : ($c->en_name ?: $c->name),
            ])
            ->values();

        $defaultShop = $shops->first();
        $codCharges  = (array) ($merchant->cod_charges ?? []);
        $fragileLiquidActive = SettingHelper('fragile_liquid_status') == \App\Enums\Status::ACTIVE;
        $fragileLiquidCharge = (float) (SettingHelper('fragile_liquid_charge') ?: 0);

        return [
            'merchant' => [
                'id'           => $merchant->id,
                'business_name'=> $merchant->business_name,
                'vat'          => (float) ($merchant->vat ?? 0),
            ],
            'cod_charges' => [
                'inside_city'  => (float) ($codCharges['inside_city']  ?? 0),
                'sub_city'     => (float) ($codCharges['sub_city']     ?? 0),
                'outside_city' => (float) ($codCharges['outside_city'] ?? 0),
            ],
            'fragile_liquid' => [
                'active' => (bool) $fragileLiquidActive,
                'charge' => $fragileLiquidCharge,
            ],
            'default_shop' => $defaultShop ? [
                'id'      => $defaultShop['id'],
                'name'    => $defaultShop['name'],
                'phone'   => $defaultShop['phone'],
                'address' => $defaultShop['address'],
                'lat'     => $defaultShop['lat'],
                'long'    => $defaultShop['long'],
            ] : null,
            'shops'         => $shops,
            'delivery_types'=> $deliveryTypes,
            'categories'    => $availableCats,
            'packagings'    => collect($this->repo->packaging())->map(fn ($p) => [
                'id'    => $p->id,
                'name'  => $p->name,
                'price' => (float) ($p->price ?? 0),
            ])->values(),
            'cities'        => $cities,
            'currency'      => settings()->currency,
            'urls' => [
                'store'             => route('merchant-panel.parcel.store'),
                'cancel'            => route('merchant-panel.parcel.index'),
                'shop_lookup'       => route('merchant-panel.parcel.merchant.shops'),
                'weight_lookup'     => route('merchant-panel.parcel.deliveryCategory.deliveryWeight'),
                'delivery_charge'   => route('merchant-panel.parcel.deliveryCharge.get'),
                'areas_by_city'     => route('merchant-panel.parcel.getAreas'),
            ],
            't' => [
                'title'              => __('Create shipment') ?: 'Create shipment',
                'dashboard'          => __('Dashboard') ?: 'Dashboard',
                'shipments'          => __('Shipments') ?: 'Shipments',
                'create'             => __('Create') ?: 'Create',
                'pickup_point'       => __('Pickup points') ?: 'Pickup point',
                'pickup_point_ph'    => __('select pickup point') ?: 'Select pickup point',
                'pickup_phone'       => __('Pickup phone') ?: 'Pickup phone',
                'pickup_address'     => __('Pickup address') ?: 'Pickup address',
                'cod'                => __('COD') ?: 'COD',
                'cod_ph'             => __('Cash amount including delivery charge') ?: 'Cash amount including delivery charge',
                'reference_number'   => __('Reference number') ?: 'Reference number',
                'reference_ph'       => __('Reference number / Order number') ?: 'Reference number / Order number',
                'category'           => __('parcel.category') ?: 'Category',
                'weight'             => __('parcel.weight') ?: 'Weight',
                'extra_weight'       => __('Extra weight') ?: 'Extra weight',
                'delivery_type'      => __('parcel.delivery_type') ?: 'Delivery type',
                'customer_name'      => __('parcel.customer_name') ?: 'Customer name',
                'customer_phone'     => __('parcel.customer_phone') ?: 'Customer phone',
                'city'               => __('City') ?: 'City',
                'city_ph'            => __('Select City') ?: 'Select city',
                'area'               => __('Area') ?: 'Area',
                'area_ph'            => __('Select Area') ?: 'Select area',
                'customer_address'   => __('parcel.customer_address') ?: 'Customer address',
                'note'               => __('parcel.note') ?: 'Note',
                'liquid_check_label' => __('parcel.liquid_check_label') ?: 'Fragile / liquid?',
                'liquid_fragile'     => __('parcel.liquid_fragile') ?: 'Liquid / fragile',
                'packaging'          => __('parcel.packaging') ?: 'Packaging',
                'parcel_bank'        => __('levels.parcel_bank') ?: 'Save to parcel bank',
                'select'             => __('menus.select') ?: 'Select',
                'save'               => __('levels.save') ?: 'Save',
                'cancel'             => __('levels.cancel') ?: 'Cancel',
                // Charge summary card
                'charge_details'     => __('parcel.charge_details') ?: 'Charge details',
                'amount'             => __('levels.amount') ?: 'Amount',
                'cash_collection'    => __('parcel.Cash_Collection') ?: 'Cash collection',
                'delivery_charge'    => __('parcel.Delivery_Charge') ?: 'Delivery charge',
                'cod_charge'         => __('reports.COD_Charge') ?: 'COD charge',
                'liquid_charge'      => __('parcel.Liquid/Fragile_Charge') ?: 'Liquid/fragile charge',
                'packaging_charge'   => __('reports.P.Charge') ?: 'Packaging charge',
                'total_charge'       => __('parcel.Total_Charge') ?: 'Total charge',
                'vat'                => __('parcel.Vat') ?: 'VAT',
                'net_payable'        => __('parcel.Net_Payable') ?: 'Net payable',
                'current_payable'    => __('parcel.Current_payable') ?: 'Current payable',
            ],
        ];
    }

    public function store(StoreRequest $request)
    {
        
 
        if(Auth::user()->merchant->wallet_use_activation == Status::ACTIVE):
            $chargeDetails = json_decode($request->chargeDetails);
            if($chargeDetails->totalDeliveryChargeAmount > Auth::user()->merchant->wallet_balance):
                Toastr::error('You are low on balance. Please recharge', 'Error');
                return redirect()->route('merchant-panel.my.wallet.index');
            endif; 
        endif;

        
        $userID = Auth::user()->id;
        $merchant = $this->currentMerchant();
        if($this->repo->store($request,$merchant->id)){
            Toastr::success(__('parcel.added_msg'),__('message.success'));
            return redirect()->route('merchant-panel.parcel.index');
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    public function duplicateStore(StoreRequest $request)
    {
        $userID = Auth::user()->id;
        $merchant = $this->currentMerchant();
        if($this->repo->duplicateStore($request,$merchant->id)){
            Toastr::success(__('parcel.added_msg'),__('message.success'));
            return redirect()->route('merchant-panel.parcel.index');
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }


    public function show($id)
    {
        //
    }

    // Parcel logs
    public function logs($id)
    {
        $parcel = $this->repo->get($id);
        if (! $parcel) {
            abort(404);
        }
        $merchant = $this->currentMerchant();
        if (! $merchant || (int) $parcel->merchant_id !== (int) $merchant->id) {
            abort(403);
        }

        $events = \App\Models\Backend\ParcelEvent::where('parcel_id', $id)
            ->with(['hub', 'deliveryMan.user', 'pickupman.user', 'user'])
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return Inertia::render('Merchant/Parcel/Logs', [
            'parcel' => [
                'id'           => $parcel->id,
                'tracking_id'  => $parcel->tracking_id,
                'status'       => (int) $parcel->status,
                'status_label' => \App\Support\ParcelStatusHelper::label((int) $parcel->status),
                'status_color' => \App\Support\ParcelStatusHelper::color((int) $parcel->status),
                'created_at'   => optional($parcel->created_at)->format('Y-m-d H:i'),
            ],
            'events' => $events->map(function ($ev) {
                $actor = optional(optional($ev)->user)->name
                    ?? optional(optional($ev->deliveryMan)->user)->name
                    ?? optional(optional($ev->pickupman)->user)->name;
                $statusId = (int) $ev->parcel_status;
                return [
                    'id'         => $ev->id,
                    'status'     => $statusId,
                    'label'      => \App\Support\ParcelStatusHelper::label($statusId),
                    'color'      => $ev->cancel_parcel_id ? 'red' : \App\Support\ParcelStatusHelper::color($statusId),
                    'actor'      => $actor,
                    'hub'        => optional($ev->hub)->name,
                    'note'       => $ev->note,
                    'created_at' => optional($ev->created_at)->format('Y-m-d H:i:s'),
                ];
            })->values(),
            'urls' => [
                'index'   => route('merchant-panel.parcel.index'),
                'details' => route('merchant-panel.parcel.details', $parcel->id),
            ],
            't' => [
                'title'        => __('parcel.parcel_logs') ?: 'Shipment logs',
                'title_index'  => __('parcel.title') ?: 'Parcels',
                'back_to_list' => __('levels.back') ?: 'Back',
                'view_details' => __('levels.view') ?: 'View details',
                'no_events'    => __('levels.no_data_found') ?: 'No events recorded yet.',
                'actor'        => __('levels.user') ?: 'Actor',
                'hub'          => __('levels.hub') ?: 'Hub',
                'note'         => __('levels.note') ?: 'Note',
                'status'       => __('levels.status') ?: 'Status',
                'when'         => __('levels.date') ?: 'When',
            ],
        ]);
    }

    // Parcel duplicate
    public function duplicate($id)
    {
        $parcel          = $this->repo->get($id);
        $merchant        = $this->merchant->get($parcel->merchant_id);
        $shops           = $this->shop->all($parcel->merchant_id);
        $deliveryCharges = DeliveryCharge::companywise()->where('category_id',$parcel->category_id)->get();

        $deliveryCategories      = $this->repo->deliveryCategories();
        $deliveryCategoryCharges = $this->repo->deliveryCharges();

        $packagings    = $this->repo->packaging();
        $deliveryTypes = $this->repo->deliveryTypes();
        return view('backend.merchant_panel.parcel.duplicate',compact('parcel','merchant','deliveryTypes','shops','deliveryCategories','deliveryCategoryCharges','deliveryCharges','packagings'));
    }

    // Parcel details
    public function details($id)
    {
        $parcel = $this->repo->details($id);
        if (! $parcel) {
            abort(404);
        }

        // Tenant-isolation guard: a merchant can only see their own parcels.
        $merchant = $this->currentMerchant();
        if (! $merchant || (int) $parcel->merchant_id !== (int) $merchant->id) {
            abort(403);
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

        $attachments = [];
        foreach ($parcel->images ?? [] as $img) {
            $attachments[] = [
                'url'     => $img->image_url,
                'label'   => ucfirst(str_replace('_', ' ', $img->type)),
                'date'    => optional($img->created_at)->format('Y-m-d H:i'),
                'contain' => false,
            ];
        }
        foreach ($events as $ev) {
            if ($ev->delivered_image) {
                $attachments[] = [
                    'url'     => static_asset($ev->delivered_image),
                    'label'   => __('Delivered Photo'),
                    'date'    => optional($ev->created_at)->format('Y-m-d H:i'),
                    'contain' => false,
                ];
            }
            if ($ev->signature_image) {
                $attachments[] = [
                    'url'     => static_asset($ev->signature_image),
                    'label'   => __('Signature'),
                    'date'    => optional($ev->created_at)->format('Y-m-d H:i'),
                    'contain' => true,
                ];
            }
        }

        $senderName  = optional($parcel->merchant)->business_name ?? optional($parcel->merchantShop)->name;
        $senderPhone = $parcel->pickup_phone ?: optional(optional($parcel->merchant)->user)->mobile;

        $isPending = (int) $parcel->status === \App\Enums\ParcelStatus::PENDING;

        return Inertia::render('Merchant/Parcel/Details', [
            'parcel' => [
                'id'                    => $parcel->id,
                'tracking_id'           => $parcel->tracking_id,
                'invoice_no'            => $parcel->invoice_no,
                'status'                => (int) $parcel->status,
                'status_label'          => \App\Support\ParcelStatusHelper::label((int) $parcel->status),
                'status_color'          => \App\Support\ParcelStatusHelper::color((int) $parcel->status),
                'created_at'            => optional($parcel->created_at)->format('Y-m-d H:i'),
                'updated_at'            => optional($parcel->updated_at)->format('Y-m-d H:i'),
                'cod_amount'            => (float) ($parcel->cod_amount ?? 0),
                'cash_collection'       => (float) ($parcel->cash_collection ?? 0),
                'selling_price'         => (float) ($parcel->selling_price ?? 0),
                'total_delivery_amount' => (float) ($parcel->total_delivery_amount ?? 0),
                'vat_amount'            => (float) ($parcel->vat_amount ?? 0),
                'current_payable'       => (float) ($parcel->current_payable ?? 0),
                'weight'                => $parcel->weight,
                'weight_unit'           => optional($parcel->deliveryCategory)->title,
                'delivery_type'         => $parcel->delivery_type_name ?? null,
                'city'                  => optional($parcel->city)->name,
                'area'                  => optional($parcel->area)->name,
                'hub'                   => optional($parcel->hub)->name,
                'priority'              => (int) ($parcel->priority_type_id ?? 2),
                'note'                  => $parcel->note,
                'attempts'              => (int) ($parcel->number_of_attempts ?? 0),
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
                    'id'         => $ev->id,
                    'status'     => $statusId,
                    'label'      => \App\Support\ParcelStatusHelper::label($statusId),
                    'color'      => $ev->cancel_parcel_id ? 'red' : \App\Support\ParcelStatusHelper::color($statusId),
                    'actor'      => $actor,
                    'hub'        => optional($ev->hub)->name,
                    'note'       => $ev->note,
                    'created_at' => optional($ev->created_at)->format('Y-m-d H:i:s'),
                ];
            })->values(),
            'currency'    => settings()->currency,
            'permissions' => [
                // Merchants can edit/delete only while the parcel is still Pending.
                'edit'   => $isPending,
                'delete' => $isPending,
            ],
            'urls' => [
                'index'  => route('merchant-panel.parcel.index'),
                'edit'   => route('merchant-panel.parcel.edit',   $parcel->id),
                'logs'   => route('merchant-panel.parcel.logs',   $parcel->id),
                'delete' => route('merchant-panel.parcel.delete', $parcel->id),
            ],
            't' => $this->parcelDetailsLabels(),
        ]);
    }

    private function parcelDetailsLabels(): array
    {
        return [
            'title'             => __('parcel.parcel_details') ?: 'Shipment details',
            'title_index'       => __('parcel.title') ?: 'Parcels',
            'sender_info'       => __('parcel.sender_info') ?: 'Sender',
            'recipient_info'    => __('parcel.recipient_info') ?: 'Recipient',
            'attachment'        => __('levels.attachment') ?: 'Attachments',
            'no_attachments'    => __('levels.no_data_found') ?: 'No attachments',
            'edit'              => __('levels.edit') ?: 'Edit',
            'logs'              => __('parcel.logs') ?: 'Logs',
            'tracking_id'       => __('parcel.tracking_id') ?: 'Tracking ID',
            'booking_date'      => __('levels.booking_date') ?: 'Booking date',
            'cod'               => __('levels.cod') ?: 'COD',
            'cash_collection'   => __('parcel.cash_collection') ?: 'Cash collection',
            'price'             => __('levels.price') ?: 'Price',
            'invoice'           => __('invoice.invoice') ?: 'Invoice',
            'weight'            => __('levels.weight') ?: 'Weight',
            'delivery_type'     => __('levels.delivery_type') ?: 'Delivery type',
            'city'              => __('levels.city') ?: 'City',
            'area'              => __('levels.area') ?: 'Area',
            'hub'               => __('levels.hub') ?: 'Hub',
            'note'              => __('levels.note') ?: 'Note',
            'status'            => __('levels.status') ?: 'Status',
            'timeline'          => __('parcel.timeline') ?: 'Timeline',
            'finance'           => __('parcel.finance') ?: 'Finance',
            'delivery'          => __('parcel.delivery_charge') ?: 'Delivery',
            'vat'               => __('parcel.vat') ?: 'VAT',
            'net_payable'       => __('parcel.Net_Payable') ?: 'Net payable',
            'shipment_creation' => __('parcel.parcel_create') ?: 'Shipment created',
            'attempts'          => __('parcel.attempts') ?: 'Delivery attempts',
            'back_to_list'      => __('levels.back') ?: 'Back',
            'priority_high'     => __('parcel.priority_high') ?: 'High',
        ];
    }

    public function edit($id)
    {
        $userID   = Auth::user()->id;
        $parcel   = $this->repo->get($id);
        $merchant = $this->currentMerchant();

        if (! $parcel) { abort(404); }
        if (! $merchant || (int) $parcel->merchant_id !== (int) $merchant->id) { abort(403); }

        // Merchants can only edit while the parcel is still Pending.
        if ($parcel->status != ParcelStatus::PENDING) {
            Toastr::error(__('parcel.edit_error_message'), __('message.error'));
            return redirect()->route('merchant-panel.parcel.index');
        }

        // Reuse the shared form-prop builder, then layer edit-mode additions:
        // the source parcel for prefill, a PUT-target store URL, and edit title.
        $base = $this->buildParcelFormProps($merchant);
        $base['parcel'] = [
            'id'                    => $parcel->id,
            'tracking_id'           => $parcel->tracking_id,
            'shop_id'               => $parcel->merchant_shop_id,
            'pickup_phone'          => $parcel->pickup_phone,
            'pickup_address'        => $parcel->pickup_address,
            'pickup_lat'            => $parcel->pickup_lat,
            'pickup_long'           => $parcel->pickup_long,
            'cash_collection'       => $parcel->cash_collection,
            'invoice_no'            => $parcel->invoice_no,
            'category_id'           => $parcel->category_id,
            'weight'                => $parcel->weight,
            'extra_weight'          => 0,
            'delivery_type_id'      => $parcel->delivery_type_id,
            'customer_name'         => $parcel->customer_name,
            'customer_phone'        => $parcel->customer_phone,
            'customer_address'      => $parcel->customer_address,
            'customer_lat'          => $parcel->customer_lat,
            'customer_long'         => $parcel->customer_long,
            'city_id'               => $parcel->city_id,
            'area_id'               => $parcel->area_id,
            'note'                  => $parcel->note,
            'packaging_id'          => $parcel->packaging_id,
            'parcel_bank'           => $parcel->parcel_bank === 'on',
            'liquid_fragile_amount' => $parcel->liquid_fragile_amount,
            'cod_charge'            => $parcel->cod_charge ?? 0,
            'vat'                   => $parcel->vat ?? 0,
        ];
        $base['mode'] = 'edit';
        $base['urls']['update'] = route('merchant-panel.parcel.update', $parcel->id);
        $base['t']['edit']       = __('parcel.parcel_edit') ?: 'Edit shipment';
        $base['t']['edit_title'] = (__('parcel.parcel_edit') ?: 'Edit shipment') . ' · ' . $parcel->tracking_id;

        return Inertia::render('Merchant/Parcel/Create', $base);
    }


    // Parcel update
    public function statusUpdate($id, $status_id)
    {
        $this->repo->statusUpdate($id, $status_id);
        Toastr::success(__('parcel.update_msg'),__('message.success'));
        return redirect()->route('merchant-panel.parcel.index');
    }

    public function update(StoreRequest $request,$id)
    {
        $userID = Auth::user()->id;
        if($this->repo->update($id, $request,$userID)){
            Toastr::success(__('parcel.update_msg'),__('message.success'));
            return redirect()->route('merchant-panel.parcel.index');
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }


    public function destroy($id)
    {
        $userID = Auth::user()->id;
        $parcel = $this->repo->get($id);
        if($parcel->status == ParcelStatus::PENDING){
            $this->repo->delete($id,$userID);
            Toastr::success(__('parcel.delete_msg'),__('message.success'));
            return back();
        }
        else{
            Toastr::error(__('parcel.delete_error_message'),__('message.error'));
            return redirect()->route('merchant-panel.parcel.index');
        }
    }

    public function parcelImportExport()
    {
        return Inertia::render('Merchant/Parcel/Import', [
            'step' => 'upload',
            'urls' => $this->importUrls(),
            't'    => $this->importLabels(),
        ]);
    }

    /**
     * Shared URL bundle for the import wizard. Both the upload step and the
     * preview step render the same Inertia page, so they share these.
     */
    private function importUrls(): array
    {
        return [
            'dashboard'      => route('dashboard.index'),
            'parcel_index'   => route('merchant-panel.parcel.index'),
            'upload'         => route('merchant-panel.m_parcel.file-import.post'),
            'confirm'        => route('merchant-panel.parcel.import.confirm'),
            'cancel'         => route('merchant-panel.parcel.parcel-import'),
            'sample'         => route('exports.shipment-template'),
        ];
    }

    private function importLabels(): array
    {
        return [
            'title'             => __('parcel.import_with_preview') ?: 'Import shipments',
            'dashboard'         => __('levels.dashboard') ?: 'Dashboard',
            'parcels'           => __('parcel.title') ?: 'Parcels',
            'parcel_import'     => __('parcel.parcel_import') ?: 'Shipment import',
            'sample'            => __('parcel.sample') ?: 'Download sample',
            'import'            => __('parcel.import') ?: 'Import',
            'note'              => __('merchantImport.note') ?: 'Please check this before importing your file.',
            'tip_01'            => __('merchantImport.01') ?: 'Uploaded file type must be xlsx.',
            'choose_file'       => __('levels.choose_file') ?: 'Choose file',
            'no_file'           => __('levels.no_data_found') ?: 'No file chosen',
            'preview_title'     => __('parcel.preview_title') ?: 'Preview before confirming',
            'total_rows'        => __('parcel.total_rows') ?: 'Total rows',
            'showing_first'     => __('parcel.showing_first') ?: 'Showing first',
            'rows_only'         => __('parcel.rows_only') ?: 'rows',
            'expected_columns'  => __('parcel.expected_columns') ?: 'Expected column order',
            'confirm_import'    => __('parcel.confirm_import') ?: 'Confirm import',
            'back'              => __('levels.back') ?: 'Back',
            'validation_errors' => __('parcel.validation_errors') ?: 'Validation errors',
            'row_number'        => __('parcel.row_number') ?: 'Row',
        ];
    }
    
    
 
public function m_parcelImport(Request $request)
{
    // ✅ Validate the uploaded file
    $request->validate([
        'file' => 'required|mimes:xlsx,xls,csv|max:5120',
    ]);

    // 📁 Store the uploaded file temporarily
    $path = $request->file('file')->store('imports');

    // 📊 Read the first sheet from the Excel file
    $sheet = Excel::toCollection(null, Storage::path($path))->first();

    if (blank($sheet) || $sheet->count() === 0) {
        return back()->withErrors(['file' => 'The uploaded file is empty or invalid.']);
    }

    // 1️⃣ Get raw headers (may include `*` for required fields)
    $rawHeaders = collect($sheet->first() ?? [])
        ->map(fn($h) => trim((string) $h))
        ->values();

    if ($rawHeaders->isEmpty()) {
        return back()->withErrors(['file' => 'No header row was found in the Excel file.']);
    }

    // 2️⃣ Detect required columns (any column name ending with `*`)
    $required = $rawHeaders
        ->filter(fn($h) => preg_match('/\*\s*$/u', $h))
        ->map(fn($h) => preg_replace('/\s*\*\s*$/u', '', $h))
        ->values();

    // 3️⃣ Clean headers (remove the `*`)
    $headers = $rawHeaders->map(fn($h) => preg_replace('/\s*\*\s*$/u', '', $h))->values();

    // 4️⃣ Define the expected columns (in the correct order, without `*`)
    $expected = collect([
        'Pickup point',
        'Pickup phone',
        'Pickup address',
        'COD',
        'Reference number',
        'Weight',
        'Customer Name',
        'Customer Phone',
        'City',
        'Area',
        'Customer Address',
        'Note',
    ]);

    // ❗ Check for missing columns
    $missing = $expected->diff($headers);
    if ($missing->isNotEmpty()) {
        return back()->withErrors([
            'file' => 'The Excel file is missing the following columns: ' . $missing->implode(', ')
        ]);
    }

    // 5️⃣ Remove empty rows
    $rows = $sheet->slice(1)->values()->filter(function ($row) {
        return collect($row)->filter(fn($c) => !is_null($c) && trim((string)$c) !== '')->isNotEmpty();
    })->values();

    // 6️⃣ Convert Arabic digits to English (if any)
    $toEnglishDigits = function ($value) {
        if ($value === null) return $value;
        $str = (string) $value;
        $nums = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩','٫','٬','،'];
        $rep  = ['0','1','2','3','4','5','6','7','8','9','.','',''];
        return str_replace($nums, $rep, $str);
    };

    // 7️⃣ Validate row data
    $errors = [];
    $normalizedRows = [];

    foreach ($rows as $index => $row) {
        // Combine column names with row values
        $assoc = $headers->combine($row)->map(function ($v, $k) use ($toEnglishDigits) {
            if (in_array($k, ['COD', 'Weight'])) {
                return $toEnglishDigits($v);
            }
            return is_string($v) ? trim($v) : $v;
        });

        $rowNumber = $index + 2; // +2 because row 1 is the header

        // ✅ Check required fields
        foreach ($required as $col) {
            $val = $assoc->get($col);
            if (is_null($val) || trim((string) $val) === '') {
                $errors[] = "Row {$rowNumber}: The field '{$col}' is required.";
            }
        }

        // ✅ Validate numeric fields
        if ($assoc->has('COD') && trim((string)$assoc['COD']) !== '' && !is_numeric($assoc['COD'])) {
            $errors[] = "Row {$rowNumber}: The field 'COD' must be a numeric value.";
        }
        if ($assoc->has('Weight') && trim((string)$assoc['Weight']) !== '' && !is_numeric($assoc['Weight'])) {
            $errors[] = "Row {$rowNumber}: The field 'Weight' must be a numeric value.";
        }

        // ✅ Basic phone validation (allows + and digits)
        foreach (['Pickup phone', 'Customer Phone'] as $phoneCol) {
            if ($assoc->has($phoneCol) && trim((string)$assoc[$phoneCol]) !== '') {
                $p = (string) $assoc[$phoneCol];
                if (!preg_match('/^\+?\d{7,20}$/', preg_replace('/\s+/', '', $p))) {
                    $errors[] = "Row {$rowNumber}: The phone number in '{$phoneCol}' is invalid.";
                }
            }
        }

        $normalizedRows[] = $assoc;
    }

    // ❗ Return errors if any
    if (!empty($errors)) {
        return back()->withErrors($errors);
    }

    // 📦 Store data in session for the confirmation step
    session([
        'm_import.path'    => $path,
        'm_import.headers' => $headers,
        'm_import.total'   => count($normalizedRows),
        // 'm_import.rows'  => collect($normalizedRows)->toArray(), // optional
    ]);

    // 📊 Preview the first 100 rows
    $previewRows = collect($normalizedRows)->take(100);

    return Inertia::render('Merchant/Parcel/Import', [
        'step'         => 'preview',
        'headers'      => $headers->values()->all(),
        'preview_rows' => $previewRows->map(fn ($r) => $r->values()->all())->values()->all(),
        'total_rows'   => count($normalizedRows),
        'preview_count' => $previewRows->count(),
        'expected'     => $expected->values()->all(),
        'urls'         => $this->importUrls(),
        't'            => $this->importLabels(),
    ]);
}




public function m_parcelImportConfirm(Request $request)
{
    $path = session('m_import.path');
    
 
    if (!$path || !Storage::exists($path)) {
        Toastr::error('انتهت صلاحية جلسة المعاينة أو الملف غير موجود. أعد الرفع.', 'خطأ');
        return back();
    }

     try {
        // نفّذ الاستيراد الفعلي بالاعتماد على كلاس الاستيراد الخاص بك
        // إن كنت تفضّل ParcelImport بدلاً من MParcelImport استبدله هنا:
        $import = new MParcelImport();
        $import->import(Storage::path($path));

        // تنظيف جلسة المعاينة والملف المؤقت
        Storage::delete($path);
        session()->forget(['m_import.path', 'm_import.headers', 'm_import.total']);

        Toastr::success(__('parcel.added_msg'), __('message.success'));
        return redirect()->route('merchant-panel.parcel.index');

    } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
        $failures = $e->failures();
        $importErrors = [];
        foreach ($failures as $failure) {
            $importErrors[$failure->row()][] = $failure->errors()[0] ?? 'خطأ غير معروف في الصف';
        }
        return $importErrors;
        // لا نحذف الملف هنا كي يقدر يعيد التأكيد بعد التصحيح إن لزم
        return back()->with('importErrors', $importErrors);
    } catch (\Throwable $th) {


        Toastr::error('حدث خطأ أثناء الاستيراد: ' . $th->getMessage(), 'خطأ');
        return back();
    }
}

public function showImportForm()
{
    return view('backend.merchant_panel.parcel.import_form');
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
            $failures = $e->failures();
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
        return redirect()->route('merchant-panel.parcel.index');
    }

    public function merchantShops(Request $request)
    {
        if (request()->ajax()) {
            if ($request->id && $request->shop == 'true') {
                $merchantShops = [];
                $merchantShop = MerchantShops::where(['merchant_id'=>$request->id,'default_shop'=>Status::ACTIVE])->first();
                $merchantShops[]= $merchantShop;
                $merchantShopArray = MerchantShops::where(['merchant_id'=>$request->id,'default_shop'=>Status::INACTIVE])->get();
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
        if (request()->ajax()) {
            if ($request->merchant_id && $request->category_id && $request->weight !='0' && $request->delivery_type_id) {
                $charges = MerchantDeliveryCharge::where(['merchant_id'=>$request->merchant_id,'category_id'=>$request->category_id,'weight'=>$request->weight])->first();
                if (blank($charges)) {
                    $charges = DeliveryCharge::companywise()->where(['category_id'=>$request->category_id])->first();
                }
            } else {
                $charges = MerchantDeliveryCharge::where(['merchant_id'=>$request->merchant_id,'category_id'=>$request->category_id,'weight'=>$request->weight])->first();
                if (blank($charges)) {
                    $charges = DeliveryCharge::companywise()->where(['category_id'=>$request->category_id])->first();
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
                    return view('backend.merchant_panel.parcel.deliveryWeight', compact('deliveryCharges'));
                }
                return '';
            }
        }
        return '';
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
    
    
    
    /**
     * AJAX: list the logged-in merchant's WMS products for the picker on
     * /merchant-panel/parcel/create. Returns has_fulfillment + products.
     * Merchant is fixed by Auth::user()->merchant, so no IDOR risk here.
     */
    public function myProducts(Request $request)
    {
        $merchant = optional(\Illuminate\Support\Facades\Auth::user())->merchant;
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

        public function getAreasByCity(Request $request)
{
    $areas = Area::where('city_id', $request->city_id)
                 ->where('is_active', 1)
                 ->orderBy('sorting')
                 ->get();

    return response()->json($areas);
}
}
