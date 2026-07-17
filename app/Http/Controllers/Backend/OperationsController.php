<?php

namespace App\Http\Controllers\Backend;

use App\Enums\ApprovalStatus;
use App\Enums\ParcelStatus;
use App\Enums\SupportStatus;
use App\Http\Controllers\Controller;
use App\Models\Backend\City;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Fleet\FleetVehicle;
use App\Models\Backend\Hub;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\Backend\ParcelEvent;
use App\Models\Backend\ParcelRating;
use App\Models\Backend\Payment;
use App\Models\Backend\Support;
use App\Models\Backend\Wms\WmsProduct;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

/**
 * Executive Operations Command Center for tenant admins. Consolidates
 * shipment ops, driver/hub load, financials, alerts, and a live activity
 * feed into a single decision-support surface. Every KPI is paired with a
 * point-of-comparison (yesterday / last week) so leadership can spot
 * trend shifts without opening a subpage. Deliberately conservative on
 * chart libraries — every visualization is inline SVG so the initial
 * paint stays fast and there's no third-party bundle penalty.
 */
class OperationsController extends Controller
{
    public function index()
    {
        $now         = CarbonImmutable::now();
        $todayFrom   = $now->startOfDay();
        $todayTo     = $now->endOfDay();
        $yesterday   = $now->subDay();
        $ydayFrom    = $yesterday->startOfDay();
        $ydayTo      = $yesterday->endOfDay();
        $weekFrom    = $now->subDays(6)->startOfDay();
        $prevWeekFrom= $now->subDays(13)->startOfDay();
        $prevWeekTo  = $now->subDays(7)->endOfDay();
        $monthFrom   = $now->startOfMonth();
        $monthTo     = $now->endOfMonth();

        // -------- KPI grid: today's operational core --------
        // Every card exposes a `delta` % vs the equivalent prior window so
        // leadership can read trend without a mental compare pass.
        $shipToday       = (int) Parcel::companywise()->whereBetween('created_at', [$todayFrom, $todayTo])->count();
        $shipYday        = (int) Parcel::companywise()->whereBetween('created_at', [$ydayFrom, $ydayTo])->count();
        $shipWeek        = (int) Parcel::companywise()->whereBetween('created_at', [$weekFrom, $todayTo])->count();
        $shipPrevWeek    = (int) Parcel::companywise()->whereBetween('created_at', [$prevWeekFrom, $prevWeekTo])->count();
        $shipMonth       = (int) Parcel::companywise()->whereBetween('created_at', [$monthFrom, $monthTo])->count();

        $delivToday      = (int) Parcel::companywise()->whereBetween('created_at', [$todayFrom, $todayTo])->where('status', ParcelStatus::DELIVERED)->count();
        $delivYday       = (int) Parcel::companywise()->whereBetween('created_at', [$ydayFrom, $ydayTo])->where('status', ParcelStatus::DELIVERED)->count();

        $ofdToday        = (int) Parcel::companywise()->whereBetween('created_at', [$todayFrom, $todayTo])->where('status', ParcelStatus::DELIVERY_MAN_ASSIGN)->count();
        $pendingToday    = (int) Parcel::companywise()->whereBetween('created_at', [$todayFrom, $todayTo])->where('status', ParcelStatus::PENDING)->count();

        // "Cancelled" collapses every *_CANCEL terminal state.
        $cancelledStatuses = [
            ParcelStatus::PICKUP_ASSIGN_CANCEL, ParcelStatus::RECEIVED_BY_PICKUP_MAN_CANCEL,
            ParcelStatus::RECEIVED_WAREHOUSE_CANCEL, ParcelStatus::DELIVERY_MAN_ASSIGN_CANCEL,
            ParcelStatus::DELIVERY_RE_SCHEDULE_CANCEL, ParcelStatus::TRANSFER_TO_HUB_CANCEL,
            ParcelStatus::RECEIVED_BY_HUB_CANCEL, ParcelStatus::DELIVERED_CANCEL,
            ParcelStatus::PICKUP_RE_SCHEDULE_CANCEL, ParcelStatus::CANCELLED,
        ];
        $cancelToday     = (int) Parcel::companywise()->whereBetween('created_at', [$todayFrom, $todayTo])->whereIn('status', $cancelledStatuses)->count();

        $returnStatuses  = [ParcelStatus::RETURN_RECEIVED_BY_MERCHANT, ParcelStatus::RETURN_TO_COURIER, ParcelStatus::RETURN_ASSIGN_TO_MERCHANT];
        $returnedToday   = (int) Parcel::companywise()->whereBetween('created_at', [$todayFrom, $todayTo])->whereIn('status', $returnStatuses)->count();

        $failedToday     = (int) Parcel::companywise()->whereBetween('created_at', [$todayFrom, $todayTo])->where('status', ParcelStatus::DELIVERY_RE_SCHEDULE)->count();

        // Success rate is delivered / (delivered + returned + cancelled) so
        // it isn't diluted by parcels still in progress; a simple
        // delivered / created ratio understates health early in the day
        // when most parcels haven't reached a terminal state yet.
        $terminalToday   = $delivToday + $returnedToday + $cancelToday;
        $successRate     = $terminalToday > 0 ? round(($delivToday / $terminalToday) * 100, 1) : 0.0;

        // COD collected/pending: pull from payments table if available,
        // otherwise fall back to cod_amount on delivered/pending parcels.
        $codCollected    = (float) Parcel::companywise()
            ->whereBetween('updated_at', [$todayFrom, $todayTo])
            ->where('status', ParcelStatus::DELIVERED)
            ->sum('cod_amount');
        $codPending      = (float) Parcel::companywise()
            ->whereIn('status', [ParcelStatus::DELIVERY_MAN_ASSIGN, ParcelStatus::RECEIVED_BY_HUB, ParcelStatus::TRANSFER_TO_HUB])
            ->sum('cod_amount');
        $revenueToday    = (float) Parcel::companywise()
            ->whereBetween('updated_at', [$todayFrom, $todayTo])
            ->where('status', ParcelStatus::DELIVERED)
            ->sum('total_delivery_amount');
        $revenueMonth    = (float) Parcel::companywise()
            ->whereBetween('updated_at', [$monthFrom, $monthTo])
            ->where('status', ParcelStatus::DELIVERED)
            ->sum('total_delivery_amount');

        // Driver posture: total active accounts vs those currently holding
        // at least one non-terminal parcel. Idle = active - busy.
        $totalDrivers    = (int) DeliveryMan::companywise()->count();
        // Parcels table has no direct driver FK on this schema — the
        // driver ↔ parcel link lives on parcel_events. A driver is
        // "busy" if any of their assigned parcels are still non-terminal.
        $busyDrivers     = (int) DeliveryMan::companywise()
            ->whereIn('id', function ($q) {
                $q->select('parcel_events.delivery_man_id')
                  ->from('parcel_events')
                  ->join('parcels', 'parcels.id', '=', 'parcel_events.parcel_id')
                  ->whereIn('parcels.status', [ParcelStatus::DELIVERY_MAN_ASSIGN, ParcelStatus::RECEIVED_BY_HUB]);
            })
            ->count();
        $idleDrivers     = max(0, $totalDrivers - $busyDrivers);

        // Fleet vehicles — this table may be feature-gated, so wrap in a
        // try/catch and swallow the exception if the schema isn't present.
        try {
            $vehiclesActive = (int) FleetVehicle::query()->count();
        } catch (\Throwable $e) { $vehiclesActive = 0; }

        // Tickets — count if the support table exists on this tenant.
        try {
            $openTickets = Schema::hasTable('supports')
                ? (int) Support::companywise()->where('status', SupportStatus::OPEN)->count()
                : 0;
        } catch (\Throwable $e) { $openTickets = 0; }

        // Inventory posture — total SKUs + low-stock/OOS (if WMS live).
        try {
            $totalSkus = Schema::hasTable('wms_products') ? (int) WmsProduct::companywise()->count() : 0;
            $lowStock  = Schema::hasTable('wms_products')
                ? (int) WmsProduct::companywise()->where('stock_on_hand', '<=', DB::raw('COALESCE(reorder_level, 5)'))->where('stock_on_hand', '>', 0)->count()
                : 0;
            $oosSkus   = Schema::hasTable('wms_products') ? (int) WmsProduct::companywise()->where('stock_on_hand', '<=', 0)->count() : 0;
        } catch (\Throwable $e) { $totalSkus = $lowStock = $oosSkus = 0; }

        // Merchant + city counters (all-time; the leaderboard section
        // shows the per-tenant breakdown).
        $merchantCount   = (int) Merchant::companywise()->count();
        $hubCount        = (int) Hub::companywise()->count();

        $kpis = [
            // Operations
            ['key' => 'ship_today',     'group' => 'ops',     'label' => 'Today shipments',       'value' => $shipToday,   'compare' => $shipYday,      'compare_label' => 'yesterday'],
            ['key' => 'delivered',      'group' => 'ops',     'label' => 'Delivered today',       'value' => $delivToday,  'compare' => $delivYday,     'compare_label' => 'yesterday', 'tone' => 'success'],
            ['key' => 'ofd',            'group' => 'ops',     'label' => 'Out for delivery',      'value' => $ofdToday,    'compare' => null,           'tone' => 'info'],
            ['key' => 'pending',        'group' => 'ops',     'label' => 'Pending pickup',        'value' => $pendingToday,'compare' => null,           'tone' => 'warning'],
            ['key' => 'cancelled',      'group' => 'ops',     'label' => 'Cancelled today',       'value' => $cancelToday, 'compare' => null,           'tone' => 'danger'],
            ['key' => 'returned',       'group' => 'ops',     'label' => 'Returned today',        'value' => $returnedToday,'compare' => null,          'tone' => 'warning'],
            ['key' => 'failed',         'group' => 'ops',     'label' => 'Failed today',          'value' => $failedToday, 'compare' => null,           'tone' => 'danger'],
            ['key' => 'success_rate',   'group' => 'ops',     'label' => 'Success rate',          'value' => $successRate, 'compare' => null,           'tone' => 'success', 'format' => 'percent'],
            // Volume
            ['key' => 'ship_week',      'group' => 'volume',  'label' => 'This week',             'value' => $shipWeek,    'compare' => $shipPrevWeek,  'compare_label' => 'prev 7d'],
            ['key' => 'ship_month',     'group' => 'volume',  'label' => 'This month',            'value' => $shipMonth,   'compare' => null],
            // Finance
            ['key' => 'revenue_today',  'group' => 'finance', 'label' => 'Revenue today',         'value' => $revenueToday,'compare' => null,           'format' => 'money', 'tone' => 'success'],
            ['key' => 'revenue_month',  'group' => 'finance', 'label' => 'Revenue this month',    'value' => $revenueMonth,'compare' => null,           'format' => 'money'],
            ['key' => 'cod_collected',  'group' => 'finance', 'label' => 'COD collected today',   'value' => $codCollected,'compare' => null,           'format' => 'money'],
            ['key' => 'cod_pending',    'group' => 'finance', 'label' => 'COD pending',           'value' => $codPending,  'compare' => null,           'format' => 'money', 'tone' => 'warning'],
            // Team
            ['key' => 'busy_drivers',   'group' => 'team',    'label' => 'Drivers on delivery',   'value' => $busyDrivers, 'compare' => $totalDrivers,  'compare_label' => 'of total'],
            ['key' => 'idle_drivers',   'group' => 'team',    'label' => 'Idle drivers',          'value' => $idleDrivers, 'compare' => null,           'tone' => 'warning'],
            ['key' => 'vehicles',       'group' => 'team',    'label' => 'Fleet vehicles',        'value' => $vehiclesActive,'compare' => null],
            ['key' => 'open_tickets',   'group' => 'team',    'label' => 'Open tickets',          'value' => $openTickets, 'compare' => null,           'tone' => $openTickets > 5 ? 'danger' : 'info'],
            // Catalog (only shown if WMS present)
            ['key' => 'skus',           'group' => 'catalog', 'label' => 'Total SKUs',            'value' => $totalSkus,   'compare' => null],
            ['key' => 'low_stock',      'group' => 'catalog', 'label' => 'Low stock',             'value' => $lowStock,    'compare' => null,           'tone' => 'warning'],
            ['key' => 'oos',            'group' => 'catalog', 'label' => 'Out of stock',          'value' => $oosSkus,     'compare' => null,           'tone' => 'danger'],
            ['key' => 'merchants',      'group' => 'catalog', 'label' => 'Merchants',             'value' => $merchantCount,'compare' => null],
        ];

        // -------- 14-day shipment timeline -------------
        $days = [];
        for ($i = 13; $i >= 0; $i--) {
            $d = $now->subDays($i)->startOfDay();
            $days[$d->format('Y-m-d')] = [
                'iso' => $d->format('Y-m-d'),
                'label' => $d->format('d M'),
                'created' => 0, 'delivered' => 0, 'cancelled' => 0, 'returned' => 0,
            ];
        }
        $rangeFrom = $now->subDays(13)->startOfDay();
        $agg = fn ($field, $whereStatus = null) => Parcel::companywise()
            ->selectRaw("DATE($field) as d, COUNT(*) as c")
            ->whereBetween($field, [$rangeFrom, $todayTo])
            ->when($whereStatus, fn ($q) => is_array($whereStatus) ? $q->whereIn('status', $whereStatus) : $q->where('status', $whereStatus))
            ->groupBy('d')->pluck('c', 'd');

        foreach ($agg('created_at') as $d => $c)                       { if (isset($days[$d])) $days[$d]['created']   = (int) $c; }
        foreach ($agg('updated_at', ParcelStatus::DELIVERED) as $d => $c){ if (isset($days[$d])) $days[$d]['delivered'] = (int) $c; }
        foreach ($agg('updated_at', $cancelledStatuses) as $d => $c)    { if (isset($days[$d])) $days[$d]['cancelled'] = (int) $c; }
        foreach ($agg('updated_at', $returnStatuses) as $d => $c)       { if (isset($days[$d])) $days[$d]['returned']  = (int) $c; }
        $timeline = array_values($days);

        // -------- Shipment funnel (this month) -------------
        // Each step is the cumulative count of parcels that reached AT LEAST
        // that stage. Conversion % is next / prior; drop % is (prior - next) / prior.
        $funnelBase = Parcel::companywise()->whereBetween('created_at', [$monthFrom, $monthTo]);
        $created    = (clone $funnelBase)->count();
        $pickedUp   = (clone $funnelBase)->whereNotIn('status', [ParcelStatus::PENDING, ...$cancelledStatuses])->count();
        $atHub      = (clone $funnelBase)->whereIn('status', [ParcelStatus::RECEIVED_WAREHOUSE, ParcelStatus::RECEIVED_BY_HUB, ParcelStatus::DELIVERY_MAN_ASSIGN, ParcelStatus::DELIVERED])->count();
        $assigned   = (clone $funnelBase)->whereIn('status', [ParcelStatus::DELIVERY_MAN_ASSIGN, ParcelStatus::DELIVERED])->count();
        $delivered  = (clone $funnelBase)->where('status', ParcelStatus::DELIVERED)->count();

        $funnel = [
            ['key' => 'created',   'label' => 'Created',         'value' => $created],
            ['key' => 'picked',    'label' => 'Picked up',       'value' => $pickedUp],
            ['key' => 'at_hub',    'label' => 'At hub',          'value' => $atHub],
            ['key' => 'assigned',  'label' => 'Out for delivery','value' => $assigned],
            ['key' => 'delivered', 'label' => 'Delivered',       'value' => $delivered],
        ];

        // -------- Ops health scores (gauges) -------------
        $delivHealth  = $successRate; // 0..100
        $driverHealth = $totalDrivers > 0 ? round(($busyDrivers / $totalDrivers) * 100, 1) : 0.0;
        $ratingHealth = 0.0;
        try {
            if (Schema::hasTable('parcel_ratings')) {
                $avg = ParcelRating::companywise()->where('created_at', '>=', $monthFrom)->avg('rating');
                $ratingHealth = $avg ? round(($avg / 5) * 100, 1) : 0.0;
            }
        } catch (\Throwable $e) {}
        // SLA: percent of delivered-this-month parcels where delivery_date
        // (promised) >= updated_at (actual). Rough proxy — good enough for
        // a management-facing score card.
        $slaHealth = 0.0;
        try {
            $slaBase = Parcel::companywise()->where('status', ParcelStatus::DELIVERED)->whereBetween('updated_at', [$monthFrom, $monthTo])->whereNotNull('delivery_date');
            $slaTotal = (clone $slaBase)->count();
            $slaOnTime = (clone $slaBase)->whereRaw('DATE(updated_at) <= DATE(delivery_date)')->count();
            $slaHealth = $slaTotal > 0 ? round(($slaOnTime / $slaTotal) * 100, 1) : 0.0;
        } catch (\Throwable $e) {}

        $health = [
            ['key' => 'delivery',  'label' => 'Delivery health',       'value' => $delivHealth,  'target' => 90, 'hint' => 'Delivered / (delivered + returned + cancelled), today'],
            ['key' => 'driver',    'label' => 'Driver utilization',    'value' => $driverHealth, 'target' => 70, 'hint' => 'Drivers currently on-route / total'],
            ['key' => 'sla',       'label' => 'SLA compliance',        'value' => $slaHealth,    'target' => 95, 'hint' => 'Delivered on or before promised date, this month'],
            ['key' => 'rating',    'label' => 'Customer rating',       'value' => $ratingHealth, 'target' => 80, 'hint' => 'Avg parcel rating this month, scaled 0–100'],
        ];

        // -------- Leaderboards (kept, expanded) -------------
        $topMerchants = Merchant::companywise()
            ->with('logo:id,original')
            ->select('id', 'business_name', 'logo_id')
            ->selectSub(Parcel::query()->selectRaw('COUNT(*)')->whereColumn('parcels.merchant_id', 'merchants.id'), 'shipments')
            ->selectSub(Parcel::query()->selectRaw('COUNT(*)')->whereColumn('parcels.merchant_id', 'merchants.id')->where('status', ParcelStatus::DELIVERED), 'delivered')
            ->selectSub(Parcel::query()->selectRaw('COALESCE(SUM(cash_collection),0)')->whereColumn('parcels.merchant_id', 'merchants.id')->where('status', ParcelStatus::DELIVERED), 'revenue')
            ->orderByDesc('shipments')
            ->limit(10)
            ->get()
            ->filter(fn ($m) => (int) $m->shipments > 0)
            ->map(fn ($m) => [
                'id'        => (int) $m->id,
                'name'      => (string) ($m->business_name ?: 'Merchant #'.$m->id),
                'logo_url'  => $m->logo_url,
                'shipments' => (int) $m->shipments,
                'delivered' => (int) $m->delivered,
                'success'   => $m->shipments > 0 ? round(($m->delivered / $m->shipments) * 100, 1) : 0.0,
                'revenue'   => (float) $m->revenue,
            ])
            ->values();

        $topDeliverymen = DeliveryMan::companywise()
            ->with(['user' => fn ($q) => $q->select('id', 'name', 'image_id')->with('upload:id,original')])
            ->select('delivery_man.id', 'delivery_man.user_id')
            ->selectSub(
                ParcelEvent::query()->selectRaw('COUNT(DISTINCT parcel_id)')
                    ->whereColumn('parcel_events.delivery_man_id', 'delivery_man.id'),
                'assigned'
            )
            ->selectSub(
                ParcelEvent::query()->selectRaw('COUNT(DISTINCT parcel_events.parcel_id)')
                    ->join('parcels', 'parcels.id', '=', 'parcel_events.parcel_id')
                    ->whereColumn('parcel_events.delivery_man_id', 'delivery_man.id')
                    ->where('parcels.status', ParcelStatus::DELIVERED),
                'delivered'
            )
            ->orderByDesc('assigned')
            ->limit(10)
            ->get()
            ->filter(fn ($d) => (int) $d->assigned > 0)
            ->map(function ($d) {
                $assigned  = (int) $d->assigned;
                $delivered = (int) $d->delivered;
                $rate      = $assigned > 0 ? round(($delivered / $assigned) * 100, 1) : 0.0;
                return [
                    'id'          => (int) $d->id,
                    'name'        => (string) (optional($d->user)->name ?: 'Deliveryman #'.$d->id),
                    'photo_url'   => optional($d->user)->image_id ? optional($d->user)->image : null,
                    'assigned'    => $assigned,
                    'delivered'   => $delivered,
                    'performance' => $rate,
                    // "top" gets a green pill; "attention" gets amber; the mid-band gets nothing.
                    'flag'        => $rate >= 90 ? 'top' : ($rate < 60 ? 'attention' : null),
                ];
            })
            ->values();

        // -------- Alerts center: derived from data ----------
        // Each alert must include a `link` so a click drills into the
        // module where the issue lives. Severity drives the pill color.
        $alerts = [];
        if ($ofdToday > 0) {
            $alerts[] = ['severity' => 'medium', 'title' => "{$ofdToday} parcels out for delivery", 'hint' => 'Monitor for SLA breaches before end-of-day', 'link' => url('/admin/parcel'), 'icon' => 'Truck'];
        }
        if ($pendingToday > 5) {
            $alerts[] = ['severity' => 'high',   'title' => "{$pendingToday} pickups pending",     'hint' => 'Assign to pickup team to clear backlog',     'link' => url('/admin/parcel/filter?status=' . ParcelStatus::PENDING), 'icon' => 'Clock'];
        }
        if ($oosSkus > 0) {
            $alerts[] = ['severity' => 'critical','title' => "{$oosSkus} SKUs out of stock",         'hint' => 'Review the WMS catalog + place reorder', 'link' => url('/admin/wms/products'), 'icon' => 'PackageX'];
        }
        if ($lowStock > 0) {
            $alerts[] = ['severity' => 'high',    'title' => "{$lowStock} SKUs low on stock",         'hint' => 'Consider PO before running out',         'link' => url('/admin/wms/products'), 'icon' => 'PackageMinus'];
        }
        if ($openTickets > 5) {
            $alerts[] = ['severity' => 'medium',  'title' => "{$openTickets} open support tickets",   'hint' => 'Route to support team',                    'link' => url('/admin/support'), 'icon' => 'MessageCircle'];
        }
        if ($idleDrivers > 0 && $pendingToday > 0) {
            $alerts[] = ['severity' => 'low',     'title' => "{$idleDrivers} idle drivers, {$pendingToday} pickups pending", 'hint' => 'Reassign idle drivers to clear pickup queue', 'link' => url('/admin/delivery-man'), 'icon' => 'Users'];
        }
        if ($failedToday > 0) {
            $alerts[] = ['severity' => 'high',    'title' => "{$failedToday} failed deliveries today", 'hint' => 'Investigate NDR reasons',                'link' => url('/admin/ndr'), 'icon' => 'AlertTriangle'];
        }
        // If everything's quiet, still show a "healthy" green pill so the
        // section never renders empty and looks broken.
        if (empty($alerts)) {
            $alerts[] = ['severity' => 'ok', 'title' => 'All systems normal', 'hint' => 'No operational alerts right now', 'link' => null, 'icon' => 'CheckCircle2'];
        }

        // -------- Live activity feed (recent parcel events) -------
        $activity = [];
        try {
            $activity = ParcelEvent::query()
                ->join('parcels', 'parcels.id', '=', 'parcel_events.parcel_id')
                ->where('parcels.company_id', settings()->id)
                ->select('parcel_events.id', 'parcel_events.parcel_id', 'parcel_events.status', 'parcel_events.created_at', 'parcels.tracking_id')
                ->orderByDesc('parcel_events.id')
                ->limit(15)
                ->get()
                ->map(fn ($e) => [
                    'id'          => (int) $e->id,
                    'parcel_id'   => (int) $e->parcel_id,
                    'tracking_id' => (string) $e->tracking_id,
                    'status'      => (int) $e->status,
                    'status_label'=> $this->statusLabel((int) $e->status),
                    'at_iso'      => optional($e->created_at)->toIso8601String(),
                    'at_relative' => optional($e->created_at)?->diffForHumans(),
                ])
                ->values();
        } catch (\Throwable $e) { $activity = []; }

        // -------- Top hubs by today's OFD (kept from old dashboard) ------
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
            ->limit(8)
            ->get()
            ->filter(fn ($h) => (int) $h->shipments > 0)
            ->map(fn ($h) => ['id' => (int) $h->id, 'name' => (string) $h->name, 'shipments' => (int) $h->shipments])
            ->values();

        // -------- Quick actions (deep-links only, all-perm safe) -------
        // Every href is resolved via route() through a small closure that
        // falls back to a canonical URL string if the route name isn't
        // registered in the current request context. Earlier iterations of
        // this list hardcoded URLs (/admin/parcel/bulk-upload, /admin/invoice,
        // /admin/pickup-requests) that don't exist — the real endpoints are
        // /admin/parcel/import-parcel, /admin/paid/invoice, and
        // /admin/pickup-request/regular respectively.
        $safeUrl = function (string $name, string $fallback): string {
            try { return route($name); }
            catch (\Throwable $e) { return url($fallback); }
        };
        $quickActions = [
            ['key' => 'create_shipment', 'label' => 'Create shipment',   'icon' => 'Plus',         'href' => $safeUrl('parcel.create',           '/admin/parcel/create')],
            ['key' => 'bulk_upload',     'label' => 'Bulk import',       'icon' => 'Upload',       'href' => $safeUrl('parcel.parcel-import',    '/admin/parcel/import-parcel')],
            ['key' => 'schedule_pickup', 'label' => 'Schedule pickup',   'icon' => 'CalendarClock','href' => $safeUrl('pickup.request.regular',  '/admin/pickup-request/regular')],
            ['key' => 'print_labels',    'label' => 'Print labels',      'icon' => 'Printer',      'href' => $safeUrl('parcel.index',            '/admin/parcel/index')],
            ['key' => 'ndr',             'label' => 'Review NDR',        'icon' => 'AlertTriangle','href' => $safeUrl('ndr.index',               '/admin/ndr')],
            ['key' => 'invoices',        'label' => 'Invoices',          'icon' => 'FileText',     'href' => $safeUrl('paid.invoice.index',      '/admin/paid/invoice')],
        ];

        return Inertia::render('Admin/Operations/Index', [
            'kpis'            => $kpis,
            'health'          => $health,
            'timeline'        => $timeline,
            'funnel'          => $funnel,
            'top_merchants'   => $topMerchants,
            'top_deliverymen' => $topDeliverymen,
            'ofd_by_hub'      => $ofdByHub,
            'alerts'          => $alerts,
            'activity'        => $activity,
            'quick_actions'   => $quickActions,
            'currency'        => settings()->currency ?: '$',
            'meta'            => [
                'now_iso'    => $now->toIso8601String(),
                'has_wms'    => Schema::hasTable('wms_products'),
                'has_tickets'=> Schema::hasTable('supports'),
                'has_ratings'=> Schema::hasTable('parcel_ratings'),
            ],
            't'               => [
                'title'           => 'Operations command center',
                'subtitle'        => 'Real-time view of shipments, drivers, warehouse, and finance',
                'kpi_group_ops'    => 'Operations',
                'kpi_group_volume' => 'Volume',
                'kpi_group_finance'=> 'Finance',
                'kpi_group_team'   => 'Team',
                'kpi_group_catalog'=> 'Catalog',
                'health_title'     => 'Operations health',
                'timeline_title'   => 'Shipments — last 14 days',
                'funnel_title'     => 'Shipment funnel — this month',
                'top_merchants'    => 'Top merchants',
                'top_drivers'      => 'Driver performance',
                'ofd_by_hub'       => 'Hub load — OFD today',
                'alerts'           => 'Alerts',
                'activity'         => 'Live activity',
                'quick_actions'    => 'Quick actions',
                'no_activity'      => 'No recent events',
                'no_alerts'        => 'No active alerts',
            ],
        ]);
    }

