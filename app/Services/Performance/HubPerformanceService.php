<?php

namespace App\Services\Performance;

use App\Enums\ParcelStatus;
use App\Enums\Status;
use App\Models\Backend\AbnormalShipment;
use App\Models\Backend\Asset;
use App\Models\Backend\Hub;
use App\Models\Backend\HubInCharge;
use App\Models\Backend\Parcel;
use App\Models\Backend\Support;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Hub / branch performance aggregations.
 *
 *   Revenue per hub  = SUM(parcels.cash_collection) for DELIVERED parcels at the hub
 *   Expenses per hub = SUM(parcels.delivery_charge) for DELIVERED parcels at the hub
 *   Orders           = COUNT(parcels) at the hub in the window
 *   Success rate     = delivered / total at the hub
 *   Avg proc. time   = AVG(delivery_date − created_at)  for delivered
 *   Employees        = users + hub_incharges attached to the hub
 *   Vehicles         = assets attached to the hub
 *   SLA              = 1 − open_abnormal / total_in_window
 */
class HubPerformanceService
{
    public function payload(PerformanceFilters $f, int $limit = 20): array
    {
        return [
            'kpi'       => $this->kpiBlock($f),
            'ranking'   => $this->ranking($f, $limit),
            'trend'     => $this->monthlyTrend($f),
        ];
    }

    /* ---------------- KPI block (roll-up across hubs) ---------------- */

