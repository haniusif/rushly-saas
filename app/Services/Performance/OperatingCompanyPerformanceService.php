<?php

namespace App\Services\Performance;

use App\Enums\ParcelStatus;
use App\Enums\Status;
use App\Models\Backend\AbnormalShipment;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\SupplierCompany;
use App\Models\Backend\Support;
use Illuminate\Support\Facades\DB;

/**
 * "Operating Company" performance — within the tenant, these are the 3PL
 * supplier_companies that drivers (delivery_man.supplier_company_id) are
 * attached to. The original spec talks about cross-tenant companies; the
 * approved scope is tenant-local 3PL partners.
 *
 *   Fleet size       = COUNT(delivery_man WHERE supplier_company_id = X)
 *   Orders handled   = parcels touched by those drivers in window
 *   Revenue          = SUM(cash_collection) of delivered parcels handled
 *   Expenses         = SUM(delivery_charge) of delivered parcels handled
 *   Success rate     = delivered / handled
 *   Avg delivery h   = AVG(delivery_date − created_at) for delivered
 *   SLA compliance   = 1 − open_abnormal_on_those_parcels / handled
 *
 * Note: "vehicles" isn't trackable per supplier company (no FK on assets).
 */
class OperatingCompanyPerformanceService
{
    public function payload(PerformanceFilters $f, int $limit = 20): array
    {
        return [
            'kpi'      => $this->kpiBlock($f),
            'ranking'  => $this->ranking($f, $limit),
            'compare'  => $this->comparison($f),
        ];
    }

    /* ---------------- KPI rollup ---------------- */

    private function kpiBlock(PerformanceFilters $f): array
    {
        $total  = SupplierCompany::companywise()->count();
        $active = SupplierCompany::companywise()->where('status', Status::ACTIVE)->count();

        $totalDrivers = DeliveryMan::companywise()->whereNotNull('supplier_company_id')->count();
        $totalVehicles = 0; // not modelled at supplier-company level

        // All parcels with delivery_man assigned to a supplier_company driver, in window.
        // parcel_events has no company_id column — scope via the parcel_id subquery
        // so we never count events from sibling tenants.
        $tenantParcelIds = DB::table('parcels')->select('id')->where('company_id', settings()->id);

        $handled = DB::table('parcel_events')
            ->whereIn('parcel_events.parcel_id', $tenantParcelIds)
            ->join('delivery_man', 'parcel_events.delivery_man_id', '=', 'delivery_man.id')
            ->whereBetween('parcel_events.created_at', [$f->from, $f->to])
            ->whereNotNull('delivery_man.supplier_company_id')
            ->when($f->supplierCompanyId, fn ($q, $v) => $q->where('delivery_man.supplier_company_id', $v))
            ->distinct()
            ->count('parcel_events.parcel_id');

        $delivered = DB::table('parcel_events')
            ->whereIn('parcel_events.parcel_id', $tenantParcelIds)
            ->join('delivery_man', 'parcel_events.delivery_man_id', '=', 'delivery_man.id')
            ->whereBetween('parcel_events.created_at', [$f->from, $f->to])
            ->whereNotNull('delivery_man.supplier_company_id')
            ->when($f->supplierCompanyId, fn ($q, $v) => $q->where('delivery_man.supplier_company_id', $v))
            ->where('parcel_events.parcel_status', ParcelStatus::DELIVERED)
            ->distinct()
            ->count('parcel_events.parcel_id');

        $financialJoin = DB::table('parcels')
            ->where('parcels.company_id', settings()->id)
            ->join('parcel_events', 'parcel_events.parcel_id', '=', 'parcels.id')
            ->join('delivery_man',  'parcel_events.delivery_man_id', '=', 'delivery_man.id')
            ->where('parcels.status', ParcelStatus::DELIVERED)
            ->whereBetween('parcels.delivery_date', [$f->from, $f->to])
            ->whereNotNull('delivery_man.supplier_company_id')
            ->when($f->supplierCompanyId, fn ($q, $v) => $q->where('delivery_man.supplier_company_id', $v));

        $finRow = (clone $financialJoin)
            ->selectRaw('SUM(DISTINCT parcels.cash_collection) AS revenue,
                         SUM(DISTINCT parcels.delivery_charge) AS expense')
            ->first();
        $revenue = (float) ($finRow->revenue ?? 0);
        $expense = (float) ($finRow->expense ?? 0);
        $profit  = $revenue - $expense;

        // Fleet utilization = handled parcels per driver (lower-bound proxy)
        $fleetUtil = $totalDrivers > 0 ? min(1.0, $handled / max($totalDrivers, 1) / 50) : null;

        // Avg delivery hours across all suppliers
        $avgRow = (clone $financialJoin)
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, parcels.created_at, parcels.delivery_date)) AS h')
            ->first();
        $avgHours = $avgRow && $avgRow->h !== null ? round((float) $avgRow->h, 1) : null;

