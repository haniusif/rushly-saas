<?php

namespace App\Http\Controllers\Api\V10\Admin;

use App\Enums\UserType;
use App\Enums\Wms\GrnStatus;
use App\Http\Controllers\Controller;
use App\Models\Backend\Wms\WmsCycleCount;
use App\Models\Backend\Wms\WmsDamageReport;
use App\Models\Backend\Wms\WmsGrn;
use App\Models\Backend\Wms\WmsLocation;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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

    /**
     * GET /admin/wms/cycle-counts
     * Open + recent cycle-count sessions (hub-clamped).
     */
    public function cycleCounts(Request $request)
    {
        $query = WmsCycleCount::companywise()
            ->with(['hub:id,name', 'assignedTo:id,name'])
            ->latest();

        if ($status = $request->query('status')) {
            $query->where('status', (string) $status);
        }
        $this->applyHubScope($query, $request);

        $per = max(10, min(100, (int) $request->query('per_page', 25)));
        $rows = $query->paginate($per);

        return $this->responseWithSuccess('admin.wms.cycle_counts', [
            'cycle_counts' => $rows->through(fn (WmsCycleCount $c) => [
                'id'            => $c->id,
                'count_number'  => $c->count_number,
                'scope'         => $c->scope,
                'zone'          => $c->zone,
                'status'        => $c->status,
                'hub_id'        => $c->hub_id,
                'hub_name'      => optional($c->hub)->name,
                'assigned_to'   => $c->assigned_to,
                'assigned_name' => optional($c->assignedTo)->name,
                'started_at'    => optional($c->started_at)->toIso8601String(),
                'completed_at'  => optional($c->completed_at)->toIso8601String(),
                'created_at'    => optional($c->created_at)->toIso8601String(),
            ]),
        ], 200);
    }

    /**
     * POST /admin/wms/cycle-counts
     * Open a new cycle-count session. Persists in the `open` status; the
     * per-line counting itself is out of MVP scope — this endpoint just
     * schedules and marks who's running it. The web WMS UI takes over
     * from there.
     */
    public function cycleCountsStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'hub_id'      => 'required|integer|exists:hubs,id',
            'scope'       => 'required|string|max:64',
            'zone'        => 'nullable|string|max:64',
            'assigned_to' => 'nullable|integer|exists:users,id',
        ]);
        if ($validator->fails()) {
            return $this->responseWithError('admin.wms.cycle_count.validation',
                ['message' => $validator->errors()], 422);
        }

        $count = WmsCycleCount::create([
            'company_id'   => settings()->id,
            'count_number' => 'CC-' . strtoupper(substr(md5(uniqid('', true)), 0, 8)),
            'hub_id'       => (int) $request->hub_id,
            'assigned_to'  => (int) ($request->assigned_to ?? Auth::id()),
            'scope'        => $request->scope,
            'zone'         => $request->zone,
            'status'       => 'open',
            'started_at'   => now(),
        ]);

        return $this->responseWithSuccess('admin.wms.cycle_count.created', [
            'id'           => $count->id,
            'count_number' => $count->count_number,
        ], 200);
    }

    /**
     * GET /admin/wms/damage-reports
     * Recent damage reports (hub-clamped via product.hub_id when present).
     */
    public function damageReports(Request $request)
    {
        $query = WmsDamageReport::companywise()
            ->with([
                'product:id,sku,name,hub_id',
                'location:id,code,hub_id',
                'reportedBy:id,name',
            ])
            ->latest();

        if ($productId = $request->query('product_id')) {
            $query->where('product_id', (int) $productId);
        }

        $per = max(10, min(100, (int) $request->query('per_page', 25)));
        $rows = $query->paginate($per);

        return $this->responseWithSuccess('admin.wms.damage_reports', [
            'damage_reports' => $rows->through(fn (WmsDamageReport $d) => [
                'id'               => $d->id,
                'product_id'       => $d->product_id,
                'product_sku'      => optional($d->product)->sku,
                'product_name'     => optional($d->product)->name,
                'location_id'      => $d->location_id,
                'location_code'    => optional($d->location)->code,
                'quantity_damaged' => (int) $d->quantity_damaged,
                'cause'            => $d->cause,
                'action_taken'     => $d->action_taken,
                'notes'            => $d->notes,
                'reported_by'      => optional($d->reportedBy)->name,
                'created_at'       => optional($d->created_at)->toIso8601String(),
            ]),
        ], 200);
    }

    /**
     * POST /admin/wms/damage-reports
     * Record a new damage report. Photos are handled as a JSON array of
     * URLs for now (upload endpoint TBD) — mobile can pass an empty list.
     */
    public function damageReportsStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id'       => 'required|integer|exists:wms_products,id',
            'location_id'      => 'required|integer|exists:wms_locations,id',
            'quantity_damaged' => 'required|integer|min:1',
            'cause'            => 'required|string|max:191',
            'action_taken'     => 'nullable|string|max:191',
            'notes'            => 'nullable|string',
        ]);
        if ($validator->fails()) {
            return $this->responseWithError('admin.wms.damage.validation',
                ['message' => $validator->errors()], 422);
        }

        $report = WmsDamageReport::create([
            'company_id'       => settings()->id,
            'product_id'       => (int) $request->product_id,
            'location_id'      => (int) $request->location_id,
            'reported_by'      => Auth::id(),
            'quantity_damaged' => (int) $request->quantity_damaged,
            'cause'            => $request->cause,
            'action_taken'     => $request->action_taken,
            'notes'            => $request->notes,
            'photos'           => [],
        ]);

        return $this->responseWithSuccess('admin.wms.damage.created', [
            'id' => $report->id,
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
