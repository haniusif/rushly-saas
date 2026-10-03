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
        $items = collect($f->items ?? [])->map(fn ($it) => [
            'id'                => $it->id,
            'sku'               => optional($it->product)->sku,
            'product'           => optional($it->product)->name,
            'location'          => optional($it->location)->code,
            'quantity_required' => (int) $it->quantity_required,
            'quantity_picked'   => (int) $it->quantity_picked,
            'status'            => $it->status,
            'status_label'      => ucfirst((string) $it->status),
        ])->values();

        // Pipeline stages with the timestamp that closes each one.
        $stages = [
            FulfillmentStatus::PENDING    => ['label' => 'Pending',    'at' => $f->created_at],
            FulfillmentStatus::PICKING    => ['label' => 'Picking',    'at' => $f->picked_at],
            FulfillmentStatus::PACKING    => ['label' => 'Packing',    'at' => $f->packed_at],
            FulfillmentStatus::READY      => ['label' => 'Ready',      'at' => null],
            FulfillmentStatus::DISPATCHED => ['label' => 'Dispatched', 'at' => $f->dispatched_at],
        ];
        $order      = array_keys($stages);
        $currentIdx = array_search($f->status, $order, true);
        $pipeline   = collect($stages)->map(function ($st, $key) use ($order, $currentIdx, $f) {
            $idx   = array_search($key, $order, true);
            $state = $f->status === FulfillmentStatus::CANCELLED ? 'todo'
                   : ($currentIdx !== false && $idx < $currentIdx ? 'done'
                   : ($key === $f->status ? 'now' : 'todo'));
            return [
                'key'   => $key,
                'label' => __($st['label']),
                'state' => $state,
                'at'    => $state === 'done' && $st['at'] ? $st['at']->diffForHumans() : null,
            ];
        })->values();

        return Inertia::render('Admin/Wms/Fulfillment/Show', [
            'fulfillment' => [
                'id'                 => $f->id,
                'fulfillment_number' => $f->fulfillment_number,
                'status'             => $f->status,
                'parcel'             => optional($f->parcel)->tracking_id ?? ('#' . $f->parcel_id),
                'parcel_url'         => $f->parcel_id ? route('parcel.details', $f->parcel_id) : null,
                'customer'           => optional($f->parcel)->customer_name,
                'merchant'           => optional($f->merchant)->business_name,
                'hub'                => optional($f->hub)->name,
                'picker'             => optional($f->picker)->name,
                'packer'             => optional($f->packer)->name,
                'sla_deadline'       => optional($f->sla_deadline)->toDateTimeString(),
                'sla_relative'       => optional($f->sla_deadline)->diffForHumans(),
                'sla_breached'       => $f->isSlaBreached(),
                'created_at'         => optional($f->created_at)->toDateTimeString(),
                'notes'              => $f->notes,
            ],
            'items'    => $items,
            'totals'   => [
                'lines'    => $items->count(),
                'required' => $items->sum('quantity_required'),
                'picked'   => $items->sum('quantity_picked'),
                'short'    => $items->where('status', 'short')->count(),
            ],
            'pipeline' => $pipeline,
            'permissions' => ['manage' => hasPermission('wms_manage')],
            'urls' => [
                'index'    => route('wms.fulfillment.index'),
                'picking'  => route('wms.fulfillment.picking', $f->id),
                'pack'     => route('wms.fulfillment.pack', $f->id),
                'dispatch' => route('wms.fulfillment.dispatch', $f->id),
            ],
            't' => [
                'title'            => __('Fulfillment') ?: 'Fulfillment',
                'list'             => __('Fulfillment') ?: 'Fulfillment',
                'back_to_list'     => __('Back to fulfillment') ?: 'Back to fulfillment',
                'start_picking'    => __('Start picking') ?: 'Start picking',
                'continue_picking' => __('Continue picking') ?: 'Continue picking',
                'confirm_pack'     => __('Confirm Pack') ?: 'Confirm pack',
                'dispatch'         => __('Dispatch') ?: 'Dispatch',
                'dispatch_confirm' => __('Dispatch this fulfillment? Stock will be deducted and the parcel handed to the courier workflow.'),
                'sla_breached'     => __('SLA breached') ?: 'SLA breached',
                'parcel'           => __('Parcel') ?: 'Parcel',
                'customer'         => __('Customer') ?: 'Customer',
                'merchant'         => __('Merchant') ?: 'Merchant',
                'hub'              => __('Hub') ?: 'Hub',
                'picker'           => __('Picker') ?: 'Picker',
                'packer'           => __('Packer') ?: 'Packer',
                'sla_deadline'     => __('SLA deadline') ?: 'SLA deadline',
                'created_at'       => __('levels.created_at') ?: 'Created',
                'notes'            => __('Notes') ?: 'Notes',
                'pipeline'         => __('Pipeline') ?: 'Pipeline',
                'lines'            => __('Lines') ?: 'Lines',
                'required'         => __('Required') ?: 'Required',
                'picked'           => __('Picked') ?: 'Picked',
                'short'            => __('Short') ?: 'Short',
                'items'            => __('Items') ?: 'Items',
                'product'          => __('Product') ?: 'Product',
                'location'         => __('Location') ?: 'Location',
                'status'           => __('levels.status') ?: 'Status',
                'no_items'         => __('No items.') ?: 'No items.',
            ],
        ]);
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

        $items     = $f->items;
        $remaining = $items->whereIn('status', ['pending', 'short'])->count();

        return Inertia::render('Admin/Wms/Fulfillment/Picking', [
            'fulfillment' => [
                'id'                 => $f->id,
                'fulfillment_number' => $f->fulfillment_number,
                'status'             => $f->status,
            ],
            'next' => $next ? [
                'id'                => $next->id,
                'product'           => optional($next->product)->name,
                'sku'               => optional($next->product)->sku,
                'location'          => optional($next->location)->code,
                'quantity_required' => (int) $next->quantity_required,
                'status'            => $next->status,
            ] : null,
            'progress' => [
                'total'     => $items->count(),
                'done'      => $items->where('status', 'picked')->count(),
                'remaining' => $remaining,
            ],
            'urls' => [
                'show' => route('wms.fulfillment.show', $f->id),
                'pick' => route('wms.fulfillment.pick', $f->id),
            ],
            't' => [
                'title'               => __('Picking') ?: 'Picking',
                'list'                => __('Fulfillment') ?: 'Fulfillment',
                'back_to_fulfillment' => __('Back to fulfillment') ?: 'Back to fulfillment',
                'progress'            => __(':done of :total items picked'),
                'walk_to'             => __('Walk to location') ?: 'Walk to location',
                'pick_this_many'      => __('Pick this many:') ?: 'Pick this many:',
                'picked_qty'          => __('Picked quantity (defaults to required)'),
                'short_hint'          => __('Less than required — the line will be marked short and offered again.'),
                'previously_short'    => __('Previously short') ?: 'Previously short',
                'confirm_pick'        => __('Confirm pick') ?: 'Confirm pick',
                'more_after'          => __(':n more item(s) after this one'),
                'all_done_title'      => __('All items picked!') ?: 'All items picked!',
                'all_done_body'       => __('Head back to the fulfillment to pack and dispatch.'),
                'continue'            => __('Continue') ?: 'Continue',
            ],
        ]);
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
