<?php

namespace App\Http\Controllers\Backend\Wms;

use App\Http\Controllers\Backend\Wms\Concerns\RendersInertiaIndex;
use App\Http\Controllers\Controller;
use App\Repositories\Hub\HubInterface;
use App\Repositories\Wms\WmsCycleCountRepositoryInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WmsCycleCountController extends Controller
{
    use RendersInertiaIndex;

    public function __construct(
        protected WmsCycleCountRepositoryInterface $repo,
        protected HubInterface $hubRepo
    ) {}

    public function index(Request $request)
    {
        $paginator = $this->repo->all($request);
        $hubs      = $this->hubRepo->all();

        $rows = collect($paginator->items())->map(fn ($c) => [
            'id'           => $c->id,
            'count_number' => $c->count_number,
            'hub'          => optional($c->hub)->name,
            'scope'        => $c->scope,
            'zone'         => $c->zone,
            'assigned_to'  => optional($c->assignedTo)->name,
            'status'       => $c->status,
            'status_label' => ucwords(str_replace('_', ' ', $c->status)),
            'started_at'   => optional($c->started_at)->diffForHumans(),
            'completed_at' => optional($c->completed_at)->diffForHumans(),
            'url'          => route('wms.cycle-counts.show', $c->id),
        ])->values();

        return Inertia::render('Admin/Wms/CycleCounts/Index', [
            'rows'        => $rows,
            'pagination'  => $this->paginateMeta($paginator),
            'lookups'     => [
                'hubs' => $this->lookupRows($hubs, fn ($h) => ['id' => $h->id, 'name' => $h->name]),
            ],
            'permissions' => ['create' => hasPermission('wms_manage')],
            'urls' => [
                'index'  => route('wms.cycle-counts.index'),
                'create' => route('wms.cycle-counts.create'),
            ],
            't' => $this->indexLabels([
                'title' => 'Cycle counts', 'count_number' => 'Count #', 'hub' => 'Hub',
                'scope' => 'Scope', 'assigned' => 'Assigned', 'started' => 'Started', 'completed' => 'Completed',
            ]),
        ]);
    }

    public function create()
    {
        $hubs = $this->hubRepo->all();
        $next = $this->repo->nextCountNumber();

        return Inertia::render('Admin/Wms/CycleCounts/Create', [
            'lookups' => [
                'hubs'   => $this->lookupRows($hubs, fn ($h) => ['id' => $h->id, 'name' => $h->name]),
                'scopes' => [
                    ['value' => 'zone',  'label' => 'Single zone',  'hint' => 'Count one zone within the hub'],
                    ['value' => 'aisle', 'label' => 'Single aisle', 'hint' => 'Count one aisle within the hub'],
                    ['value' => 'full',  'label' => 'Full hub',     'hint' => 'Count every location in the hub'],
                ],
            ],
            'next_number' => $next,
            'urls' => [
                'submit' => route('wms.cycle-counts.store'),
                'cancel' => route('wms.cycle-counts.index'),
            ],
            't' => [
                'title'        => 'New cycle count',
                'title_index'  => 'Cycle counts',
                'count_number' => 'Count #',
                'hub'          => 'Hub',
                'scope'        => 'Scope',
                'zone'         => 'Zone',
                'zone_hint'    => 'Required when scope is "Single zone"',
                'cancel'       => __('levels.cancel') ?: 'Cancel',
                'save'         => 'Create count',
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'hub_id' => ['required', 'integer', 'exists:hubs,id'],
            'scope'  => ['required', 'string', 'in:zone,aisle,full'],
            'zone'   => ['nullable', 'string', 'max:191'],
        ]);
        $data['assigned_to'] = Auth::id();
        $c = $this->repo->create($data);
        Toastr::success(__('Cycle count :n created.', ['n' => $c->count_number]));
        return redirect()->route('wms.cycle-counts.show', $c->id);
    }

    public function show(int $id)
    {
        $count = $this->repo->find($id);
        if (!$count) return redirect()->route('wms.cycle-counts.index');

        // Pre-load the products expected at the in-scope locations so the count sheet
        // can show expected qty vs (admin-entered) counted qty.
        $stockQuery = \App\Models\Backend\Wms\WmsStock::companywise()
            ->with(['product', 'location'])
            ->whereHas('location', fn ($q) => $q->where('hub_id', $count->hub_id));
        if ($count->scope === 'zone' && $count->zone) {
            $stockQuery->whereHas('location', fn ($q) => $q->where('zone', $count->zone));
        }
        $stockRows = $stockQuery->orderBy('location_id')->get();

        return view('backend.wms.cycle_counts.show', compact('count', 'stockRows'));
    }

    public function edit() { abort(404); }
    public function update(Request $request, int $id)
    {
        $count = $this->repo->find($id);
        if (!$count) return redirect()->route('wms.cycle-counts.index');

        if ($request->input('action') === 'start') {
            $this->repo->start($count);
            Toastr::success(__('Count started.'));
        } elseif ($request->input('action') === 'complete') {
            $this->repo->complete($count);
            Toastr::success(__('Count completed.'));
        }
        return redirect()->route('wms.cycle-counts.show', $count->id);
    }
    public function destroy() { abort(404); }
}
