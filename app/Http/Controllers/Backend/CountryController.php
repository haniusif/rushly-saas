<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\ManagesReferenceData;
use App\Http\Controllers\Controller;
use App\Models\Backend\Country;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Countries admin. The routes (country.index / create / store / edit / update /
 * delete) existed in routes/web.php with no controller behind them, so every
 * one of them threw and `php artisan route:list` could not even build.
 *
 * `countries` has no company_id — see ManagesReferenceData.
 */
class CountryController extends Controller
{
    use ManagesReferenceData;

    /** Tables that point at a country; delete is refused while any row does. */
    private const REFERENCES = [
        'cities'             => 'country_id',
        'merchant_countries' => 'country_id',
    ];

    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status', '');

        $paginator = Country::query()
            ->when($search !== '', fn ($q) => $q->where(function ($w) use ($search) {
                $w->where('name', 'like', "%{$search}%")
                  ->orWhere('en_name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            }))
            ->when($status !== '', fn ($q) => $q->where('is_active', (int) $status))
            ->withCount('cities')
            ->orderBy('sorting')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $rows = collect($paginator->items())->map(fn ($c) => [
            'id'          => $c->id,
            'name'        => (string) $c->name,
            'en_name'     => (string) $c->en_name,
            'code'        => (string) $c->code,
            'sorting'     => $c->sorting,
            'is_active'   => (bool) $c->is_active,
            'cities_count'=> (int) ($c->cities_count ?? 0),
            'urls'        => [
                'edit'   => route('country.edit', $c->id),
                'delete' => route('country.delete', $c->id),
            ],
        ])->values();

        return Inertia::render('Admin/Country/Index', [
            'rows'        => $rows,
            'pagination'  => $this->paginateMeta($paginator),
            'filters'     => ['search' => $search, 'status' => (string) $status],
            'permissions' => [
                'create' => hasPermission('country_create'),
                'update' => hasPermission('country_update'),
                'delete' => hasPermission('country_delete'),
            ],
            'urls' => [
                'index'  => route('country.index'),
                'create' => route('country.create'),
            ],
            't' => $this->labels([
                'title'          => __('country.title') ?: 'Countries',
                'code'           => __('country.code') ?: 'Code',
                'en_name'        => __('country.en_name') ?: 'English name',
                'cities'         => __('menus.city') ?: 'Cities',
                'no_rows'        => 'No countries found.',
                'delete_confirm' => 'Delete this country?',
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
        $country = Country::find($id);
        if (! $country) {
            abort(404);
        }
        return $this->form('edit', $country);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        Country::create($data);
        Toastr::success('Country successfully added.', __('message.success'));

        return redirect()->route('country.index');
    }

    public function update(Request $request, $id)
    {
        $country = Country::find($id);
        if (! $country) {
            abort(404);
        }

        $country->update($this->validated($request, $country->id));
        Toastr::success('Country successfully updated.', __('message.success'));

        return redirect()->route('country.index');
    }

    public function destroy($id)
    {
        $country = Country::find($id);
        if (! $country) {
            abort(404);
        }

        if ($reason = $this->blockingReferences(self::REFERENCES, $country->id)) {
            Toastr::error($reason, __('message.error'));
            return back();
        }

        $country->delete();
        Toastr::success('Country successfully deleted.', __('message.success'));

        return back();
    }

    private function form(string $mode, ?Country $country)
    {
        return Inertia::render('Admin/Country/Form', [
            'mode'   => $mode,
            'entity' => $country ? [
                'id'        => $country->id,
                'name'      => (string) $country->name,
                'en_name'   => (string) $country->en_name,
                'code'      => (string) $country->code,
                'sorting'   => $country->sorting,
                'is_active' => (bool) $country->is_active,
            ] : null,
            'urls' => [
                'submit' => $country ? route('country.update', $country->id) : route('country.store'),
                'cancel' => route('country.index'),
            ],
            't' => $this->labels([
                'title'      => $country
                    ? (__('country.edit') ?: 'Edit country')
                    : (__('country.add') ?: 'Add country'),
                'list_title' => __('country.title') ?: 'Countries',
                'code'       => __('country.code') ?: 'Code',
                'en_name'    => __('country.en_name') ?: 'English name',
                'code_hint'  => 'ISO code, e.g. SA / AE / EG.',
                'sorting_hint' => 'Lower numbers appear first.',
            ]),
        ]);
    }

    /**
     * `code` is unique because it is what integrations match a country on;
     * two rows sharing one code makes that lookup ambiguous.
     */
    private function validated(Request $request, $ignoreId = null): array
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'en_name'   => ['nullable', 'string', 'max:255'],
            'code'      => [
                'required', 'string', 'max:10',
                Rule::unique('countries', 'code')->ignore($ignoreId),
            ],
            'sorting'   => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $data['sorting']   = $data['sorting'] ?? 0;

        return $data;
    }
}
