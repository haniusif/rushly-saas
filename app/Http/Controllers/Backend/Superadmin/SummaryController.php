<?php

namespace App\Http\Controllers\Backend\Superadmin;

use App\Enums\ParcelStatus;
use App\Http\Controllers\Controller;
use App\Models\Backend\GeneralSettings;
use App\Models\Backend\Parcel;
use App\Models\Backend\ParcelEvent;
use App\Models\Backend\DeliveryMan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * Cross-tenant summary rendered on the central (super-admin) domain. Same
 * layout as the tenant Summary page but every query drops the companywise
 * scope so the numbers reflect the whole platform.
 */
class SummaryController extends Controller
{
    public function index()
    {
        $now       = CarbonImmutable::now();
        $todayFrom = $now->startOfDay();
        $todayTo   = $now->endOfDay();
        $weekFrom  = $now->subDays(6)->startOfDay();

        // -------- KPI cards (today, platform-wide) ----------------
        $kpis = [
            'today_shipments' => (int) Parcel::query()
                ->whereBetween('created_at', [$todayFrom, $todayTo])->count(),
            'ofd'             => (int) Parcel::query()
                ->whereBetween('created_at', [$todayFrom, $todayTo])
                ->where('status', ParcelStatus::DELIVERY_MAN_ASSIGN)->count(),
            'delivered_today' => (int) Parcel::query()
                ->whereBetween('created_at', [$todayFrom, $todayTo])
                ->where('status', ParcelStatus::DELIVERED)->count(),
            'pending'         => (int) Parcel::query()
                ->whereBetween('created_at', [$todayFrom, $todayTo])
                ->where('status', ParcelStatus::PENDING)->count(),
        ];

        // -------- 7-day trend, cross-tenant -----------------------
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
        $created = Parcel::query()
            ->selectRaw("DATE(created_at) as d, COUNT(*) as c")
            ->whereBetween('created_at', [$weekFrom, $todayTo])
            ->groupBy('d')->pluck('c', 'd');
        foreach ($created as $d => $c) if (isset($days[$d])) $days[$d]['created'] = (int) $c;

        $delivered = Parcel::query()
            ->selectRaw("DATE(updated_at) as d, COUNT(*) as c")
            ->where('status', ParcelStatus::DELIVERED)
            ->whereBetween('updated_at', [$weekFrom, $todayTo])
            ->groupBy('d')->pluck('c', 'd');
        foreach ($delivered as $d => $c) if (isset($days[$d])) $days[$d]['delivered'] = (int) $c;

        $trend = array_values($days);

        // -------- Today's OFD per tenant --------------------------
        $ofdByTenant = GeneralSettings::query()
            ->select('id', 'name')
            ->selectSub(
                Parcel::query()->selectRaw('COUNT(*)')
                    ->whereColumn('parcels.company_id', 'general_settings.id')
                    ->whereBetween('parcels.created_at', [$todayFrom, $todayTo])
                    ->where('parcels.status', ParcelStatus::DELIVERY_MAN_ASSIGN),
                'shipments'
            )
            ->orderByDesc('shipments')
            ->limit(10)
            ->get()
            ->filter(fn ($t) => (int) $t->shipments > 0)
            ->map(fn ($t) => [
                'id'        => (int) $t->id,
                'name'      => (string) ($t->name ?: 'Tenant #'.$t->id),
                'shipments' => (int) $t->shipments,
            ])
            ->values();

        // -------- Top 10 tenants by shipment volume (all time) ----
        $topTenants = GeneralSettings::query()
            ->select('id', 'name')
            ->selectSub(
                Parcel::query()->selectRaw('COUNT(*)')
                    ->whereColumn('parcels.company_id', 'general_settings.id'),
                'shipments'
            )
            ->orderByDesc('shipments')
            ->limit(10)
            ->get()
            ->filter(fn ($t) => (int) $t->shipments > 0)
            ->map(fn ($t) => [
                'id'        => (int) $t->id,
                'name'      => (string) ($t->name ?: 'Tenant #'.$t->id),
                'shipments' => (int) $t->shipments,
            ])
            ->values();

        // -------- Deliveryman performance CURRENT MONTH, cross-tenant
        $monthFrom = $now->startOfMonth();
        $monthTo   = $now->endOfMonth();

        $topDeliverymen = DeliveryMan::query()
            ->with(['user' => fn ($q) => $q->select('id', 'name', 'image_id')->with('upload:id,original')])
            ->select('delivery_man.id', 'delivery_man.user_id', 'delivery_man.company_id')
            ->selectSub(
                ParcelEvent::query()->selectRaw('COUNT(DISTINCT parcel_id)')
                    ->whereColumn('parcel_events.delivery_man_id', 'delivery_man.id')
                    ->whereBetween('parcel_events.created_at', [$monthFrom, $monthTo]),
                'assigned'
            )
            ->selectSub(
                ParcelEvent::query()->selectRaw('COUNT(DISTINCT parcel_events.parcel_id)')
                    ->join('parcels', 'parcels.id', '=', 'parcel_events.parcel_id')
                    ->whereColumn('parcel_events.delivery_man_id', 'delivery_man.id')
                    ->whereBetween('parcel_events.created_at', [$monthFrom, $monthTo])
                    ->where('parcels.status', ParcelStatus::DELIVERED),
                'delivered'
            )
            ->orderByDesc('assigned')
            ->limit(10)
            ->get()
            ->filter(fn ($d) => (int) $d->assigned > 0)
            ->map(function ($d) {
                $a = (int) $d->assigned; $del = (int) $d->delivered;
                return [
                    'id'          => (int) $d->id,
                    'name'        => (string) (optional($d->user)->name ?: 'Deliveryman #'.$d->id),
                    'photo_url'   => optional($d->user)->image_id ? optional($d->user)->image : null,
                    'assigned'    => $a,
                    'delivered'   => $del,
                    'performance' => $a > 0 ? round(($del / $a) * 100, 1) : 0.0,
                ];
            })
            ->values();

        // Platform-only metrics (no tenant equivalent, so they appear here).
        $tenantCount = (int) GeneralSettings::query()->count();
        $userCount   = (int) User::query()->count();
        $adminCount  = (int) User::query()->where('user_type', \App\Enums\UserType::ADMIN)->count();
        $deliveryCount = (int) DeliveryMan::query()->count();

        // Last 5 tenant signups by created_at.
        $recentTenants = GeneralSettings::query()
            ->select('id', 'name', 'created_at')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn ($t) => [
                'id'         => (int) $t->id,
                'name'       => (string) ($t->name ?: 'Tenant #'.$t->id),
                'created_at' => optional($t->created_at)->format('Y-m-d'),
                'ago'        => optional($t->created_at)?->diffForHumans(),
            ])
            ->values();