    private function kpiBlock(PerformanceFilters $f): array
    {
        $totalBranches  = Hub::companywise()->count();
        $activeBranches = Hub::companywise()->where('status', Status::ACTIVE)->count();

        $orders = Parcel::query()->whereBetween('created_at', [$f->from, $f->to]);
        if ($f->hubId) $orders->where('hub_id', $f->hubId);
        $totalOrders = (clone $orders)->count();

        $delivered = (clone $orders)->where('status', ParcelStatus::DELIVERED);
        $deliveredCount = (clone $delivered)->count();

        $revenue = (float) (clone $delivered)->sum('cash_collection');
        $expense = (float) (clone $delivered)->sum('delivery_charge');
        $profit  = $revenue - $expense;

        $successRate = $totalOrders > 0 ? round($deliveredCount / $totalOrders, 4) : null;

        $avgProcHours = (float) (clone $delivered)
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, delivery_date)) AS h')
            ->value('h');

        // Employees per scope.
        // Users carry company_id directly; hub_incharges does NOT — it only has
        // (user_id, hub_id, status), so we filter by joining to users for tenancy.
        $employees = User::query()->where('company_id', settings()->id);
        if ($f->hubId) $employees->where('hub_id', $f->hubId);
        $employeesCount = (clone $employees)->whereNotNull('hub_id')->count();
        $employeesCount += HubInCharge::query()
            ->whereIn('user_id', User::query()->where('company_id', settings()->id)->select('id'))
            ->when($f->hubId, fn ($q, $v) => $q->where('hub_id', $v))
            ->count();

        // Vehicles (assets attached to a hub)
        $vehicles = Asset::query()->where('company_id', settings()->id)->whereNotNull('hub_id');
        if ($f->hubId) $vehicles->where('hub_id', $f->hubId);
        $vehiclesCount = $vehicles->count();

        // SLA = 1 - open abnormal / total in window
        $abnormalOpen = AbnormalShipment::query()
            ->whereBetween('detected_at', [$f->from, $f->to])
            ->whereNotIn('status', ['resolved', 'closed_lost'])
            ->count();
        $sla = $totalOrders > 0 ? max(0.0, 1.0 - ($abnormalOpen / max($totalOrders, 1))) : null;

        // Satisfaction proxy (cohort-level)
        $tickets = Support::query()->whereBetween('created_at', [$f->from, $f->to])->count();
        $satisfaction = $totalOrders > 0
            ? max(0.0, min(1.0, 1.0 - ($tickets / max($totalOrders, 1))))
            : null;

        $cohort = PerformanceScoreCalculator::compute([
            'productivity' => $totalBranches > 0 ? min(1.0, $totalOrders / max($totalBranches, 1) / 100) : 0,
            'completion'   => $successRate,
            'rating'       => $satisfaction,
            'on_time'      => null,
            'revenue'      => null,
            'sla'          => $sla,
            'growth'       => null,
        ]);

        return [
            'total_branches'    => $totalBranches,
            'active_branches'   => $activeBranches,
            'orders'            => $totalOrders,
            'revenue'           => round($revenue, 2),
            'expenses'          => round($expense, 2),
            'profit'            => round($profit, 2),
            'employees'         => $employeesCount,
            'vehicles'          => $vehiclesCount,
            'success_rate'      => $successRate,
            'avg_processing_hours' => $avgProcHours > 0 ? round($avgProcHours, 1) : null,
            'sla_compliance'    => $sla !== null ? round($sla, 4) : null,
            'satisfaction'      => $satisfaction !== null ? round($satisfaction, 4) : null,
            'cohort_score'      => $cohort['score'],
            'cohort_band'       => $cohort['band'],
            'proxies'           => [
                'sla_compliance' => '1 − open abnormal_shipments / total parcels in window',
                'satisfaction'   => '1 − support_tickets / orders (cohort-level)',
            ],
        ];
    }

    /* ---------------- Per-hub ranking ---------------- */

    private function ranking(PerformanceFilters $f, int $limit): array
    {
        $rows = Parcel::query()
            ->whereBetween('created_at', [$f->from, $f->to])
            ->whereNotNull('hub_id')
            ->selectRaw(
                'hub_id,
                 COUNT(*) AS orders,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS delivered,
                 SUM(CASE WHEN status = ? THEN cash_collection ELSE 0 END) AS revenue,
                 SUM(CASE WHEN status = ? THEN delivery_charge ELSE 0 END) AS expense,
                 AVG(CASE WHEN status = ? THEN TIMESTAMPDIFF(HOUR, created_at, delivery_date) END) AS avg_hours',
                [ParcelStatus::DELIVERED, ParcelStatus::DELIVERED, ParcelStatus::DELIVERED, ParcelStatus::DELIVERED]
            )
            ->groupBy('hub_id')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get();

        if ($rows->isEmpty()) return [];

        $hubs = Hub::companywise()->whereIn('id', $rows->pluck('hub_id'))
            ->pluck('name', 'id');

        $topRevenue = max(1, (float) $rows->max('revenue'));
        $topOrders  = max(1, (int) $rows->max('orders'));

        $out = [];
        foreach ($rows as $r) {
            $orders    = (int) $r->orders;
            $delivered = (int) $r->delivered;
            $revenue   = (float) $r->revenue;
            $expense   = (float) $r->expense;
            $profit    = $revenue - $expense;
            $successRate = $orders > 0 ? $delivered / $orders : null;

            $score = PerformanceScoreCalculator::compute([
                'productivity' => $orders / $topOrders,
                'completion'   => $successRate,
                'rating'       => null,
                'on_time'      => null,
                'revenue'      => $revenue / $topRevenue,
                'sla'          => null,
                'growth'       => null,
            ]);

            $out[] = [
                'hub_id'         => (int) $r->hub_id,
                'name'           => $hubs[$r->hub_id] ?? ('Hub #' . $r->hub_id),
                'orders'         => $orders,
                'delivered'      => $delivered,
                'success_rate'   => $successRate !== null ? round($successRate, 4) : null,
                'revenue'        => round($revenue, 2),
                'expense'        => round($expense, 2),
                'profit'         => round($profit, 2),
                'avg_hours'      => $r->avg_hours !== null ? round((float) $r->avg_hours, 1) : null,
                'score'          => $score['score'],
                'band'           => $score['band'],
            ];
        }
        return $out;
    }

    /* ---------------- Monthly performance trend (revenue, orders, profit) ---------------- */

    private function monthlyTrend(PerformanceFilters $f): array
    {
        $rows = Parcel::query()
            ->whereBetween('created_at', [$f->from, $f->to])
            ->when($f->hubId, fn ($q, $v) => $q->where('hub_id', $v))
            ->selectRaw(
                "DATE_FORMAT(created_at, '%Y-%m') AS ym,
                 COUNT(*) AS orders,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS delivered,
                 SUM(CASE WHEN status = ? THEN cash_collection ELSE 0 END) AS revenue,
                 SUM(CASE WHEN status = ? THEN delivery_charge ELSE 0 END) AS expense",
                [ParcelStatus::DELIVERED, ParcelStatus::DELIVERED, ParcelStatus::DELIVERED]
            )
            ->groupBy('ym')
            ->orderBy('ym')
            ->get();

        return $rows->map(fn ($r) => [
            'label'     => $r->ym,
            'orders'    => (int) $r->orders,
            'delivered' => (int) $r->delivered,
            'revenue'   => round((float) $r->revenue, 2),
            'expense'   => round((float) $r->expense, 2),
            'profit'    => round((float) $r->revenue - (float) $r->expense, 2),
        ])->all();
    }
}
