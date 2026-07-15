<?php

namespace App\Http\Controllers\Backend;

use App\Enums\ParcelStatus;
use App\Http\Controllers\Controller;
use App\Models\Backend\City;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Hub;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\Backend\ParcelEvent;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
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

        // -------- KPI cards (all scoped to TODAY) ----------------
        // Every KPI is the count of parcels created between start-of-day
        // and end-of-day, further filtered by status where applicable.
        // Keeps the four cards internally consistent — they all describe
        // today's shipments in different states.
        $kpis = [
            'today_shipments' => (int) Parcel::companywise()
                ->whereBetween('created_at', [$todayFrom, $todayTo])->count(),
            // OFD = Out For Delivery (status = DELIVERY_MAN_ASSIGN).
            'ofd'             => (int) Parcel::companywise()
                ->whereBetween('created_at', [$todayFrom, $todayTo])
                ->where('status', ParcelStatus::DELIVERY_MAN_ASSIGN)->count(),
            'delivered_today' => (int) Parcel::companywise()
                ->whereBetween('created_at', [$todayFrom, $todayTo])
                ->where('status', ParcelStatus::DELIVERED)->count(),
            'pending'         => (int) Parcel::companywise()
                ->whereBetween('created_at', [$todayFrom, $todayTo])
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

        // Every leaderboard below is windowed to the current month
        // (parcels.created_at). Hoisted once so the bounds are consistent
        // across merchants / hubs / cities / areas / deliverymen.
        $monthFrom = $now->startOfMonth();
        $monthTo   = $now->endOfMonth();

        // -------- Top 10 merchants (current month) ---------------
        // Correlated subquery keeps the FROM clause a single table so the
        // Merchant::companywise() scope's `company_id` filter stays
        // unambiguous. Rows with zero shipments this month are filtered
        // out below so the card shows only merchants with real activity.
        $topMerchants = Merchant::companywise()
            ->select('id', 'business_name')
            ->selectSub(
                Parcel::query()->selectRaw('COUNT(*)')
                    ->whereColumn('parcels.merchant_id', 'merchants.id')
                    ->whereBetween('parcels.created_at', [$monthFrom, $monthTo]),
                'shipments'
            )
            ->orderByDesc('shipments')
            ->limit(10)
            ->get()
            ->filter(fn ($m) => (int) $m->shipments > 0)
            ->map(fn ($m) => [
                'id'        => (int) $m->id,
                'name'      => (string) ($m->business_name ?: 'Merchant #'.$m->id),
                'shipments' => (int) $m->shipments,
            ])
            ->values();

        // -------- Top 10 hubs (current month) --------------------
        // Joins on parcels.hub_id (the current hub the parcel sits at,
        // not first_hub_id / transfer_hub_id).
        $topHubs = Hub::companywise()
            ->select('id', 'name')
            ->selectSub(
                Parcel::query()->selectRaw('COUNT(*)')
                    ->whereColumn('parcels.hub_id', 'hubs.id')
                    ->whereBetween('parcels.created_at', [$monthFrom, $monthTo]),
                'shipments'
            )
            ->orderByDesc('shipments')
            ->limit(10)
            ->get()
            ->filter(fn ($h) => (int) $h->shipments > 0)
            ->map(fn ($h) => [
                'id'        => (int) $h->id,
                'name'      => (string) ($h->name ?: 'Hub #'.$h->id),
                'shipments' => (int) $h->shipments,
            ])
            ->values();

        // -------- Top 10 cities & areas (current month) ----------
        // Cities/areas are shared reference data (no company_id), so we
        // subquery Parcel::companywise() to keep the count tenant-scoped.
        // en_name wins over name when both are set — matches the create
        // form's label pick from ParcelController::create().
        $topCities = City::query()
            ->select('id', 'name', 'en_name')
            ->selectSub(
                Parcel::companywise()->selectRaw('COUNT(*)')
                    ->whereColumn('parcels.city_id', 'cities.id')
                    ->whereBetween('parcels.created_at', [$monthFrom, $monthTo]),
                'shipments'
            )
            ->orderByDesc('shipments')
            ->limit(10)
            ->get()
            ->filter(fn ($c) => (int) $c->shipments > 0)
            ->map(fn ($c) => [
                'id'        => (int) $c->id,
                'name'      => (string) ($c->en_name ?: $c->name ?: 'City #'.$c->id),
                'shipments' => (int) $c->shipments,
            ])
            ->values();

        // -------- Top 10 deliverymen (current month) --------------
        // Both counts are windowed to [monthFrom .. monthTo] by
        // parcel_events.created_at, so a driver is judged on the parcels
        // they were handed *this* month and how many of those are now
        // delivered.
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

        return Inertia::render('Admin/Summary/Index', [
            'kpis'           => $kpis,
            'trend'          => $trend,
            'top_merchants'  => $topMerchants,
            'top_hubs'       => $topHubs,
            'top_cities'     => $topCities,
            'top_deliverymen'=> $topDeliverymen,
            't' => [
                'title'           => __('summary.title') ?: 'Summary',
                'kpi_today'       => __('summary.kpi_today')       ?: "Today's shipments",
                'kpi_ofd'         => __('summary.kpi_ofd')         ?: 'OFD',
                'kpi_delivered'   => __('summary.kpi_delivered')   ?: 'Delivered today',
                'kpi_pending'     => __('summary.kpi_pending')     ?: 'Pending pickup',
                'seven_day_title' => __('summary.seven_day_title') ?: 'Last 7 days',
                'legend_created'   => __('summary.legend_created')   ?: 'Created',
                'legend_delivered' => __('summary.legend_delivered') ?: 'Delivered',
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
                // Shared "Current month: <name>" caption used under every
                // leaderboard title. translatedFormat honors app locale so
                // Arabic gets Arabic month names.
                'current_month'  => trans('summary.current_month', [
                    'month' => $monthFrom->locale(app()->getLocale())->translatedFormat('F Y'),
                ]),
                'top_deliverymen_title'    => __('summary.top_deliverymen_title') ?: 'Deliveryman performance',
                'top_deliverymen_col_name' => __('summary.top_deliverymen_col_name') ?: 'Deliveryman',
                'top_deliverymen_col_assigned'  => __('summary.top_deliverymen_col_assigned')  ?: 'Assigned',
                'top_deliverymen_col_delivered' => __('summary.top_deliverymen_col_delivered') ?: 'Delivered',
                'top_deliverymen_col_performance' => __('summary.top_deliverymen_col_performance') ?: 'Performance',
                'top_deliverymen_empty' => __('summary.top_deliverymen_empty') ?: 'No deliverymen yet.',
            ],
        ]);
    }

}
