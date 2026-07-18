<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Packaging\StoreRequest;
use App\Http\Requests\Packaging\UpdateRequest;
use App\Repositories\Packaging\PackagingInterface;
use Brian2694\Toastr\Facades\Toastr;
use Inertia\Inertia;

class PackagingController extends Controller
{
    protected $repo;

    public function __construct(PackagingInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $paginator = $this->repo->all();

        $rows = collect($paginator->items())->map(fn ($p) => [
            'id'       => $p->id,
            'name'     => (string) $p->name,
            'position' => $p->position,
            'status'   => (int) $p->status,
            'status_label' => trans('status.' . $p->status) ?: ((int) $p->status === 1 ? 'Active' : 'Inactive'),
            'price'    => (float) $p->price,
            'image'    => (string) $p->image,
            'urls'     => [
                'edit'   => route('packaging.edit', $p->id),
                'delete' => route('packaging.delete', $p->id),
            ],
        ])->values();

        return Inertia::render('Admin/Packaging/Index', [
            'rows'        => $rows,
            'pagination'  => $this->paginateMeta($paginator),
            'currency'    => settings()->currency,
            'permissions' => [
                'create' => hasPermission('packaging_create'),
                'update' => hasPermission('packaging_update'),
                'delete' => hasPermission('packaging_delete'),
            ],
            'urls' => [
                'index'  => route('packaging.index'),
                'create' => route('packaging.create'),
            ],
            't' => $this->labels([
                'title'          => __('packaging.title') ?: 'Packaging',
                'list'           => __('levels.list') ?: 'List',
                'no_rows'        => 'No packaging types yet.',
                'delete_confirm' => __('delete.packaging') ?: 'Delete this packaging?',
                'price'          => __('levels.price') ?: 'Price',
                'image'          => __('levels.image') ?: 'Image',
            ]),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Packaging/Form', [
            'mode'    => 'create',
            'entity'  => null,
            'lookups' => ['statuses' => $this->statusOptions()],
            'urls' => [
                'submit' => route('packaging.store'),
                'cancel' => route('packaging.index'),
            ],
            't' => $this->labels([
                'title'             => __('packaging.create_packaging') ?: 'Create packaging',
                'list_title'        => __('packaging.title') ?: 'Packaging',
                'placeholder_name'  => __('placeholder.Enter_name')     ?: 'Enter name',
                'placeholder_price' => __('placeholder.Enter_price')    ?: 'Enter price',
                'placeholder_pos'   => __('placeholder.Enter_Position') ?: 'Enter position',
                'image'             => __('levels.image') ?: 'Image',
                'image_hint'        => 'PNG or JPG, up to 2 MB.',
                'price'             => __('levels.price') ?: 'Price',
            ]),
        ]);
    }

    public function store(StoreRequest $request)
    {
        if ($this->repo->store($request)) {
            Toastr::success('Packaging successfully added.', __('message.success'));
            return redirect()->route('packaging.index');
        }
        Toastr::error('Something went wrong.', __('message.error'));
        return redirect()->back();
    }

    public function edit($id)
    {
        $p = $this->repo->get($id);
        if (!$p) abort(404);

        return Inertia::render('Admin/Packaging/Form', [
            'mode'   => 'edit',
            'entity' => [
                'id'       => $p->id,
                'name'     => (string) $p->name,
                'price'    => (float) $p->price,
                'status'   => (string) $p->status,
                'position' => $p->position,
                'image'    => (string) $p->image,
            ],
            'lookups' => ['statuses' => $this->statusOptions()],
            'urls' => [
                'submit' => route('packaging.update'),
                'cancel' => route('packaging.index'),
            ],
            't' => $this->labels([
                'title'             => 'Edit packaging',
                'list_title'        => __('packaging.title') ?: 'Packaging',
                'placeholder_name'  => __('placeholder.Enter_name')     ?: 'Enter name',
                'placeholder_price' => __('placeholder.Enter_price')    ?: 'Enter price',
                'placeholder_pos'   => __('placeholder.Enter_Position') ?: 'Enter position',
                'image'             => __('levels.image') ?: 'Image',
                'image_hint'        => 'PNG or JPG, up to 2 MB. Leave blank to keep the existing image.',
                'price'             => __('levels.price') ?: 'Price',
            ]),
        ]);
    }

    public function update(UpdateRequest $request)
    {
        if ($this->repo->update($request)) {
            Toastr::success('Packaging successfully updated.', __('message.success'));
            return redirect()->route('packaging.index');
        }
        Toastr::error('Something went wrong.', __('message.error'));
        return redirect()->back();
    }

    public function destroy($id)
    {
        $this->repo->delete($id);
        Toastr::success('Packaging successfully deleted.', __('message.success'));
        return back();
    }

    private function statusOptions(): array
    {
        $opts = [];
        foreach ((array) trans('status') as $k => $v) {
            $opts[] = ['value' => (string) $k, 'label' => (string) $v];
        }
        return $opts;
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
            'name'      => __('levels.name') ?: 'Name',
            'status'    => __('levels.status') ?: 'Status',
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
