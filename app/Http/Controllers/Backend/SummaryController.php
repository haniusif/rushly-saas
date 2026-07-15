<?php

namespace App\Http\Controllers\Backend;

use App\Enums\ParcelStatus;
use App\Http\Controllers\Controller;
use App\Models\Backend\Area;
use App\Models\Backend\City;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Hub;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\Backend\ParcelEvent;
use App\Models\Backend\Payment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * "Home" summary for tenant admins. Lighter than /dashboard — a small
 * grid of high-signal KPIs, seven-day parcel trend, quick actions, and
 * a live recent-parcels feed. Rendered inline via Inertia.
 */
class SummaryController extends Controller
{
    public function index()
    {
        $now       = CarbonImmutable::now();
        $todayFrom = $now->startOfDay();
        $todayTo   = $now->endOfDay();
        $weekFrom  = $now->subDays(6)->startOfDay();

        // -------- KPI cards --------------------------------------
        // "In transit" = anywhere between "received at warehouse" and "delivery-man assigned"
        // (i.e., anything moving that isn't yet delivered or returned).
        $inTransitStates = [
            ParcelStatus::PICKUP_ASSIGN,
            ParcelStatus::RECEIVED_BY_PICKUP_MAN,
            ParcelStatus::RECEIVED_WAREHOUSE,
            ParcelStatus::TRANSFER_TO_HUB,
            ParcelStatus::RECEIVED_BY_HUB,
            ParcelStatus::DELIVERY_MAN_ASSIGN,
            ParcelStatus::ASSIGN_TO_3PL,
        ];

        $kpis = [
            'today_shipments' => (int) Parcel::companywise()
                ->whereBetween('created_at', [$todayFrom, $todayTo])->count(),
            'in_transit'      => (int) Parcel::companywise()
                ->whereIn('status', $inTransitStates)->count(),
            'delivered_today' => (int) Parcel::companywise()
                ->where('status', ParcelStatus::DELIVERED)
                ->whereBetween('updated_at', [$todayFrom, $todayTo])->count(),
            'pending'         => (int) Parcel::companywise()
                ->where('status', ParcelStatus::PENDING)->count(),
        ];

        // -------- 7-day trend (created_at bucketed by day) -------
        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = $now->subDays($i)->startOfDay();
            $days[$d->format('Y-m-d')] = [
                'label'   => $d->format('D'),
                'iso'     => $d->format('Y-m-d'),
                'created' => 0,
                'delivered' => 0,
            ];
        }
        $created = Parcel::companywise()
            ->selectRaw("DATE(created_at) as d, COUNT(*) as c")
            ->whereBetween('created_at', [$weekFrom, $todayTo])
            ->groupBy('d')->pluck('c', 'd');
        foreach ($created as $d => $c) {
            if (isset($days[$d])) $days[$d]['created'] = (int) $c;
        }
        $delivered = Parcel::companywise()
            ->selectRaw("DATE(updated_at) as d, COUNT(*) as c")
            ->where('status', ParcelStatus::DELIVERED)
            ->whereBetween('updated_at', [$weekFrom, $todayTo])
            ->groupBy('d')->pluck('c', 'd');
        foreach ($delivered as $d => $c) {
            if (isset($days[$d])) $days[$d]['delivered'] = (int) $c;
        }
        $trend = array_values($days);

        // -------- Recent parcels ---------------------------------
        $recent = Parcel::companywise()
            ->with(['merchant:id,business_name'])
            ->orderByDesc('id')->limit(8)
            ->get()
            ->map(fn ($p) => [
                'id'            => $p->id,
                'tracking_id'   => (string) $p->tracking_id,
                'customer_name' => (string) $p->customer_name,
                'merchant'      => optional($p->merchant)->business_name,
                'status'        => (int) $p->status,
                'status_label'  => $this->statusLabel((int) $p->status),
                'created_at'    => optional($p->created_at)->diffForHumans(),
            ]);

        // -------- Roster tiles -----------------------------------
        $totals = [
            'merchants'    => (int) Merchant::companywise()->count(),
            'deliverymen'  => (int) DeliveryMan::companywise()->count(),
            'pending_pay'  => (float) Payment::companywise()
                ->where('status', 1)->sum(DB::raw('COALESCE(amount, 0)')),
        ];

        // -------- Top 10 merchants by shipment volume ------------
        // Correlated subquery keeps the FROM clause a single table so the
        // Merchant::companywise() scope's `company_id` filter stays
        // unambiguous. Merchants with no shipments still show up (0) if
        // they land in the top-N by lifetime — sorted desc by count.
        $topMerchants = Merchant::companywise()
            ->select('id', 'business_name')
            ->selectSub(
                Parcel::query()->selectRaw('COUNT(*)')->whereColumn('parcels.merchant_id', 'merchants.id'),
                'shipments'
            )
            ->orderByDesc('shipments')
            ->limit(10)
            ->get()
            ->map(fn ($m) => [
                'id'        => (int) $m->id,
                'name'      => (string) ($m->business_name ?: 'Merchant #'.$m->id),
                'shipments' => (int) $m->shipments,
            ])
            ->values();

