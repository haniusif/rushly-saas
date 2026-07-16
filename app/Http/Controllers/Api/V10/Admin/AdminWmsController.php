<?php

namespace App\Http\Controllers\Api\V10\Admin;

use App\Enums\UserType;
use App\Enums\Wms\GrnStatus;
use App\Http\Controllers\Controller;
use App\Models\Backend\Wms\WmsGrn;
use App\Models\Backend\Wms\WmsLocation;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Admin-side WMS helpers that complement the existing scanner endpoints
 * under /api/v10/wms/*. The admin app reuses those directly for the
 * scan / complete / adjust flows; these two add the "warehouse manager
 * overview" pieces (list open GRNs, list locations).
 */
class AdminWmsController extends Controller
{
    use ApiReturnFormatTrait;

    /**
     * GET /admin/wms/grns
     * Open GRNs (DRAFT or IN_PROGRESS) available for receiving.
     */
    public function grns(Request $request)
    {
        $query = WmsGrn::companywise()
            ->with(['hub:id,name', 'merchant:id,business_name'])
            ->withCount([
                'items as items_count',
            ])
            ->whereIn('status', [GrnStatus::DRAFT, GrnStatus::IN_PROGRESS])
            ->latest();

        $this->applyHubScope($query, $request);

        $per = max(10, min(100, (int) $request->query('per_page', 25)));
        $rows = $query->paginate($per);

        // We want expected/received totals too — fetch in a second query
        // to avoid loading every item row into memory.
        $ids = $rows->pluck('id')->all();
        $sums = $ids
            ? DB::table('wms_grn_items')
                ->select('grn_id',
                    DB::raw('COALESCE(SUM(expected_qty),0) as expected'),
                    DB::raw('COALESCE(SUM(received_qty),0) as received'))
                ->whereIn('grn_id', $ids)
                ->groupBy('grn_id')
                ->get()
                ->keyBy('grn_id')
            : collect();

        return $this->responseWithSuccess('admin.wms.grns', [
            'grns' => $rows->through(function (WmsGrn $g) use ($sums) {
                $s = $sums->get($g->id);
                return [
                    'id'               => $g->id,
                    'grn_number'       => $g->grn_number,
                    'reference_number' => $g->reference_number,
                    'status'           => $g->status,
                    'hub_id'           => $g->hub_id,
                    'hub_name'         => optional($g->hub)->name,
                    'merchant_id'      => $g->merchant_id,
                    'merchant_name'    => optional($g->merchant)->business_name,
                    'items_count'      => (int) $g->items_count,
                    'expected_total'   => (int) ($s->expected ?? 0),
                    'received_total'   => (int) ($s->received ?? 0),
                    'received_at'      => optional($g->received_at)->toIso8601String(),
                ];
            }),
        ], 200);
    }

    /**
     * GET /admin/wms/locations
     * Bin/shelf list used by scan + adjustment pickers.
     */
    public function locations(Request $request)
    {
        $query = WmsLocation::companywise()
            ->with(['hub:id,name'])
            ->where('is_active', 1)
            ->orderBy('code');

        if ($hubId = $request->query('hub_id')) {
            $query->where('hub_id', (int) $hubId);
        }
        $this->applyHubScope($query, $request);

        $rows = $query->limit(500)->get();

        return $this->responseWithSuccess('admin.wms.locations', [
            'locations' => $rows->map(fn (WmsLocation $l) => [
                'id'       => $l->id,
                'code'     => $l->code,
                'zone'     => $l->zone,
                'type'     => $l->type,
                'hub_id'   => $l->hub_id,
                'hub_name' => optional($l->hub)->name,
            ])->values(),
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
}
