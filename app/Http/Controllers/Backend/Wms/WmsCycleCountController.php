<?php

namespace App\Http\Controllers\Backend\Wms;

use App\Http\Controllers\Controller;
use App\Repositories\Hub\HubInterface;
use App\Repositories\Wms\WmsCycleCountRepositoryInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WmsCycleCountController extends Controller
{
    public function __construct(
        protected WmsCycleCountRepositoryInterface $repo,
        protected HubInterface $hubRepo
    ) {}

    public function index(Request $request)
    {
        $counts = $this->repo->all($request);
        $hubs   = $this->hubRepo->all();
        return view('backend.wms.cycle_counts.index', compact('counts', 'hubs'));
    }

    public function create()
    {
        $hubs = $this->hubRepo->all();
        $next = $this->repo->nextCountNumber();
        return view('backend.wms.cycle_counts.create', compact('hubs', 'next'));
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
