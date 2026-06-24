<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Deliverycategory\StoreRequest;
use App\Http\Requests\Deliverycategory\UpdateRequest;
use App\Repositories\DeliveryCategory\DeliveryCategoryInterface;
use Brian2694\Toastr\Facades\Toastr;
use Inertia\Inertia;

class DeliverycategoryController extends Controller
{
    protected $repo;
    public $notDeleteArray = [1];

    public function __construct(DeliveryCategoryInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $paginator = $this->repo->all();

        $rows = collect($paginator->items())->map(fn ($c) => [
            'id'           => $c->id,
            'title'        => (string) $c->title,
            'status'       => (int) $c->status,
            'status_label' => trans('status.' . $c->status) ?: ($c->status == 1 ? 'Active' : 'Inactive'),
            'position'     => $c->position,
            'is_locked'    => in_array($c->id, $this->notDeleteArray, true),
            'urls' => [
                'edit'   => route('delivery-category.edit', $c->id),
                'delete' => route('delivery-category.delete', $c->id),
            ],
        ])->values();

        return Inertia::render('Admin/DeliveryCategory/Index', [
            'rows'        => $rows,
            'pagination'  => $this->paginateMeta($paginator),
            'permissions' => [
                'create' => hasPermission('delivery_category_create'),
                'update' => hasPermission('delivery_category_update'),
                'delete' => hasPermission('delivery_category_delete'),
            ],
            'urls' => [
                'index'  => route('delivery-category.index'),
                'create' => route('delivery-category.create'),
            ],
            't' => $this->labels([
                'title'          => __('deliverycategory.title') ?: 'Delivery categories',
                'list'           => __('levels.list') ?: 'List',
                'no_rows'        => 'No delivery categories yet.',
                'delete_confirm' => 'Delete this category?',
            ]),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/DeliveryCategory/Form', [
            'mode'    => 'create',
            'entity'  => null,
            'lookups' => ['statuses' => $this->statusOptions()],
            'urls' => [
                'submit' => route('delivery-category.store'),
                'cancel' => route('delivery-category.index'),
            ],
            't' => $this->labels([
                'title'             => __('deliverycategory.create_deliverycategory') ?: 'Create delivery category',
                'list_title'        => __('deliverycategory.title') ?: 'Delivery categories',
                'placeholder_title' => __('placeholder.Enter_title') ?: 'Enter title',
                'placeholder_pos'   => __('placeholder.Enter_Position') ?: 'Enter position',
            ]),
        ]);
    }

    public function store(StoreRequest $request)
    {
        if ($this->repo->store($request)) {
            Toastr::success('Deliverycategory successfully added.', __('message.success'));
            return redirect()->route('delivery-category.index');
        }
        Toastr::error('Something went wrong.', __('message.error'));
        return redirect()->back();
    }

    public function edit($id)
    {
        $c = $this->repo->get($id);
        if (!$c) abort(404);

        return Inertia::render('Admin/DeliveryCategory/Form', [
            'mode'   => 'edit',
            'entity' => [
                'id'       => $c->id,
                'title'    => $c->title,
                'status'   => (string) $c->status,
                'position' => $c->position,
            ],
            'lookups' => ['statuses' => $this->statusOptions()],
            'urls' => [
                'submit' => route('delivery-category.update'),
                'cancel' => route('delivery-category.index'),
            ],
            't' => $this->labels([
                'title'             => 'Edit delivery category',
                'list_title'        => __('deliverycategory.title') ?: 'Delivery categories',
                'placeholder_title' => __('placeholder.Enter_title') ?: 'Enter title',
                'placeholder_pos'   => __('placeholder.Enter_Position') ?: 'Enter position',
            ]),
        ]);
    }

    public function update(UpdateRequest $request)
    {
        if ($this->repo->update($request)) {
            Toastr::success('Deliverycategory successfully updated.', __('message.success'));
            return redirect()->route('delivery-category.index');
        }
        Toastr::error('Something went wrong.', __('message.error'));
        return redirect()->back();
    }

    public function destroy($id)
    {
        $this->repo->delete($id);
        Toastr::success('Deliverycategory successfully deleted.', __('message.success'));
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
            'name'      => __('levels.title') ?: 'Title',
            'status'    => __('levels.status') ?: 'Status',
            'position'  => __('levels.position') ?: 'Position',
            'save'      => __('levels.save') ?: 'Save',
            'update'    => __('levels.update') ?: 'Update',
            'cancel'    => __('levels.cancel') ?: 'Cancel',
            'back'      => __('levels.back') ?: 'Back',
            'locked_hint' => 'Default — cannot delete',
            'prev'      => 'Prev',
            'next'      => 'Next',
            'showing_results' => 'Showing :from – :to of :total',
        ], $extra);
    }
}
