<?php

namespace App\Http\Controllers\Backend\Wms;

use App\Enums\Wms\FulfillmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Backend\Wms\WmsAdjustment;
use App\Models\Backend\Wms\WmsDamageReport;
use App\Models\Backend\Wms\WmsFulfillment;
use App\Models\Backend\Wms\WmsGrn;
use App\Models\Backend\Wms\WmsProduct;
use App\Models\Backend\Wms\WmsStock;
use App\Repositories\Wms\WmsFulfillmentRepositoryInterface;
use App\Repositories\Wms\WmsProductRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class WmsDashboardController extends Controller
{
    public function __construct(
        protected WmsProductRepositoryInterface $productRepo,
        protected WmsFulfillmentRepositoryInterface $fulRepo
    ) {}

    public function index()
    {
        // ===== KPI cards =====
        $kpi = [
            'total_skus'           => WmsProduct::companywise()->where('is_active', true)->count(),
            'total_units'          => (int) WmsStock::companywise()->sum('quantity'),
            'pending_fulfillments' => WmsFulfillment::companywise()
                                        ->whereNotIn('status', [FulfillmentStatus::DISPATCHED, FulfillmentStatus::CANCELLED])
                                        ->count(),
            'grns_today'           => WmsGrn::companywise()->whereDate('created_at', today())->count(),
            'low_stock_count'      => $this->productRepo->lowStock()->count(),
            'damage_this_month'    => WmsDamageReport::companywise()
                                        ->whereMonth('created_at', now()->month)
                                        ->whereYear('created_at',  now()->year)
                                        ->count(),
            'sla_breached'         => $this->fulRepo->breachedSla()->count(),
            'pending_adjustments'  => WmsAdjustment::companywise()->where('approval_status', 'pending_approval')->count(),
        ];

        // ===== Stock movement chart (last 7 days) — sums of adjustments per day =====
        $days = collect();
        for ($i = 6; $i >= 0; $i--) {
            $days->push(Carbon::today()->subDays($i));
        }
        $movement = $days->map(function (Carbon $d) {
            $sums = WmsAdjustment::companywise()
                ->whereDate('created_at', $d)
                ->where('approval_status', 'approved')
                ->selectRaw('SUM(GREATEST(quantity_change, 0)) AS credit, SUM(LEAST(quantity_change, 0)) AS debit')
                ->first();
            return [
                'label'  => $d->format('M j'),
                'credit' => (int) ($sums->credit ?? 0),
                'debit'  => (int) ($sums->debit ?? 0),  // negative number
            ];
        })->all();

        // ===== Fulfillment status breakdown (current snapshot) =====
        $fulBreakdown = WmsFulfillment::companywise()
            ->selectRaw('status, COUNT(*) as n')
            ->groupBy('status')
            ->pluck('n', 'status')
            ->all();
        $fulOrder = ['pending','picking','packing','ready','dispatched','cancelled'];
        $fulChart = collect($fulOrder)->map(fn ($s) => ['status' => $s, 'n' => (int) ($fulBreakdown[$s] ?? 0)])->all();

        // ===== Alerts panels =====
        $lowStockProducts = $this->productRepo->lowStock()->take(8);
        $expiringSoon = WmsStock::companywise()
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', now()->addDays(7))
            ->where('quantity', '>', 0)
            ->with(['product', 'location'])
            ->orderBy('expiry_date')
            ->take(8)
            ->get();
        $slaBreached = $this->fulRepo->breachedSla()->take(8);

        return view('backend.wms.dashboard.index', compact(
            'kpi', 'movement', 'fulChart', 'lowStockProducts', 'expiringSoon', 'slaBreached'
        ));
    }
}
