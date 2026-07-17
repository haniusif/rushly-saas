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

        // Every "drill-in" link is built through this closure so a stale
        // route cache or a rename in web.php can't crash the page — it
        // just falls back to a plain path string.
        $safe = function (string $name, array $params = [], string $fallback = ''): string {
            try { return route($name, $params); }
            catch (\Throwable $e) { return $fallback ?: url('/'); }
        };
        $parcelIndex  = $safe('parcel.index',  [], '/admin/parcel/index');
        $parcelFilter = fn (array $q) => $this->buildFilterUrl(
            $safe('parcel.filter', [], '/admin/parcel/filter'),
            $q
        );

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

        // Per-KPI drill-in link. Sitting alongside the count keeps JSX
        // trivial ({kpis.today_shipments_url}) and lets us centralize
        // the query-param shape here.
        $kpi_urls = [
            'today_shipments' => $parcelIndex,
            'ofd'             => $parcelFilter(['parcel_status' => ParcelStatus::DELIVERY_MAN_ASSIGN]),
            'delivered_today' => $parcelFilter(['parcel_status' => ParcelStatus::DELIVERED]),
            'pending'         => $parcelFilter(['parcel_status' => ParcelStatus::PENDING]),
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

        // -------- Today's status breakdown (donut chart) --------
        // Buckets today's parcels into five ops-facing groups so the donut
        // reads at a glance: still to pick up / in-flight / done / bounced.
        // "Other" catches everything not in the four primary states so the
        // total always equals kpis.today_shipments (no missing slice).
        $cancelled  = [
            ParcelStatus::PICKUP_ASSIGN_CANCEL, ParcelStatus::RECEIVED_BY_PICKUP_MAN_CANCEL,
            ParcelStatus::RECEIVED_WAREHOUSE_CANCEL, ParcelStatus::DELIVERY_MAN_ASSIGN_CANCEL,
            ParcelStatus::DELIVERY_RE_SCHEDULE_CANCEL, ParcelStatus::TRANSFER_TO_HUB_CANCEL,
            ParcelStatus::RECEIVED_BY_HUB_CANCEL, ParcelStatus::DELIVERED_CANCEL,
            ParcelStatus::PICKUP_RE_SCHEDULE_CANCEL, ParcelStatus::CANCELLED,
        ];
        $returned   = [
            ParcelStatus::RETURN_RECEIVED_BY_MERCHANT, ParcelStatus::RETURN_TO_COURIER,
            ParcelStatus::RETURN_ASSIGN_TO_MERCHANT,
        ];
        $todayByStatus = Parcel::companywise()
            ->selectRaw('status, COUNT(*) as c')
            ->whereBetween('created_at', [$todayFrom, $todayTo])
            ->groupBy('status')
            ->pluck('c', 'status');

        $cancelledCount = 0; foreach ($cancelled as $s) $cancelledCount += (int) ($todayByStatus[$s] ?? 0);
        $returnedCount  = 0; foreach ($returned  as $s) $returnedCount  += (int) ($todayByStatus[$s] ?? 0);
        $delivered      = (int) ($todayByStatus[ParcelStatus::DELIVERED] ?? 0);
        $ofd            = (int) ($todayByStatus[ParcelStatus::DELIVERY_MAN_ASSIGN] ?? 0);
        $pending        = (int) ($todayByStatus[ParcelStatus::PENDING] ?? 0);
        $accounted      = $delivered + $ofd + $pending + $cancelledCount + $returnedCount;
        $other          = max(0, $kpis['today_shipments'] - $accounted);

        // Order matters — donut renders slices in this sequence going
        // clockwise from 12 o'clock. Each slice carries its own drill-in
        // link so a click jumps straight to the filtered shipments list.
        $statusBreakdown = [
            ['key' => 'delivered', 'label' => 'Delivered',         'value' => $delivered,      'url' => $parcelFilter(['parcel_status' => ParcelStatus::DELIVERED])],
            ['key' => 'ofd',       'label' => 'Out for delivery',  'value' => $ofd,            'url' => $parcelFilter(['parcel_status' => ParcelStatus::DELIVERY_MAN_ASSIGN])],
            ['key' => 'pending',   'label' => 'Pending pickup',    'value' => $pending,        'url' => $parcelFilter(['parcel_status' => ParcelStatus::PENDING])],
            ['key' => 'returned',  'label' => 'Returned',          'value' => $returnedCount,  'url' => $parcelFilter(['parcel_status' => ParcelStatus::RETURN_RECEIVED_BY_MERCHANT])],
            ['key' => 'cancelled', 'label' => 'Cancelled',         'value' => $cancelledCount, 'url' => $parcelFilter(['parcel_status' => ParcelStatus::CANCELLED])],
            ['key' => 'other',     'label' => 'In transit',        'value' => $other,          'url' => $parcelIndex],
        ];

        // -------- Weekly success ring (small gauge) --------
        // Success = delivered this week / (delivered + returned + cancelled)
        // over the same seven-day window. Zero-denominator guard leaves it
        // at 0.0 so the ring shows an empty state instead of NaN.
        $weekTotals = Parcel::companywise()
            ->selectRaw('status, COUNT(*) as c')
            ->whereBetween('updated_at', [$weekFrom, $todayTo])
            ->groupBy('status')
            ->pluck('c', 'status');
        $weekDelivered = (int) ($weekTotals[ParcelStatus::DELIVERED] ?? 0);
        $weekReturned  = 0; foreach ($returned  as $s) $weekReturned  += (int) ($weekTotals[$s] ?? 0);
        $weekCancelled = 0; foreach ($cancelled as $s) $weekCancelled += (int) ($weekTotals[$s] ?? 0);
        $weekTerminal  = $weekDelivered + $weekReturned + $weekCancelled;
        $weekSuccess   = $weekTerminal > 0 ? round(($weekDelivered / $weekTerminal) * 100, 1) : 0.0;
        $weekCreated   = (int) Parcel::companywise()->whereBetween('created_at', [$weekFrom, $todayTo])->count();

        // TEMP: leaderboards are unwindowed (lifetime) for now — revert by
        // re-adding `->whereBetween('parcels.created_at', [$monthFrom, $monthTo])`
        // to each correlated subquery below. $monthFrom / $monthTo are still
        // resolved because the Deliveryman perf card + subtitle need them.
        $monthFrom = $now->startOfMonth();
        $monthTo   = $now->endOfMonth();

        // -------- Top 10 merchants (all time) --------------------
        // Eager-loads the logo relation so getLogoUrlAttribute() has the
        // upload without an N+1 fetch per row. `logo_id` must be in the
        // outer select for the belongsTo relation to resolve.
        $topMerchants = Merchant::companywise()
            ->with('logo:id,original')
            ->select('id', 'business_name', 'logo_id')
            ->selectSub(
                Parcel::query()->selectRaw('COUNT(*)')
                    ->whereColumn('parcels.merchant_id', 'merchants.id'),
                'shipments'
            )
            ->orderByDesc('shipments')
            ->limit(10)
            ->get()
            ->filter(fn ($m) => (int) $m->shipments > 0)
            ->map(fn ($m) => [
                'id'        => (int) $m->id,
                'name'      => (string) ($m->business_name ?: 'Merchant #'.$m->id),
                'logo_url'  => $m->logo_url, // null when the merchant hasn't uploaded a logo
                'shipments' => (int) $m->shipments,
                'url'       => $safe('merchant.view', ['id' => $m->id], '/admin/merchant/view/'.$m->id),
            ])
            ->values();

        // -------- Top 10 hubs (all time) -------------------------
        $topHubs = Hub::companywise()
            ->select('id', 'name')
            ->selectSub(
                Parcel::query()->selectRaw('COUNT(*)')
                    ->whereColumn('parcels.hub_id', 'hubs.id'),
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
                'url'       => $safe('hub.view', ['id' => $h->id], '/admin/hub/view/'.$h->id),
            ])
            ->values();

        // -------- Top 10 cities (all time) -----------------------
        // Cities are shared reference data (no company_id), so we
        // subquery Parcel::companywise() to keep the count tenant-scoped.
        // en_name wins over name — matches the create form's label pick.
        $topCities = City::query()
            ->select('id', 'name', 'en_name')
            ->selectSub(
                Parcel::companywise()->selectRaw('COUNT(*)')
                    ->whereColumn('parcels.city_id', 'cities.id'),
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
                // No city-detail page — click drills into the shipments
                // list filtered by this city.
                'url'       => $parcelFilter(['city_id' => $c->id]),
            ])
            ->values();

        // -------- Top 10 deliverymen (current month) --------------
        // Both counts are windowed to [monthFrom .. monthTo] by
        // parcel_events.created_at, so a driver is judged on the parcels
        // they were handed *this* month and how many of those are now
        // delivered.
        $topDeliverymen = DeliveryMan::companywise()
            // Eager-load user + upload so getImageAttribute() renders each
            // driver's avatar without an N+1 fetch. user.image_id feeds the
            // belongsTo, upload.original supplies the actual asset path.
            ->with(['user' => fn ($q) => $q->select('id', 'name', 'image_id')->with('upload:id,original')])
            ->select('delivery_man.id', 'delivery_man.user_id')
            ->selectSub(
                // TEMP all-time — see the "TEMP" note above the leaderboards.
                ParcelEvent::query()
                    ->selectRaw('COUNT(DISTINCT parcel_id)')
                    ->whereColumn('parcel_events.delivery_man_id', 'delivery_man.id'),
                'assigned'
            )
            ->selectSub(
                ParcelEvent::query()
                    ->selectRaw('COUNT(DISTINCT parcel_events.parcel_id)')
                    ->join('parcels', 'parcels.id', '=', 'parcel_events.parcel_id')
                    ->whereColumn('parcel_events.delivery_man_id', 'delivery_man.id')
                    ->where('parcels.status', ParcelStatus::DELIVERED),
                'delivered'
            )
            ->orderByDesc('assigned')
            ->limit(10)
            ->get()
            ->filter(fn ($d) => (int) $d->assigned > 0) // hide drivers with no activity this month
            ->map(function ($d) use ($safe) {
                $assigned  = (int) $d->assigned;
                $delivered = (int) $d->delivered;
                return [
                    'id'          => (int) $d->id,
                    'name'        => (string) (optional($d->user)->name ?: 'Deliveryman #'.$d->id),
                    // Only surface a real photo — the accessor otherwise
                    // returns images/default/user.png for every driver,
                    // making the whole column look identical.
                    'photo_url'   => optional($d->user)->image_id ? optional($d->user)->image : null,
                    'assigned'    => $assigned,
                    'delivered'   => $delivered,
                    'performance' => $assigned > 0 ? round(($delivered / $assigned) * 100, 1) : 0.0,
                    // No dedicated driver-view page — jump to edit which
                    // shows the driver's identity + all their assignments.
                    'url'         => $safe('deliveryman.edit', ['id' => $d->id], '/admin/deliveryman/edit/'.$d->id),
                ];
            })
            ->values();

        // -------- Today's OFD per hub ---------------------------
        // Parcels currently Out For Delivery (status=DELIVERY_MAN_ASSIGN)
        // that were also created today. Grouped per hub so an ops lead
        // can see which hubs are under active load right now.
        $ofdByHub = Hub::companywise()
            ->select('id', 'name')
            ->selectSub(
                Parcel::query()->selectRaw('COUNT(*)')
                    ->whereColumn('parcels.hub_id', 'hubs.id')
                    ->whereBetween('parcels.created_at', [$todayFrom, $todayTo])
                    ->where('parcels.status', ParcelStatus::DELIVERY_MAN_ASSIGN),
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
                'url'       => $safe('hub.view', ['id' => $h->id], '/admin/hub/view/'.$h->id),
            ])
            ->values();

        return Inertia::render('Admin/Summary/Index', [
            'kpis'           => $kpis,
            'kpi_urls'       => $kpi_urls,
            'trend'          => $trend,
            'status_breakdown' => $statusBreakdown,
            'week_summary'   => [
                'success_rate' => $weekSuccess,
                'delivered'    => $weekDelivered,
                'created'      => $weekCreated,
            ],
            'ofd_by_hub'     => $ofdByHub,
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
                // New chart blocks: today's status donut + weekly success ring.
                'status_donut_title'    => __('summary.status_donut_title')    ?: "Today's shipment mix",
                'status_donut_subtitle' => __('summary.status_donut_subtitle') ?: 'By current status',
                'status_donut_empty'    => __('summary.status_donut_empty')    ?: 'No shipments created today yet.',
                'status_delivered'      => __('summary.status_delivered')      ?: 'Delivered',
                'status_ofd'            => __('summary.status_ofd')            ?: 'Out for delivery',
                'status_pending'        => __('summary.status_pending')        ?: 'Pending pickup',
                'status_returned'       => __('summary.status_returned')       ?: 'Returned',
                'status_cancelled'      => __('summary.status_cancelled')      ?: 'Cancelled',
                'status_other'          => __('summary.status_other')          ?: 'In transit',
                'week_ring_title'       => __('summary.week_ring_title')       ?: 'Success this week',
                'week_ring_subtitle'    => __('summary.week_ring_subtitle')    ?: 'Delivered vs terminal, last 7 days',
                'week_ring_delivered'   => __('summary.week_ring_delivered')   ?: 'delivered',
                'week_ring_created'     => __('summary.week_ring_created')     ?: 'created',
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
                'ofd_by_hub_title'    => __('summary.ofd_by_hub_title') ?: 'OFD by hub',
                'ofd_by_hub_subtitle' => __('summary.ofd_by_hub_subtitle') ?: 'Today, currently out for delivery',
                'ofd_by_hub_col_name' => __('summary.ofd_by_hub_col_name') ?: 'Hub',
                'ofd_by_hub_col_qty'  => __('summary.ofd_by_hub_col_qty')  ?: 'OFD',
                'ofd_by_hub_empty'    => __('summary.ofd_by_hub_empty')    ?: 'No parcels out for delivery today.',
                // TEMP: while leaderboards are unwindowed above, the shared
                // caption reads "All time" instead of "Current month: <name>".
                // Swap this back to the trans() call when the window returns.
                'current_month'  => __('summary.all_time') ?: 'All time',
                'top_deliverymen_title'    => __('summary.top_deliverymen_title') ?: 'Deliveryman performance',
                'top_deliverymen_col_name' => __('summary.top_deliverymen_col_name') ?: 'Deliveryman',
                'top_deliverymen_col_assigned'  => __('summary.top_deliverymen_col_assigned')  ?: 'Assigned',
                'top_deliverymen_col_delivered' => __('summary.top_deliverymen_col_delivered') ?: 'Delivered',
                'top_deliverymen_col_performance' => __('summary.top_deliverymen_col_performance') ?: 'Performance',
                'top_deliverymen_empty' => __('summary.top_deliverymen_empty') ?: 'No deliverymen yet.',
            ],
        ]);
    }

    /**
     * Append a query string to a route URL, preserving any existing query
     * the resolver put in place (fallback paths from the safe closure are
     * plain strings with no query, so this handles both).
     */
    private function buildFilterUrl(string $base, array $query): string
    {
        if (empty($query)) return $base;
        $sep = str_contains($base, '?') ? '&' : '?';
        return $base . $sep . http_build_query($query);
    }
}
