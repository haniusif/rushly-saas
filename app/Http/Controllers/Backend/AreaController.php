<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\ManagesReferenceData;
use App\Http\Controllers\Controller;
use App\Models\Backend\Area;
use App\Models\Backend\City;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Areas admin. Like countries and cities, the routes existed with no
 * controller behind them. `areas` has no company_id — see ManagesReferenceData.
 *
 * This is the largest of the three sets (518 rows at time of writing), so the
 * list is filterable by city as well as by name and status.
 */
class AreaController extends Controller
{
    use ManagesReferenceData;

    /** Tables that point at an area; delete is refused while any row does. */
    private const REFERENCES = [
        'parcels' => 'area_id',
    ];

    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status', '');
        $cityId = $request->input('city_id', '');

        $paginator = Area::query()
            ->with('city:id,name')
            ->when($search !== '', fn ($q) => $q->where(function ($w) use ($search) {
                $w->where('name', 'like', "%{$search}%")
                  ->orWhere('en_name', 'like', "%{$search}%")
                  ->orWhere('area_code', 'like', "%{$search}%");
            }))
            ->when($status !== '', fn ($q) => $q->where('is_active', (int) $status))
            ->when($cityId !== '', fn ($q) => $q->where('city_id', $cityId))
            ->orderBy('sorting')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $rows = collect($paginator->items())->map(fn ($a) => [
            'id'        => $a->id,
            'name'      => (string) $a->name,
            'en_name'   => (string) $a->en_name,
            'area_code' => (string) $a->area_code,
            'city'      => optional($a->city)->name,
            'sorting'   => $a->sorting,
            'is_active' => (bool) $a->is_active,
            'urls'      => [
                'edit'   => route('area.edit', $a->id),
                'delete' => route('area.delete', $a->id),
            ],
        ])->values();

        return Inertia::render('Admin/Area/Index', [
            'rows'        => $rows,
            'pagination'  => $this->paginateMeta($paginator),
            'filters'     => [
                'search'  => $search,
                'status'  => (string) $status,
                'city_id' => (string) $cityId,
            ],
            'lookups'     => ['cities' => $this->cityOptions()],
            'permissions' => [
                'create' => hasPermission('area_create'),
                'update' => hasPermission('area_update'),
                'delete' => hasPermission('area_delete'),
            ],
            'urls' => [
                'index'  => route('area.index'),
                'create' => route('area.create'),
            ],
            't' => $this->labels([
                'title'          => __('menus.area') ?: 'Areas',
                'code'           => __('area.code') ?: 'Code',
                'en_name'        => __('country.en_name') ?: 'English name',
                'city'           => __('menus.city_single') ?: 'City',
                'no_rows'        => 'No areas found.',
                'delete_confirm' => 'Delete this area?',
                'search_ph'      => 'Name or code',
            ]),
        ]);
    }

    public function create()
    {
        return $this->form('create', null);
    }

    public function edit($id)
    {
        $area = Area::find($id);
        if (! $area) {
            abort(404);
        }
        return $this->form('edit', $area);
    }

    public function store(Request $request)
    {
        Area::create($this->validated($request));
        Toastr::success('Area successfully added.', __('message.success'));

        return redirect()->route('area.index');
    }

    public function update(Request $request, $id)
    {
        $area = Area::find($id);
        if (! $area) {
            abort(404);
        }

        $area->update($this->validated($request));
        Toastr::success('Area successfully updated.', __('message.success'));

        return redirect()->route('area.index');
    }

    public function destroy($id)
    {
        $area = Area::find($id);
        if (! $area) {
            abort(404);
        }

        if ($reason = $this->blockingReferences(self::REFERENCES, $area->id)) {
            Toastr::error($reason, __('message.error'));
            return back();
        }

        $area->delete();
        Toastr::success('Area successfully deleted.', __('message.success'));

        return back();
    }

    private function form(string $mode, ?Area $area)
    {
        return Inertia::render('Admin/Area/Form', [
            'mode'   => $mode,
            'entity' => $area ? [
                'id'        => $area->id,
                'city_id'   => $area->city_id,
                'name'      => (string) $area->name,
                'en_name'   => (string) $area->en_name,
                'area_code' => (string) $area->area_code,
                'sorting'   => $area->sorting,
                'is_active' => (bool) $area->is_active,
            ] : null,
            'lookups' => ['cities' => $this->cityOptions()],
            'urls' => [
                'submit' => $area ? route('area.update', $area->id) : route('area.store'),
                'cancel' => route('area.index'),
            ],
            't' => $this->labels([
                'title'      => $area ? 'Edit area' : 'Add area',
                'list_title' => __('menus.area') ?: 'Areas',
                'code'       => __('area.code') ?: 'Code',
                'en_name'    => __('country.en_name') ?: 'English name',
                'city'       => __('menus.city_single') ?: 'City',
                'sorting_hint' => 'Lower numbers appear first.',
            ]),
        ]);
    }

    private function cityOptions(): array
    {
        return City::query()
            ->orderBy('sorting')->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($c) => ['id' => $c->id, 'name' => (string) $c->name])
            ->all();
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'city_id'   => ['required', 'integer', 'exists:cities,id'],
            'name'      => ['required', 'string', 'max:255'],
            'en_name'   => ['nullable', 'string', 'max:255'],
            'area_code' => ['nullable', 'string', 'max:50'],
            'sorting'   => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $data['sorting']   = $data['sorting'] ?? 0;

        return $data;
    }
}