        $sla = $handled > 0 ? max(0.0, 1.0 - ($this->openAbnormalIn($f) / max($handled, 1))) : null;
        $tickets = Support::query()->whereBetween('created_at', [$f->from, $f->to])->count();
        $satisfaction = $handled > 0
            ? max(0.0, min(1.0, 1.0 - ($tickets / max($handled, 1))))
            : null;

        $successRate = $handled > 0 ? $delivered / $handled : null;

        $cohort = PerformanceScoreCalculator::compute([
            'productivity' => $totalDrivers > 0 ? min(1.0, $delivered / max($totalDrivers, 1) / 20) : 0,
            'completion'   => $successRate,
            'rating'       => $satisfaction,
            'on_time'      => null,
            'revenue'      => null,
            'sla'          => $sla,
            'growth'       => null,
        ]);

        return [
            'total_companies'  => $total,
            'active_companies' => $active,
            'fleet_size'       => $totalDrivers,
            'total_vehicles'   => $totalVehicles,
            'handled'          => $handled,
            'completed'        => $delivered,
            'revenue'          => round($revenue, 2),
            'expenses'         => round($expense, 2),
            'profit'           => round($profit, 2),
            'avg_delivery_hours' => $avgHours,
            'success_rate'     => $successRate !== null ? round($successRate, 4) : null,
            'fleet_utilization'=> $fleetUtil !== null ? round($fleetUtil, 4) : null,
            'sla_compliance'   => $sla !== null ? round($sla, 4) : null,
            'satisfaction'     => $satisfaction !== null ? round($satisfaction, 4) : null,
            'cohort_score'     => $cohort['score'],
            'cohort_band'      => $cohort['band'],
            'proxies'          => [
                'fleet_utilization' => 'handled parcels / drivers / 50 (rule-of-thumb)',
                'sla_compliance'    => '1 − open abnormal_shipments / handled',
                'satisfaction'      => '1 − support_tickets / handled',
            ],
        ];
    }

    /* ---------------- Per-supplier leaderboard ---------------- */

    private function ranking(PerformanceFilters $f, int $limit): array
    {
        $rows = DB::table('parcels')
            ->join('parcel_events', 'parcel_events.parcel_id', '=', 'parcels.id')
            ->join('delivery_man',  'parcel_events.delivery_man_id', '=', 'delivery_man.id')
            ->join('supplier_companies', 'supplier_companies.id', '=', 'delivery_man.supplier_company_id')
            ->whereBetween('parcels.created_at', [$f->from, $f->to])
            ->where('supplier_companies.company_id', settings()->id)
            ->selectRaw(
                'supplier_companies.id AS sid,
                 supplier_companies.name AS sname,
                 COUNT(DISTINCT parcels.id) AS handled,
                 SUM(DISTINCT CASE WHEN parcels.status = ? THEN 1 ELSE 0 END) AS delivered,
                 SUM(DISTINCT CASE WHEN parcels.status = ? THEN parcels.cash_collection ELSE 0 END) AS revenue,
                 SUM(DISTINCT CASE WHEN parcels.status = ? THEN parcels.delivery_charge ELSE 0 END) AS expense,
                 AVG(CASE WHEN parcels.status = ? THEN TIMESTAMPDIFF(HOUR, parcels.created_at, parcels.delivery_date) END) AS avg_hours',
                [ParcelStatus::DELIVERED, ParcelStatus::DELIVERED, ParcelStatus::DELIVERED, ParcelStatus::DELIVERED]
            )
            ->groupBy('supplier_companies.id', 'supplier_companies.name')
            ->orderByDesc('handled')
            ->limit($limit)
            ->get();

        if ($rows->isEmpty()) return [];

        $topHandled = max(1, (int) $rows->max('handled'));
        $topRevenue = max(1, (float) $rows->max('revenue'));

        // Driver counts per supplier (fleet size)
        $drivers = DeliveryMan::companywise()
            ->select('supplier_company_id', DB::raw('COUNT(*) AS n'))
            ->whereIn('supplier_company_id', $rows->pluck('sid'))
            ->groupBy('supplier_company_id')
            ->pluck('n', 'supplier_company_id');

        $out = [];
        foreach ($rows as $r) {
            $handled   = (int) $r->handled;
            $delivered = (int) $r->delivered;
            $revenue   = (float) $r->revenue;
            $expense   = (float) $r->expense;
            $successRate = $handled > 0 ? $delivered / $handled : null;

            $score = PerformanceScoreCalculator::compute([
                'productivity' => $handled / $topHandled,
                'completion'   => $successRate,
                'rating'       => null,
                'on_time'      => null,
                'revenue'      => $revenue / $topRevenue,
                'sla'          => null,
                'growth'       => null,
            ]);

            $out[] = [
                'company_id'     => (int) $r->sid,
                'name'           => $r->sname,
                'fleet_size'     => (int) ($drivers[$r->sid] ?? 0),
                'handled'        => $handled,
                'delivered'      => $delivered,
                'success_rate'   => $successRate !== null ? round($successRate, 4) : null,
                'revenue'        => round($revenue, 2),
                'expense'        => round($expense, 2),
                'profit'         => round($revenue - $expense, 2),
                'avg_hours'      => $r->avg_hours !== null ? round((float) $r->avg_hours, 1) : null,
                'score'          => $score['score'],
                'band'           => $score['band'],
            ];
        }
        return $out;
    }

    /* ---------------- Revenue + order comparison series (weekly) ---------------- */

    private function comparison(PerformanceFilters $f): array
    {
        $rows = DB::table('parcels')
            ->join('parcel_events', 'parcel_events.parcel_id', '=', 'parcels.id')
            ->join('delivery_man',  'parcel_events.delivery_man_id', '=', 'delivery_man.id')
            ->whereBetween('parcels.created_at', [$f->from, $f->to])
            ->whereNotNull('delivery_man.supplier_company_id')
            ->when($f->supplierCompanyId, fn ($q, $v) => $q->where('delivery_man.supplier_company_id', $v))
            ->selectRaw(
                "DATE_FORMAT(parcels.created_at, '%Y-%u') AS yw,
                 MIN(DATE(parcels.created_at)) AS first_day,
                 COUNT(DISTINCT parcels.id) AS orders,
                 SUM(DISTINCT CASE WHEN parcels.status = ? THEN parcels.cash_collection ELSE 0 END) AS revenue",
                [ParcelStatus::DELIVERED]
            )
            ->groupBy('yw')
            ->orderBy('yw')
            ->get();

        return $rows->map(fn ($r) => [
            'label'   => $r->first_day,
            'orders'  => (int) $r->orders,
            'revenue' => round((float) $r->revenue, 2),
        ])->all();
    }

    /* ---------------- helpers ---------------- */

    private function openAbnormalIn(PerformanceFilters $f): int
    {
        return AbnormalShipment::query()
            ->whereBetween('detected_at', [$f->from, $f->to])
            ->whereNotIn('status', ['resolved', 'closed_lost'])
            ->count();
    }
}