        return Inertia::render('Admin/Superadmin/Summary/Index', [
            'kpis'            => $kpis,
            'platform'        => [
                'tenants'      => $tenantCount,
                'users'        => $userCount,
                'admins'       => $adminCount,
                'deliverymen'  => $deliveryCount,
            ],
            'trend'           => $trend,
            'top_tenants'     => $topTenants,
            'ofd_by_tenant'   => $ofdByTenant,
            'recent_tenants'  => $recentTenants,
            'top_deliverymen' => $topDeliverymen,
            't' => [
                'title'          => 'Platform summary',
                'subtitle'       => 'Cross-tenant KPIs for the whole platform.',
                'kpi_today'      => "Today's shipments",
                'kpi_ofd'        => 'OFD',
                'kpi_delivered'  => 'Delivered today',
                'kpi_pending'    => 'Pending pickup',
                'platform_title'    => 'Platform',
                'platform_tenants'  => 'Tenants',
                'platform_users'    => 'Users',
                'platform_admins'   => 'Admins',
                'platform_deliverymen' => 'Deliverymen',
                'seven_day_title'    => 'Last 7 days · platform-wide',
                'legend_created'     => 'Created',
                'legend_delivered'   => 'Delivered',
                'top_tenants_title'  => 'Top tenants by shipments',
                'top_tenants_col_name' => 'Tenant',
                'top_tenants_col_qty'  => 'Shipments',
                'top_tenants_empty'    => 'No tenants with shipments yet.',
                'ofd_title'          => 'OFD by tenant',
                'ofd_subtitle'       => 'Today, currently out for delivery',
                'ofd_col_name'       => 'Tenant',
                'ofd_col_qty'        => 'OFD',
                'ofd_empty'          => 'No parcels out for delivery today.',
                'recent_tenants_title' => 'Recent tenants',
                'recent_tenants_empty' => 'No tenants signed up yet.',
                'top_deliverymen_title'    => 'Deliveryman performance',
                'top_deliverymen_subtitle' => 'Current month · across all tenants',
                'top_deliverymen_col_name' => 'Deliveryman',
                'top_deliverymen_col_assigned'  => 'Assigned',
                'top_deliverymen_col_delivered' => 'Delivered',
                'top_deliverymen_col_performance' => 'Performance',
                'top_deliverymen_empty' => 'No deliveryman activity this month.',
            ],
        ]);
    }
}
