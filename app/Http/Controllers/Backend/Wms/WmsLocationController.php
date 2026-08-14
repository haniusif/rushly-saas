<?php

namespace App\Http\Controllers\Backend\Wms;

use App\Enums\Wms\LocationType;
use App\Http\Controllers\Backend\Wms\Concerns\RendersInertiaIndex;
use App\Http\Controllers\Controller;
use App\Models\Backend\Wms\WmsLocation;
use App\Repositories\Hub\HubInterface;
use App\Repositories\Wms\WmsLocationRepositoryInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WmsLocationController extends Controller
{
    use RendersInertiaIndex;

    public function __construct(
        protected WmsLocationRepositoryInterface $repo,
        protected HubInterface $hubRepo
    ) {}

    public function index(Request $request)
    {
        $paginator = $this->repo->all($request);
        $hubs      = $this->hubRepo->all();
        $types     = $this->typeOptions();

        $rows = collect($paginator->items())->map(fn ($l) => [
            'id'       => $l->id,
            'code'     => $l->code,
            'hub'      => optional($l->hub)->name,
            'zone'     => $l->zone,
            'aisle'    => $l->aisle,
            'rack'     => $l->rack,
            'shelf'    => $l->shelf,
            'bin'      => $l->bin,
            'type'     => $l->type,
            'capacity' => $l->capacity,
            'url'      => route('wms.locations.edit', $l->id),
        ])->values();

        return Inertia::render('Admin/Wms/Locations/Index', [
            'rows'        => $rows,
            'pagination'  => $this->paginateMeta($paginator),
            'filters'     => [
                'hub_id' => $request->input('hub_id', ''),
                'zone'   => $request->input('zone', ''),
                'aisle'  => $request->input('aisle', ''),
                'type'   => $request->input('type', ''),
            ],
            'lookups'     => [
                'hubs'  => $this->lookupRows($hubs, fn ($h) => ['id' => $h->id, 'name' => $h->name]),
                'types' => collect($types)->map(fn ($l, $k) => ['value' => $k, 'label' => $l])->values(),
            ],
            'permissions' => ['create' => hasPermission('wms_manage')],
            'urls' => [
                'index'  => route('wms.locations.index'),
                'create' => route('wms.locations.create'),
                'map'    => route('wms.locations.map'),
            ],
            't' => $this->indexLabels([
                'title' => 'Storage locations', 'code' => 'Code', 'hub' => 'Hub',
                'zone' => 'Zone', 'aisle' => 'Aisle', 'rack' => 'Rack',
                'shelf' => 'Shelf', 'bin' => 'Bin', 'type' => 'Type', 'capacity' => 'Capacity',
                'map_view' => 'Map view',
            ]),
        ]);
    }

    public function map(Request $request)
    {
        $hubId = $request->input('hub_id');
        $rawTree = $this->repo->tree($hubId);
        $hubs    = $this->hubRepo->all();

        // Flatten ['zone' => ['aisle' => [location, ...]]] into structured arrays
        // the React tree view can iterate cleanly.
        $zones = [];
        foreach ($rawTree as $zoneName => $aisles) {
            $aisleList = [];
            foreach ($aisles as $aisleName => $locations) {
                $aisleList[] = [
                    'name'      => $aisleName,
                    'locations' => collect($locations)->map(fn ($l) => [
                        'id'       => $l->id,
                        'code'     => $l->code,
                        'rack'     => $l->rack,
                        'shelf'    => $l->shelf,
                        'bin'      => $l->bin,
                        'type'     => $l->type,
                        'capacity' => $l->capacity,
                        'url'      => route('wms.locations.edit', $l->id),
                    ])->values(),
                ];
            }
            $zones[] = ['name' => $zoneName, 'aisles' => $aisleList];
        }

        return Inertia::render('Admin/Wms/Locations/Map', [
            'zones'  => $zones,
            'filters' => ['hub_id' => $hubId ? (int) $hubId : ''],
            'lookups' => [
                'hubs' => $this->lookupRows($hubs, fn ($h) => ['id' => $h->id, 'name' => $h->name]),
            ],
            'urls' => [
                'index'  => route('wms.locations.index'),
                'map'    => route('wms.locations.map'),
                'create' => route('wms.locations.create'),
            ],
            'permissions' => ['create' => hasPermission('wms_manage')],
            't' => $this->indexLabels([
                'title'        => 'Locations map',
                'list_view'    => 'List view',
                'map_view'     => 'Map view',
                'all_hubs'     => 'All hubs',
                'zone'         => 'Zone',
                'aisle'        => 'Aisle',
                'rack'         => 'Rack',
                'shelf'        => 'Shelf',
                'bin'          => 'Bin',
                'type'         => 'Type',
                'capacity'     => 'Capacity',
                'no_locations' => 'No storage locations defined yet.',
                'edit'         => 'Edit',
            ]),
        ]);
    }

    public function create()
    {
        $hubs  = $this->hubRepo->all();
        $types = $this->typeOptions();

        return Inertia::render('Admin/Wms/Locations/Create', [
            'mode'    => 'create',
            'lookups' => [
                'hubs'  => $this->lookupRows($hubs, fn ($h) => ['id' => $h->id, 'name' => $h->name]),
                'types' => $types,
            ],
            'urls' => [
                'submit' => route('wms.locations.store'),
                'cancel' => route('wms.locations.index'),
            ],
            't' => $this->indexLabels([
                'title'    => 'New storage location',
                'list'     => 'Locations',
                'identity' => 'Identity',
                'address'  => 'Hierarchy',
                'options'  => 'Options',
                'hub'      => 'Hub',
                'zone'     => 'Zone',
                'aisle'    => 'Aisle',
                'rack'     => 'Rack',
                'shelf'    => 'Shelf',
                'bin'      => 'Bin',
                'type'     => 'Type',
                'capacity' => 'Capacity',
                'code'     => 'Code',
                'is_active'=> 'Active',
                'save'     => __('levels.submit') ?: 'Save',
                'cancel'   => __('levels.cancel') ?: 'Cancel',
                'code_hint'=> 'Leave blank to auto-generate from rack/shelf/bin.',
                'zone_hint'=> 'Optional grouping (e.g. cold, dry).',
            ]),
        ]);
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
