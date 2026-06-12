<?php

namespace App\Http\Controllers\Api\V10\Admin;

use App\Enums\ParcelStatus;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Hub;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Traits\ApiReturnFormatTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    use ApiReturnFormatTrait;

    /**
     * Top-level KPIs + per-status parcel counts.
     * Scope rules:
     *   - SUPER_ADMIN: sees everything (no scope).
     *   - ADMIN: tenant-scoped via the Companywise global scope (already wired on models).
     *   - HUB / INCHARGE: clamp to their hub_id.
     */
    public function index(Request $request)
    {
        $today = Carbon::today();

        $parcelQuery   = Parcel::query();
        $merchantQuery = Merchant::query();
        $driverQuery   = DeliveryMan::query();
        $hubQuery      = Hub::query();

        $hubId = $this->restrictHubId($request->user());
        if ($hubId) {
            $parcelQuery->where('hub_id', $hubId);
            $driverQuery->where('hub_id', $hubId);
        }

        $byStatus = (clone $parcelQuery)
            ->select('status', DB::raw('count(*) as total'), DB::raw('coalesce(sum(cash_collection),0) as cod_total'))
            ->groupBy('status')
            ->get()
            ->map(fn ($row) => [
                'status'     => (int) $row->status,
                'statusName' => trans('parcelStatus.' . $row->status),
                'count'      => (int) $row->total,
                'cod_total'  => (float) $row->cod_total,
            ]);

        $totalParcels    = (clone $parcelQuery)->count();
        $deliveredToday  = (clone $parcelQuery)->where('status', ParcelStatus::DELIVERED)->whereDate('updated_at', $today)->count();
        $codCollectedToday = (clone $parcelQuery)->where('status', ParcelStatus::DELIVERED)->whereDate('updated_at', $today)->sum('cash_collection');
        $pendingPickup   = (clone $parcelQuery)->whereIn('status', [ParcelStatus::PENDING, ParcelStatus::PICKUP_ASSIGN])->count();
        $inTransit       = (clone $parcelQuery)->whereIn('status', [ParcelStatus::DELIVERY_MAN_ASSIGN, ParcelStatus::DELIVERY_RE_SCHEDULE])->count();

        $totals = [
            'parcels'             => $totalParcels,
            'merchants'           => $merchantQuery->count(),
            'drivers'             => $driverQuery->count(),
            'hubs'                => $hubQuery->count(),
            'delivered_today'     => $deliveredToday,
            'cod_collected_today' => (float) $codCollectedToday,
            'pending_pickup'      => $pendingPickup,
            'in_transit'          => $inTransit,
        ];

        return $this->responseWithSuccess('admin.dashboard', [
            'totals'    => $totals,
            'by_status' => $byStatus,
            'role'      => $this->roleName((int) $request->user()->user_type),
        ], 200);
    }

    /**
     * Daily parcel-creation series for the last N days (default 14).
     */
    public function timeseries(Request $request)
    {
        $days  = max(1, min(60, (int) $request->query('days', 14)));
        $start = Carbon::today()->subDays($days - 1);

        $query = Parcel::query()
            ->where('created_at', '>=', $start)
            ->select(
                DB::raw('DATE(created_at) as day'),
                DB::raw('count(*) as total'),
                DB::raw('coalesce(sum(cash_collection),0) as cod_total')
            )
            ->groupBy('day')
            ->orderBy('day');

        $hubId = $this->restrictHubId($request->user());
        if ($hubId) {
            $query->where('hub_id', $hubId);
        }

        $rows = $query->get()->keyBy('day');

        $series = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $start->copy()->addDays($i)->toDateString();
            $row = $rows->get($day);
            $series[] = [
                'day'   => $day,
                'count' => $row ? (int) $row->total : 0,
                'cod'   => $row ? (float) $row->cod_total : 0,
            ];
        }

        return $this->responseWithSuccess('admin.timeseries', ['series' => $series], 200);
    }

    private function restrictHubId($user): ?int
    {
        $type = (int) $user->user_type;
        if ($type === UserType::HUB || $type === UserType::INCHARGE) {
            return $user->hub_id ? (int) $user->hub_id : null;
        }
        return null;
    }

    private function roleName(int $type): string
    {
        return match ($type) {
            UserType::ADMIN       => 'admin',
            UserType::SUPER_ADMIN => 'super_admin',
            UserType::INCHARGE    => 'incharge',
            UserType::HUB         => 'hub',
            default               => 'unknown',
        };
    }
}
