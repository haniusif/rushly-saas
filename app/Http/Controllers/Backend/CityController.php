<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\ManagesReferenceData;
use App\Http\Controllers\Controller;
use App\Models\Backend\City;
use App\Models\Backend\Country;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Cities admin. Like countries, the routes existed with no controller behind
 * them. `cities` has no company_id — see ManagesReferenceData.
 */
class CityController extends Controller
{
    use ManagesReferenceData;

    /** Tables that point at a city; delete is refused while any row does. */
    private const REFERENCES = [
        'areas'          => 'city_id',
        'parcels'        => 'city_id',
        'merchant_cities'=> 'city_id',
    ];

    public function index(Request $request)
    {
        $search    = trim((string) $request->input('search', ''));
        $status    = $request->input('status', '');
        $countryId = $request->input('country_id', '');

        $paginator = City::query()
            ->with('country:id,name')
            ->when($search !== '', fn ($q) => $q->where(function ($w) use ($search) {
                $w->where('name', 'like', "%{$search}%")
                  ->orWhere('en_name', 'like', "%{$search}%")
                  ->orWhere('city_code', 'like', "%{$search}%");
            }))
            ->when($status !== '', fn ($q) => $q->where('is_active', (int) $status))
            ->when($countryId !== '', fn ($q) => $q->where('country_id', $countryId))
            ->withCount('areas')
            ->orderBy('sorting')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $rows = collect($paginator->items())->map(fn ($c) => [
            'id'          => $c->id,
            'name'        => (string) $c->name,
            'en_name'     => (string) $c->en_name,
            'city_code'   => (string) $c->city_code,
            'country'     => optional($c->country)->name,
            'sorting'     => $c->sorting,
            'is_active'   => (bool) $c->is_active,
            'areas_count' => (int) ($c->areas_count ?? 0),
            'urls'        => [
                'edit'   => route('city.edit', $c->id),
                'delete' => route('city.delete', $c->id),
            ],
        ])->values();

        return Inertia::render('Admin/City/Index', [
            'rows'        => $rows,
            'pagination'  => $this->paginateMeta($paginator),
            'filters'     => [
                'search'     => $search,
                'status'     => (string) $status,
                'country_id' => (string) $countryId,
            ],
            'lookups'     => ['countries' => $this->countryOptions()],
            'permissions' => [
                'create' => hasPermission('city_create'),
                'update' => hasPermission('city_update'),
                'delete' => hasPermission('city_delete'),
            ],
            'urls' => [
                'index'  => route('city.index'),
                'create' => route('city.create'),
            ],
            't' => $this->labels([
                'title'          => __('menus.city') ?: 'Cities',
                'code'           => __('city.code') ?: 'Code',
                'en_name'        => __('country.en_name') ?: 'English name',
                'country'        => __('country.title_single') ?: 'Country',
                'areas'          => __('menus.area') ?: 'Areas',
                'no_rows'        => 'No cities found.',
                'delete_confirm' => 'Delete this city?',
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
        $city = City::find($id);
        if (! $city) {
            abort(404);
        }
        return $this->form('edit', $city);
    }

    public function store(Request $request)
    {
        City::create($this->validated($request));
        Toastr::success('City successfully added.', __('message.success'));

        return redirect()->route('city.index');
    }

    public function update(Request $request, $id)
    {
        $city = City::find($id);
        if (! $city) {
            abort(404);
        }

        $city->update($this->validated($request));
        Toastr::success('City successfully updated.', __('message.success'));

        return redirect()->route('city.index');
    }

    public function destroy($id)
    {
        $city = City::find($id);
        if (! $city) {
            abort(404);
        }

        if ($reason = $this->blockingReferences(self::REFERENCES, $city->id)) {
            Toastr::error($reason, __('message.error'));
            return back();
        }

        $city->delete();
        Toastr::success('City successfully deleted.', __('message.success'));

        return back();
    }

    private function form(string $mode, ?City $city)
    {
        return Inertia::render('Admin/City/Form', [
            'mode'   => $mode,
            'entity' => $city ? [
                'id'         => $city->id,
                'country_id' => $city->country_id,
                'name'       => (string) $city->name,
                'en_name'    => (string) $city->en_name,
                'city_code'  => (string) $city->city_code,
                'sorting'    => $city->sorting,
                'is_active'  => (bool) $city->is_active,
            ] : null,
            'lookups' => ['countries' => $this->countryOptions()],
            'urls' => [
                'submit' => $city ? route('city.update', $city->id) : route('city.store'),
                'cancel' => route('city.index'),
            ],
            't' => $this->labels([
                'title'      => $city ? 'Edit city' : 'Add city',
                'list_title' => __('menus.city') ?: 'Cities',
                'code'       => __('city.code') ?: 'Code',
                'en_name'    => __('country.en_name') ?: 'English name',
                'country'    => __('country.title_single') ?: 'Country',
                'sorting_hint' => 'Lower numbers appear first.',
            ]),
        ]);
    }

    private function countryOptions(): array
    {
        return Country::query()
            ->orderBy('sorting')->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($c) => ['id' => $c->id, 'name' => (string) $c->name])
            ->all();
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'name'       => ['required', 'string', 'max:255'],
            'en_name'    => ['nullable', 'string', 'max:255'],
            'city_code'  => ['nullable', 'string', 'max:50'],
            'sorting'    => ['nullable', 'integer', 'min:0'],
            'is_active'  => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $data['sorting']   = $data['sorting'] ?? 0;

        return $data;
    }
}