    /**
     * Compact label for the activity feed. Full status descriptions are
     * available via app/Http/Helper/Helper.php::parcelStatusName(); here
     * we keep the top-15 or so terminal-ish events short.
     */
    private function statusLabel(int $status): string
    {
        static $map = null;
        if ($map === null) {
            $map = [
                ParcelStatus::PENDING                       => 'Created',
                ParcelStatus::PICKUP_ASSIGN                 => 'Pickup assigned',
                ParcelStatus::RECEIVED_BY_PICKUP_MAN        => 'Picked up',
                ParcelStatus::RECEIVED_WAREHOUSE            => 'At warehouse',
                ParcelStatus::TRANSFER_TO_HUB               => 'To hub',
                ParcelStatus::RECEIVED_BY_HUB               => 'At hub',
                ParcelStatus::DELIVERY_MAN_ASSIGN           => 'Out for delivery',
                ParcelStatus::DELIVERED                     => 'Delivered',
                ParcelStatus::DELIVERY_RE_SCHEDULE          => 'Rescheduled',
                ParcelStatus::RETURN_RECEIVED_BY_MERCHANT   => 'Returned',
                ParcelStatus::CANCELLED                     => 'Cancelled',
            ];
        }
        return $map[$status] ?? "Status #$status";
    }
}
