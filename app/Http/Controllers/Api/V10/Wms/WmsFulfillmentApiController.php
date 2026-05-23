<?php

namespace App\Http\Controllers\Api\V10\Wms;

use App\Enums\Wms\FulfillmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Backend\Wms\WmsFulfillment;
use App\Repositories\Wms\WmsFulfillmentRepositoryInterface;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WmsFulfillmentApiController extends Controller
{
    use ApiReturnFormatTrait;

    public function __construct(protected WmsFulfillmentRepositoryInterface $repo) {}

    /**
     * GET /api/v10/wms/fulfillment/my-tasks
     * Returns the picker's current queue: unassigned-or-assigned-to-me fulfillments
     * still in pending/picking state.
     */
    public function myTasks(Request $request)
    {
        $uid = Auth::id();
        $tasks = WmsFulfillment::companywise()
            ->whereIn('status', [FulfillmentStatus::PENDING, FulfillmentStatus::PICKING])
            ->where(function ($q) use ($uid) {
                $q->whereNull('picker_id')->orWhere('picker_id', $uid);
            })
            ->with(['parcel', 'hub', 'items.product', 'items.location'])
            ->orderBy('sla_deadline')
            ->limit(20)
            ->get();

        return $this->responseWithSuccess('Picker queue', [
            'count' => $tasks->count(),
            'tasks' => $tasks->map(fn ($f) => [
                'id'                 => $f->id,
                'fulfillment_number' => $f->fulfillment_number,
                'status'             => $f->status,
                'parcel'             => optional($f->parcel)->tracking_id,
                'hub'                => optional($f->hub)->name,
                'sla_deadline'       => (string) $f->sla_deadline,
                'sla_breached'       => $f->isSlaBreached(),
                'items_remaining'    => $f->items->whereIn('status', ['pending','short'])->count(),
                'items_total'        => $f->items->count(),
                'next_item'          => $f->items->whereIn('status', ['pending','short'])
                    ->sortBy(fn ($i) => optional($i->location)->code ?? '')
                    ->first()?->only(['id','product_id','location_id','quantity_required','quantity_picked']),
            ]),
        ], 200);
    }

    /**
     * POST /api/v10/wms/fulfillment/{id}/pick
     * Body: { item_id, picked_qty }
     */
    public function confirmPick(Request $request, int $id)
    {
        $f = $this->repo->find($id);
        if (!$f) return $this->responseWithError('Fulfillment not found', [], 404);

        $request->validate([
            'item_id'    => ['required', 'integer'],
            'picked_qty' => ['required', 'integer', 'min:0'],
        ]);

        $this->repo->confirmPick($f, Auth::id(), [(int) $request->input('item_id') => (int) $request->input('picked_qty')]);
        $f->refresh();

        return $this->responseWithSuccess('Pick recorded', [
            'fulfillment_id' => $f->id,
            'status'         => $f->status,
            'items_remaining'=> $f->items->whereIn('status', ['pending','short'])->count(),
        ], 200);
    }

    /**
     * POST /api/v10/wms/fulfillment/{id}/pack
     */
    public function confirmPack(int $id)
    {
        $f = $this->repo->find($id);
        if (!$f) return $this->responseWithError('Fulfillment not found', [], 404);
        if ($f->status !== FulfillmentStatus::PACKING) {
            return $this->responseWithError('Not in packing state', ['status' => $f->status], 409);
        }
        $this->repo->confirmPack($f, Auth::id());
        $f->refresh();
        return $this->responseWithSuccess('Packed', ['status' => $f->status], 200);
    }
}
