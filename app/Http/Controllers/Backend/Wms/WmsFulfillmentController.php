<?php

namespace App\Http\Controllers\Backend\Wms;

use App\Enums\Wms\FulfillmentStatus;
use App\Http\Controllers\Backend\Wms\Concerns\RendersInertiaIndex;
use App\Http\Controllers\Controller;
use App\Models\Backend\Wms\WmsFulfillment;
use App\Models\Backend\Wms\WmsFulfillmentItem;
use App\Repositories\Hub\HubInterface;
use App\Repositories\Wms\WmsFulfillmentRepositoryInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WmsFulfillmentController extends Controller
{
    use RendersInertiaIndex;

    public function __construct(
        protected WmsFulfillmentRepositoryInterface $repo,
        protected HubInterface $hubRepo
    ) {}

    public function index(Request $request)
    {
        $paginator = $this->repo->all($request);
        $hubs      = $this->hubRepo->all();

        $base = WmsFulfillment::companywise();
        $summary = [
            'pending'      => (clone $base)->where('status', FulfillmentStatus::PENDING)->count(),
            'picking'      => (clone $base)->where('status', FulfillmentStatus::PICKING)->count(),
            'packing'      => (clone $base)->where('status', FulfillmentStatus::PACKING)->count(),
            'ready'        => (clone $base)->where('status', FulfillmentStatus::READY)->count(),
            'dispatched_today' => (clone $base)->where('status', FulfillmentStatus::DISPATCHED)
                                  ->whereDate('dispatched_at', today())->count(),
            'sla_breached' => $this->repo->breachedSla()->count(),
        ];

        $rows = collect($paginator->items())->map(fn ($f) => [
            'id'             => $f->id,
            'fulfillment_no' => $f->fulfillment_number,
            'parcel_label'   => optional($f->parcel)->tracking_id ?? ('#' . $f->parcel_id),
            'merchant'       => optional($f->merchant)->business_name,
            'hub'            => optional($f->hub)->name,
            'picker'         => optional($f->picker)->name,
            'status'         => $f->status,
            'status_label'   => ucwords(str_replace('_', ' ', $f->status)),
            'sla_deadline'   => optional($f->sla_deadline)->diffForHumans(),
            'sla_overdue'    => $f->sla_deadline && $f->sla_deadline->isPast()
                                && !in_array($f->status, [FulfillmentStatus::DISPATCHED, FulfillmentStatus::CANCELLED]),
            'url'            => route('wms.fulfillment.show', $f->id),
        ])->values();

        return Inertia::render('Admin/Wms/Fulfillment/Index', [
            'rows'        => $rows,
            'pagination'  => $this->paginateMeta($paginator),
            'filters'     => [
                'status'        => $request->input('status', ''),
                'hub_id'        => $request->input('hub_id', ''),
                'sla_breached'  => (bool) $request->boolean('sla_breached'),
            ],
            'summary'     => array_map('intval', $summary),
            'lookups'     => [
                'statuses' => ['pending', 'picking', 'packing', 'ready', 'dispatched', 'cancelled'],
                'hubs'     => $this->lookupRows($hubs, fn ($h) => ['id' => $h->id, 'name' => $h->name]),
            ],
            'urls' => [
                'index' => route('wms.fulfillment.index'),
            ],
            't' => $this->indexLabels([
                'title' => 'Fulfillment', 'fulfillment_number' => 'Fulfillment #',
                'parcel' => 'Parcel', 'merchant' => 'Merchant', 'hub' => 'Hub', 'picker' => 'Picker', 'sla' => 'SLA',
                'sla_breached' => 'SLA breached', 'overdue' => 'Overdue',
            ]),
        ]);
    }

    public function show(int $id)
    {
        $f = $this->repo->find($id);
        if (!$f) {
            Toastr::error(__('Fulfillment not found.'));
            return redirect()->route('wms.fulfillment.index');
        }
        return view('backend.wms.fulfillment.show', compact('f'));
    }

    public function picking(int $id)
    {
        $f = $this->repo->find($id);
        if (!$f) return redirect()->route('wms.fulfillment.index');

        // The picker UI shows ONE pending/short item at a time, in location-code order
        // so the picker walks the warehouse efficiently.
        $next = $f->items()
            ->whereIn('status', ['pending', 'short'])
            ->with(['product', 'location'])
            ->get()
            ->sortBy(fn ($i) => optional($i->location)->code ?? '')
            ->first();

        return view('backend.wms.fulfillment.picking', compact('f', 'next'));
    }

    public function confirmPick(Request $request, int $id)
    {
        $f = $this->repo->find($id);
        if (!$f) return redirect()->route('wms.fulfillment.index');

        $request->validate([
            'picks'        => ['nullable', 'array'],
            'picks.*'      => ['integer', 'min:0'],
            'item_id'      => ['nullable', 'integer'],
            'picked_qty'   => ['nullable', 'integer', 'min:0'],
        ]);

        // Picker UI submits one item at a time → use `item_id` + `picked_qty`.
        // Full-page submit can also send a `picks` map [itemId => qty].
        $picks = $request->input('picks', []);
        if ($request->filled('item_id') && $request->filled('picked_qty')) {
            $picks[(int) $request->input('item_id')] = (int) $request->input('picked_qty');
        }

        if (!$picks) {
            Toastr::warning(__('Nothing to pick.'));
            return redirect()->route('wms.fulfillment.picking', $f->id);
        }

        $this->repo->confirmPick($f, Auth::id(), $picks);
        $f->refresh();

        // If still picking → next item; if moved to packing → land on show page.
        if ($f->status === FulfillmentStatus::PICKING) {
            return redirect()->route('wms.fulfillment.picking', $f->id);
        }

        Toastr::success(__('All items picked. Ready to pack.'));
        return redirect()->route('wms.fulfillment.show', $f->id);
    }

    public function confirmPack(int $id)
    {
        $f = $this->repo->find($id);
        if (!$f) return redirect()->route('wms.fulfillment.index');

        if ($f->status !== FulfillmentStatus::PACKING) {
            Toastr::warning(__('This fulfillment is not in packing state.'));
            return redirect()->route('wms.fulfillment.show', $f->id);
        }

        $this->repo->confirmPack($f, Auth::id());
        Toastr::success(__('Packed and ready to ship.'));
        return redirect()->route('wms.fulfillment.show', $f->id);
    }

    public function dispatchOrder(int $id)
    {
        $f = $this->repo->find($id);
        if (!$f) return redirect()->route('wms.fulfillment.index');

        if ($f->status !== FulfillmentStatus::READY) {
            Toastr::warning(__('Cannot dispatch — fulfillment must be in READY state.'));
            return redirect()->route('wms.fulfillment.show', $f->id);
        }

        try {
            $this->repo->dispatch($f);
            Toastr::success(__('Dispatched. Parcel handed off to courier workflow.'));
        } catch (\Throwable $e) {
            Toastr::error(__('Dispatch failed: :msg', ['msg' => $e->getMessage()]));
        }
        return redirect()->route('wms.fulfillment.show', $f->id);
    }
}
