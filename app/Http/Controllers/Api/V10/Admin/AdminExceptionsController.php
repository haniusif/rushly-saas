<?php

namespace App\Http\Controllers\Api\V10\Admin;

use App\Enums\ParcelStatus;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\Backend\Ndr;
use App\Models\Backend\Parcel;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;

/**
 * Aggregated "needs attention" feed for the supervisor mobile app.
 * Everything a supervisor should look at, in a single ranked payload.
 * Hub-clamped for HUB / INCHARGE roles.
 *
 * Three sections:
 *  - open_ndrs — parcels currently in a status/NDR indicating a failed attempt
 *  - stuck — parcels whose status hasn't changed in >= `stuckDays` (default 3)
 *  - returning — parcels in RETURN_TO_COURIER / awaiting return-to-merchant
 */
class AdminExceptionsController extends Controller
{
    use ApiReturnFormatTrait;

    private const STUCK_DAYS = 3;
    private const LIMIT = 100;

    public function index(Request $request)
    {
        $stuckDays = max(1, min(30, (int) $request->query('stuck_days', self::STUCK_DAYS)));
        $stuckThreshold = now()->subDays($stuckDays);

        // 1. Open NDRs (failed delivery attempts still open)
        $ndrQuery = Ndr::companywise()
            ->with(['parcel:id,tracking_id,customer_name,hub_id,merchant_id'])
            ->whereIn('status', ['open', 'in_progress'])
            ->latest('id')
            ->limit(self::LIMIT);
        $this->applyHubScopeViaParcel($ndrQuery, $request);
        $ndrs = $ndrQuery->get()->map(fn (Ndr $n) => [
            'ndr_id'         => $n->id,
            'parcel_id'      => $n->parcel_id,
            'tracking_id'    => optional($n->parcel)->tracking_id,
            'customer_name'  => optional($n->parcel)->customer_name,
            'failure_reason' => $n->failure_reason,
            'attempt_number' => (int) $n->attempt_number,
            'created_at'     => optional($n->created_at)->toIso8601String(),
        ]);

        // 2. Stuck parcels — same status for stuckDays or more, not yet delivered
        $stuckQuery = Parcel::query()
            ->select('id', 'tracking_id', 'customer_name', 'status', 'hub_id',
                     'merchant_id', 'updated_at')
            ->withDriverId()
            ->whereNotIn('status', [
                ParcelStatus::DELIVERED,
                ParcelStatus::PARTIAL_DELIVERED,
                ParcelStatus::RETURN_RECEIVED_BY_MERCHANT,
            ])
            ->where('updated_at', '<=', $stuckThreshold)
            ->latest('updated_at')
            ->limit(self::LIMIT);
        $this->applyHubScope($stuckQuery, $request);
        $stuck = $stuckQuery->get()->map(fn (Parcel $p) => [
            'parcel_id'       => $p->id,
            'tracking_id'     => $p->tracking_id,
            'customer_name'   => $p->customer_name,
            'status'          => (int) $p->status,
            'delivery_man_id' => $p->delivery_man_id,
            'stuck_since'     => optional($p->updated_at)->toIso8601String(),
            'days_stuck'      => (int) $p->updated_at->diffInDays(now()),
        ]);

        // 3. Returning to courier or already back at courier
        $returningQuery = Parcel::query()
            ->select('id', 'tracking_id', 'customer_name', 'status', 'hub_id',
                     'merchant_id', 'updated_at')
            ->withDriverId()
            ->where('status', ParcelStatus::RETURN_TO_COURIER)
            ->latest('updated_at')
            ->limit(self::LIMIT);
        $this->applyHubScope($returningQuery, $request);
        $returning = $returningQuery->get()->map(fn (Parcel $p) => [
            'parcel_id'       => $p->id,
            'tracking_id'     => $p->tracking_id,
            'customer_name'   => $p->customer_name,
            'delivery_man_id' => $p->delivery_man_id,
            'updated_at'      => optional($p->updated_at)->toIso8601String(),
        ]);

        return $this->responseWithSuccess('admin.exceptions', [
            'stuck_days_threshold' => $stuckDays,
            'open_ndrs'            => $ndrs,
            'stuck'                => $stuck,
            'returning'            => $returning,
        ], 200);
    }

    private function applyHubScope($query, Request $request): void
    {
        $user = $request->user();
        $type = (int) $user->user_type;
        if (($type === UserType::HUB || $type === UserType::INCHARGE) && $user->hub_id) {
            $query->where('hub_id', (int) $user->hub_id);
        }
    }

    private function applyHubScopeViaParcel($query, Request $request): void
    {
        $user = $request->user();
        $type = (int) $user->user_type;
        if (($type === UserType::HUB || $type === UserType::INCHARGE) && $user->hub_id) {
            $query->whereHas('parcel',
                fn ($q) => $q->where('hub_id', (int) $user->hub_id));
        }
    }
}
