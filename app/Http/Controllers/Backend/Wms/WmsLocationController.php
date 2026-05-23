<?php

namespace App\Http\Controllers\Backend\Wms;

use App\Enums\Wms\LocationType;
use App\Http\Controllers\Controller;
use App\Models\Backend\Wms\WmsLocation;
use App\Repositories\Hub\HubInterface;
use App\Repositories\Wms\WmsLocationRepositoryInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class WmsLocationController extends Controller
{
    public function __construct(
        protected WmsLocationRepositoryInterface $repo,
        protected HubInterface $hubRepo
    ) {}

    public function index(Request $request)
    {
        $locations = $this->repo->all($request);
        $hubs      = $this->hubRepo->all();
        $types     = $this->typeOptions();
        return view('backend.wms.locations.index', compact('locations', 'hubs', 'types'));
    }

    public function map(Request $request)
    {
        $tree = $this->repo->tree($request->input('hub_id'));
        $hubs = $this->hubRepo->all();
        return view('backend.wms.locations.map', compact('tree', 'hubs'));
    }

    public function create()
    {
        $hubs  = $this->hubRepo->all();
        $types = $this->typeOptions();
        return view('backend.wms.locations.create', compact('hubs', 'types'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'hub_id'   => ['required', 'integer', 'exists:hubs,id'],
            'zone'     => ['nullable', 'string', 'max:191'],
            'aisle'    => ['nullable', 'string', 'max:191'],
            'rack'     => ['required', 'string', 'max:191'],
            'shelf'    => ['required', 'string', 'max:191'],
            'bin'      => ['nullable', 'string', 'max:191'],
            'type'     => ['required', 'string'],
            'capacity' => ['nullable', 'integer', 'min:0'],
            'code'     => ['nullable', 'string', 'max:191', 'unique:wms_locations,code'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);

        $loc = $this->repo->create($data);
        Toastr::success(__('Location created: :code', ['code' => $loc->code]));
        return redirect()->route('wms.locations.index');
    }

    public function show(int $id)
    {
        $location = $this->repo->find($id);
        if (!$location) return redirect()->route('wms.locations.index');
        return view('backend.wms.locations.show', compact('location'));
    }

    public function edit(int $id)
    {
        $location = $this->repo->find($id);
        if (!$location) return redirect()->route('wms.locations.index');
        $hubs  = $this->hubRepo->all();
        $types = $this->typeOptions();
        return view('backend.wms.locations.edit', compact('location', 'hubs', 'types'));
    }

    public function update(Request $request, int $id)
    {
        $location = $this->repo->find($id);
        if (!$location) return redirect()->route('wms.locations.index');

        $data = $request->validate([
            'hub_id'   => ['required', 'integer', 'exists:hubs,id'],
            'zone'     => ['nullable', 'string', 'max:191'],
            'aisle'    => ['nullable', 'string', 'max:191'],
            'rack'     => ['required', 'string', 'max:191'],
            'shelf'    => ['required', 'string', 'max:191'],
            'bin'      => ['nullable', 'string', 'max:191'],
            'type'     => ['required', 'string'],
            'capacity' => ['nullable', 'integer', 'min:0'],
            'code'     => ['nullable', 'string', 'max:191', 'unique:wms_locations,code,' . $location->id],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);

        $this->repo->update($location, $data);
        Toastr::success(__('Location updated.'));
        return redirect()->route('wms.locations.index');
    }

    public function destroy(int $id)
    {
        $location = $this->repo->find($id);
        if (!$location) return redirect()->route('wms.locations.index');
        $this->repo->delete($location);
        Toastr::success(__('Location deleted.'));
        return redirect()->route('wms.locations.index');
    }

    protected function typeOptions(): array
    {
        $rc = new \ReflectionClass(LocationType::class);
        return array_values($rc->getConstants());
    }
}