        // -------- Top 10 hubs by shipment volume -----------------
        // Same shape as topMerchants; joins on parcels.hub_id (the current
        // hub the parcel sits at, not first_hub_id / transfer_hub_id).
        $topHubs = Hub::companywise()
            ->select('id', 'name')
            ->selectSub(
                Parcel::query()->selectRaw('COUNT(*)')->whereColumn('parcels.hub_id', 'hubs.id'),
                'shipments'
            )
            ->orderByDesc('shipments')
            ->limit(10)
            ->get()
            ->map(fn ($h) => [
                'id'        => (int) $h->id,
                'name'      => (string) ($h->name ?: 'Hub #'.$h->id),
                'shipments' => (int) $h->shipments,
            ])
            ->values();

        // -------- Top 10 cities & areas by shipment volume -------
        // Cities/areas are shared reference data (no company_id), so we
        // subquery Parcel::companywise() to keep the count tenant-scoped.
        // en_name wins over name when both are set — matches the create
        // form's label pick from ParcelController::create().
        $topCities = City::query()
            ->select('id', 'name', 'en_name')
            ->selectSub(
                Parcel::companywise()->selectRaw('COUNT(*)')->whereColumn('parcels.city_id', 'cities.id'),
                'shipments'
            )
            ->orderByDesc('shipments')
            ->limit(10)
            ->get()
            ->map(fn ($c) => [
                'id'        => (int) $c->id,
                'name'      => (string) ($c->en_name ?: $c->name ?: 'City #'.$c->id),
                'shipments' => (int) $c->shipments,
            ])
            ->values();

        // -------- Top 10 deliverymen for CURRENT MONTH ------------
        // Both counts are windowed to [start of month .. end of month]
        // by parcel_events.created_at, so a driver is judged on the
        // parcels they were handed *this* month and how many of those
        // are now delivered.
        $monthFrom = $now->startOfMonth();
        $monthTo   = $now->endOfMonth();

        $topDeliverymen = DeliveryMan::companywise()
            ->with('user:id,name')
            ->select('delivery_man.id', 'delivery_man.user_id')
            ->selectSub(
                ParcelEvent::query()
                    ->selectRaw('COUNT(DISTINCT parcel_id)')
                    ->whereColumn('parcel_events.delivery_man_id', 'delivery_man.id')
                    ->whereBetween('parcel_events.created_at', [$monthFrom, $monthTo]),
                'assigned'
            )
            ->selectSub(
                ParcelEvent::query()
                    ->selectRaw('COUNT(DISTINCT parcel_events.parcel_id)')
                    ->join('parcels', 'parcels.id', '=', 'parcel_events.parcel_id')
                    ->whereColumn('parcel_events.delivery_man_id', 'delivery_man.id')
                    ->whereBetween('parcel_events.created_at', [$monthFrom, $monthTo])
                    ->where('parcels.status', ParcelStatus::DELIVERED),
                'delivered'
            )
            ->orderByDesc('assigned')
            ->limit(10)
            ->get()
            ->filter(fn ($d) => (int) $d->assigned > 0) // hide drivers with no activity this month
            ->map(function ($d) {
                $assigned  = (int) $d->assigned;
                $delivered = (int) $d->delivered;
                return [
                    'id'          => (int) $d->id,
                    'name'        => (string) (optional($d->user)->name ?: 'Deliveryman #'.$d->id),
                    'assigned'    => $assigned,
                    'delivered'   => $delivered,
                    'performance' => $assigned > 0 ? round(($delivered / $assigned) * 100, 1) : 0.0,
                ];
            })
            ->values();

        $topAreas = Area::query()
            ->select('id', 'name', 'en_name')
            ->selectSub(
                Parcel::companywise()->selectRaw('COUNT(*)')->whereColumn('parcels.area_id', 'areas.id'),
                'shipments'
            )
            ->orderByDesc('shipments')
            ->limit(10)
            ->get()
            ->map(fn ($a) => [
                'id'        => (int) $a->id,
                'name'      => (string) ($a->en_name ?: $a->name ?: 'Area #'.$a->id),
                'shipments' => (int) $a->shipments,
            ])
            ->values();

