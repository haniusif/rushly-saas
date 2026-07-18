<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssetCategory\StoreRequest;
use App\Repositories\AssetCategory\AssetCategoryInterface;
use Brian2694\Toastr\Facades\Toastr;
use Inertia\Inertia;

class AssetcategoryController extends Controller
{
    protected $repo;

    public function __construct(AssetcategoryInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $paginator = $this->repo->all();

        $rows = collect($paginator->items())->map(fn ($c) => [
            'id'       => $c->id,
            'title'    => (string) $c->title,
            'position' => $c->position,
            'urls'     => [
                'edit'   => route('asset-category.edit', $c->id),
                'delete' => route('asset-category.delete', $c->id),
            ],
        ])->values();

        return Inertia::render('Admin/AssetCategory/Index', [
            'rows'        => $rows,
            'pagination'  => $this->paginateMeta($paginator),
            'permissions' => [
                'create' => hasPermission('asset_category_create'),
                'update' => hasPermission('asset_category_update'),
                'delete' => hasPermission('asset_category_delete'),
            ],
            'urls' => [
                'index'  => route('asset-category.index'),
                'create' => route('asset-category.create'),
            ],
            't' => $this->labels([
                'title'          => __('asset_category.title_name') ?: 'Asset categories',
                'list'           => __('levels.list') ?: 'List',
                'no_rows'        => 'No asset categories yet.',
                'delete_confirm' => 'Delete this asset category?',
            ]),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/AssetCategory/Form', [
            'mode'   => 'create',
            'entity' => null,
            'urls' => [
                'submit' => route('asset-category.store'),
                'cancel' => route('asset-category.index'),
            ],
            't' => $this->labels([
                'title'             => __('asset_category.assetc_add') ?: 'Create asset category',
                'list_title'        => __('asset_category.title_name') ?: 'Asset categories',
                'placeholder_title' => __('placeholder.Enter_title') ?: 'Enter title',
                'placeholder_pos'   => __('placeholder.Enter_Position') ?: 'Enter position',
            ]),
        ]);
    }

    public function store(StoreRequest $request)
    {
        if ($this->repo->store($request)) {
            Toastr::success('Assetcategory successfully added.', __('message.success'));
            return redirect()->route('asset-category.index');
        }
        Toastr::error('Something went wrong.', __('message.error'));
        return redirect()->back();
    }

    public function edit($id)
    {
        $c = $this->repo->get($id);
        if (!$c) abort(404);

        return Inertia::render('Admin/AssetCategory/Form', [
            'mode'   => 'edit',
            'entity' => [
                'id'       => $c->id,
                'title'    => (string) $c->title,
                'position' => $c->position,
            ],
            'urls' => [
                'submit' => route('asset-category.update'),
                'cancel' => route('asset-category.index'),
            ],
            't' => $this->labels([
                'title'             => 'Edit asset category',
                'list_title'        => __('asset_category.title_name') ?: 'Asset categories',
                'placeholder_title' => __('placeholder.Enter_title') ?: 'Enter title',
                'placeholder_pos'   => __('placeholder.Enter_Position') ?: 'Enter position',
            ]),
        ]);
    }

    public function update(StoreRequest $request)
    {
        if ($this->repo->update($request)) {
            Toastr::success('Assetcategory successfully updated.', __('message.success'));
            return redirect()->route('asset-category.index');
        }
        Toastr::error('Something went wrong.', __('message.error'));
        return redirect()->back();
    }

    public function destroy($id)
    {
        $this->repo->delete($id);
        Toastr::success('Assetcategory successfully deleted.', __('message.success'));
        return back();
    }

    private function paginateMeta($p): array
    {
        return [
            'current_page' => $p->currentPage(),
            'last_page'    => $p->lastPage(),
            'from'         => $p->firstItem(),
            'to'           => $p->lastItem(),
            'total'        => $p->total(),
            'prev_url'     => $p->previousPageUrl(),
            'next_url'     => $p->nextPageUrl(),
        ];
    }

    private function labels(array $extra = []): array
    {
        return array_merge([
            'add'       => __('levels.add') ?: 'Add',
            'edit'      => __('levels.edit') ?: 'Edit',
            'delete'    => __('levels.delete') ?: 'Delete',
            'actions'   => __('levels.actions') ?: 'Actions',
            'name'      => __('levels.title') ?: 'Title',
            'position'  => __('levels.position') ?: 'Position',
            'save'      => __('levels.save') ?: 'Save',
            'update'    => __('levels.update') ?: 'Update',
            'cancel'    => __('levels.cancel') ?: 'Cancel',
            'back'      => __('levels.back') ?: 'Back',
            'prev'      => 'Prev',
            'next'      => 'Next',
            'showing_results' => 'Showing :from – :to of :total',
        ], $extra);
    }
}
