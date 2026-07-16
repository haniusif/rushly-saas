<?php

namespace App\Http\Controllers\Backend\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Backend\GeneralSettings;
use App\Models\Backend\Subscription;
use App\Models\Backend\Support;
use App\Models\Backend\Superadmin\Plan;
use App\Models\User;
use App\Enums\UserType;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * SaaS-owner dashboard on the central super-admin domain. Focuses on
 * platform health — tenants, subscriptions/MRR, plans, support — not
 * shipment operations (that's what the tenant /summary is for).
 */
class SummaryController extends Controller
{
    public function index()
    {
        $now       = CarbonImmutable::now();
        $todayFrom = $now->startOfDay();
        $todayTo   = $now->endOfDay();
        $monthFrom = $now->startOfMonth();
        $monthTo   = $now->endOfMonth();
        $soonFrom  = $now->copy();
        $soonTo    = $now->copy()->addDays(30)->endOfDay();

        // -------- SaaS KPIs --------------------------------------
        $activeSubs   = Subscription::query()->where('expired_date', '>=', $now)->count();
        $mrr          = (float) Subscription::query()->where('expired_date', '>=', $now)->sum('price');
        $newThisMonth = GeneralSettings::query()->whereBetween('created_at', [$monthFrom, $monthTo])->count();
        $openTickets  = Support::query()->where('status', '!=', 2)->count(); // 2 = closed convention in this repo

        $saas = [
            'tenants'         => (int) GeneralSettings::query()->count(),
            'active_subs'     => (int) $activeSubs,
            'mrr'             => $mrr,
            'new_this_month'  => (int) $newThisMonth,
            'open_tickets'    => (int) $openTickets,
            'total_tickets'   => (int) Support::query()->count(),
        ];

        // -------- Users spread ----------------------------------
        $userSplit = [
            'total'       => (int) User::query()->count(),
            'admins'      => (int) User::query()->where('user_type', UserType::ADMIN)->count(),
            'deliverymen' => (int) User::query()->where('user_type', UserType::DELIVERYMAN)->count(),
            'merchants'   => (int) User::query()->where('user_type', UserType::MERCHANT)->count(),
        ];

        // -------- Subscription status breakdown -----------------
        // "Expiring soon" = active but within 30 days of the end date.
        $subStatus = [
            'active'      => (int) Subscription::query()->where('expired_date', '>=', $now)->count(),
            'expired'     => (int) Subscription::query()->where('expired_date', '<',  $now)->count(),
            'expiring_soon' => (int) Subscription::query()
                ->whereBetween('expired_date', [$soonFrom, $soonTo])->count(),
        ];

        // -------- 30-day tenant signup trend --------------------
        $days = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = $now->subDays($i)->startOfDay();
            $days[$d->format('Y-m-d')] = [
                'label' => $d->format('d'),
                'iso'   => $d->format('Y-m-d'),
                'count' => 0,
            ];
        }
        $rows = GeneralSettings::query()
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->whereBetween('created_at', [$now->subDays(29)->startOfDay(), $todayTo])
            ->groupBy('d')->pluck('c', 'd');
        foreach ($rows as $d => $c) if (isset($days[$d])) $days[$d]['count'] = (int) $c;
        $signupTrend = array_values($days);

        // -------- Plan distribution (# active tenants per plan) --
        $planDist = Plan::query()
            ->select('id', 'name', 'price')
            ->selectSub(
                Subscription::query()
                    ->selectRaw('COUNT(DISTINCT company_id)')
                    ->whereColumn('subscriptions.plan_id', 'plans.id')
                    ->where('expired_date', '>=', $now),
                'active_tenants'
            )
            ->orderByDesc('active_tenants')
            ->get()
            ->map(fn ($p) => [
                'id'             => (int) $p->id,
                'name'           => (string) $p->name,
                'price'          => (float) $p->price,
                'active_tenants' => (int) $p->active_tenants,
            ])
            ->values();

        // -------- Recent tenants (last 6) with plan info ---------
        $recentTenants = GeneralSettings::query()
            ->select('id', 'name', 'created_at', 'plan_id', 'subscription_id')
            ->with(['plan:id,name,price'])
            ->orderByDesc('created_at')
            ->limit(6)
            ->get()
            ->map(fn ($t) => [
                'id'         => (int) $t->id,
                'name'       => (string) ($t->name ?: 'Tenant #'.$t->id),
                'plan'       => optional($t->plan)->name,
                'created_at' => optional($t->created_at)->format('Y-m-d'),
                'ago'        => optional($t->created_at)?->diffForHumans(),
            ])
            ->values();

        // -------- Recent support tickets (last 6) ----------------
        $recentTickets = Support::query()
            ->with(['user:id,name'])
            ->orderByDesc('created_at')
            ->limit(6)
            ->get()
            ->map(fn ($s) => [
                'id'         => (int) $s->id,
                'subject'    => (string) ($s->subject ?: '—'),
                'status'     => (int) ($s->status ?? 0),
                'priority'   => (string) ($s->priority ?? ''),
                'user'       => optional($s->user)->name,
                'ago'        => optional($s->created_at)?->diffForHumans(),
            ])
            ->values();

        return Inertia::render('Admin/Superadmin/Summary/Index', [
            'currency'      => (string) (settings()->currency ?? ''),
            'saas'          => $saas,
            'users'         => $userSplit,
            'sub_status'    => $subStatus,
            'signup_trend'  => $signupTrend,
            'plan_dist'     => $planDist,
            'recent_tenants'=> $recentTenants,
            'recent_tickets'=> $recentTickets,
            't' => [
                'title'    => 'Platform overview',
                'subtitle' => 'SaaS health at a glance: tenants, subscriptions, plans, and support.',
                // KPI labels
                'kpi_tenants'      => 'Tenants',
                'kpi_active_subs'  => 'Active subscriptions',
                'kpi_mrr'          => 'Active revenue',
                'kpi_new_month'    => 'New this month',
                'kpi_open_tickets' => 'Open tickets',
                'kpi_total_tickets'=> 'Total tickets',
                // Users breakdown
                'users_title'      => 'Users on the platform',
                'users_total'      => 'Total',
                'users_admins'     => 'Admins',
                'users_delivery'   => 'Deliverymen',
                'users_merchants'  => 'Merchants',
                // Subscription status
                'sub_status_title'  => 'Subscriptions',
                'sub_active'        => 'Active',
                'sub_expiring_soon' => 'Expiring in 30 days',
                'sub_expired'       => 'Expired',
                // Signup trend
                'signup_trend_title'    => 'Tenant signups · last 30 days',
                'signup_trend_empty'    => 'No signups in the last 30 days.',
                // Plan distribution
                'plan_dist_title'   => 'Plan distribution',
                'plan_dist_col_name'=> 'Plan',
                'plan_dist_col_price'=> 'Price',
                'plan_dist_col_tenants' => 'Active tenants',
                'plan_dist_empty'   => 'No plans yet.',
                // Recent tenants
                'recent_tenants_title' => 'Recent tenants',
                'recent_tenants_empty' => 'No tenants signed up yet.',
                // Recent tickets
                'recent_tickets_title' => 'Recent support tickets',
                'recent_tickets_empty' => 'No support tickets yet.',
                'ticket_open'          => 'Open',
                'ticket_pending'       => 'Pending',
                'ticket_closed'        => 'Closed',
            ],
        ]);
    }
}