        return Inertia::render('Admin/Summary/Index', [
            'currency'       => (string) (settings()->currency ?? ''),
            'kpis'           => $kpis,
            'trend'          => $trend,
            'recent'         => $recent,
            'totals'         => $totals,
            'top_merchants'  => $topMerchants,
            'top_hubs'       => $topHubs,
            'top_cities'     => $topCities,
            'top_areas'      => $topAreas,
            'top_deliverymen'=> $topDeliverymen,
            'urls' => [
                'list_parcels'    => $this->safeRoute('parcel.index', '/admin/parcel/index'),
            ],
            't' => [
                'title'           => __('summary.title') ?: 'Summary',
                'kpi_today'       => __('summary.kpi_today')       ?: "Today's shipments",
                'kpi_in_transit'  => __('summary.kpi_in_transit')  ?: 'In transit',
                'kpi_delivered'   => __('summary.kpi_delivered')   ?: 'Delivered today',
                'kpi_pending'     => __('summary.kpi_pending')     ?: 'Pending pickup',
                'seven_day_title' => __('summary.seven_day_title') ?: 'Last 7 days',
                'recent_title'    => __('summary.recent_title')    ?: 'Recent shipments',
                'list_parcels'    => __('summary.list_parcels')    ?: 'View all shipments',
                'roster_title'    => __('summary.roster_title')    ?: 'Team & payouts',
                'roster_merchants'   => __('summary.roster_merchants')   ?: 'Merchants',
                'roster_deliverymen' => __('summary.roster_deliverymen') ?: 'Deliverymen',
                'roster_pending_pay' => __('summary.roster_pending_pay') ?: 'Pending payouts',
                'legend_created'   => __('summary.legend_created')   ?: 'Created',
                'legend_delivered' => __('summary.legend_delivered') ?: 'Delivered',
                'no_recent'       => __('summary.no_recent') ?: 'No shipments yet — create your first one.',
                'top_merchants_title'    => __('summary.top_merchants_title') ?: 'Top merchants by shipments',
                'top_merchants_col_name' => __('summary.top_merchants_col_name') ?: 'Merchant',
                'top_merchants_col_qty'  => __('summary.top_merchants_col_qty')  ?: 'Shipments',
                'top_merchants_empty'    => __('summary.top_merchants_empty')    ?: 'No merchants yet.',
                'top_hubs_title'    => __('summary.top_hubs_title') ?: 'Top hubs by shipments',
                'top_hubs_col_name' => __('summary.top_hubs_col_name') ?: 'Hub',
                'top_hubs_empty'    => __('summary.top_hubs_empty')    ?: 'No hubs yet.',
                'top_cities_title'    => __('summary.top_cities_title') ?: 'Top cities by shipments',
                'top_cities_col_name' => __('summary.top_cities_col_name') ?: 'City',
                'top_cities_empty'    => __('summary.top_cities_empty')    ?: 'No cities yet.',
                'top_areas_title'     => __('summary.top_areas_title')  ?: 'Top areas by shipments',
                'top_areas_col_name'  => __('summary.top_areas_col_name') ?: 'Area',
                'top_areas_empty'     => __('summary.top_areas_empty')  ?: 'No areas yet.',
                'top_deliverymen_title'    => __('summary.top_deliverymen_title') ?: 'Deliveryman performance',
                // translatedFormat honors app locale, so Arabic gets Arabic month names.
                'top_deliverymen_subtitle' => trans('summary.top_deliverymen_subtitle', [
                    'month' => $monthFrom->locale(app()->getLocale())->translatedFormat('F Y'),
                ]),
                'top_deliverymen_col_name' => __('summary.top_deliverymen_col_name') ?: 'Deliveryman',
                'top_deliverymen_col_assigned'  => __('summary.top_deliverymen_col_assigned')  ?: 'Assigned',
                'top_deliverymen_col_delivered' => __('summary.top_deliverymen_col_delivered') ?: 'Delivered',
                'top_deliverymen_col_performance' => __('summary.top_deliverymen_col_performance') ?: 'Performance',
                'top_deliverymen_empty' => __('summary.top_deliverymen_empty') ?: 'No deliverymen yet.',
            ],
        ]);
    }

    private function statusLabel(int $status): string
    {
        static $labels = [
            ParcelStatus::PENDING                     => 'Pending',
            ParcelStatus::PICKUP_ASSIGN               => 'Pickup assigned',
            ParcelStatus::RECEIVED_BY_PICKUP_MAN      => 'Picked up',
            ParcelStatus::RECEIVED_WAREHOUSE          => 'At warehouse',
            ParcelStatus::TRANSFER_TO_HUB             => 'To hub',
            ParcelStatus::RECEIVED_BY_HUB             => 'At hub',
            ParcelStatus::DELIVERY_MAN_ASSIGN         => 'Out for delivery',
            ParcelStatus::DELIVERED                   => 'Delivered',
            ParcelStatus::PARTIAL_DELIVERED           => 'Partial delivery',
            ParcelStatus::RETURN_WAREHOUSE            => 'Return',
            ParcelStatus::RETURN_RECEIVED_BY_MERCHANT => 'Returned to merchant',
            ParcelStatus::ASSIGN_TO_3PL               => '3PL',
        ];
        return $labels[$status] ?? 'Status '.$status;
    }

    private function safeRoute(string $name, string $fallback): string
    {
        try { return route($name); } catch (\Throwable $e) { return url($fallback); }
    }
}
