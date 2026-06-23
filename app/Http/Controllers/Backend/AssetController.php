<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Asset\StoreRequest;
use App\Repositories\Asset\AssetInterface;
use Brian2694\Toastr\Facades\Toastr;
use Inertia\Inertia;

class AssetController extends Controller
{
    protected $repo;

    public function __construct(AssetInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $paginator = $this->repo->all();

        $rows = collect($paginator->items())->map(fn ($a) => [
            'id'             => $a->id,
            'name'           => (string) $a->name,
            'category'       => optional($a->assetcategorys)->title,
            'hub'            => optional($a->hubs)->name,
            'supplier_name'  => (string) ($a->supplyer_name ?? ''),
            'quantity'       => $a->quantity,
            'warranty'       => $a->warranty,
            'invoice_no'     => $a->invoice_no,
            'amount'         => (float) ($a->amount ?? 0),
            'urls' => [
                'edit'   => route('asset.edit', $a->id),
                'delete' => route('asset.delete', $a->id),
                'view'   => route('asset.view', $a->id),
            ],
        ])->values();

        return Inertia::render('Admin/Asset/Index', [
            'rows'        => $rows,
            'pagination'  => $this->paginateMeta($paginator),
            'permissions' => [
                'create' => hasPermission('assets_create'),
                'update' => hasPermission('assets_update'),
                'delete' => hasPermission('assets_delete'),
                'view'   => hasPermission('assets_read'),
            ],
            'currency' => settings()->currency,
            'urls' => [
                'index'  => route('asset.index'),
                'create' => route('asset.create'),
            ],
            't' => $this->labels([
                'title'          => __('asset.title') ?: 'Assets',
                'list'           => __('asset.asset_list') ?: 'Asset list',
                'no_rows'        => 'No assets recorded.',
                'delete_confirm' => 'Delete this asset?',
            ]),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Asset/Form', [
            'mode'    => 'create',
            'entity'  => null,
            'lookups' => [
                'categories' => $this->categoryOptions(),
                'hubs'       => $this->hubOptions(),
            ],
            'urls' => [
                'submit' => route('asset.store'),
                'cancel' => route('asset.index'),
            ],
            't' => $this->labels([
                'title'      => __('asset.asset_add') ?: 'Add asset',
                'list_title' => __('asset.title') ?: 'Assets',
            ]),
        ]);
    }

    public function store(StoreRequest $request)
    {
        if ($this->repo->store($request)) {
            Toastr::success('Asset successfully added.', __('message.success'));
            return redirect()->route('asset.index');
        }
        Toastr::error('Something went wrong.', __('message.error'));
        return redirect()->back()->withInput();
    }

    public function edit($id)
    {
        $a = $this->repo->get($id);
        if (!$a) abort(404);

        return Inertia::render('Admin/Asset/Form', [
            'mode'   => 'edit',
            'entity' => [
                'id'              => $a->id,
                'name'            => $a->name,
                'assetcategory_id' => (string) $a->assetcategory_id,
                'hub_id'          => (string) $a->hub_id,
                'supplyer_name'   => $a->supplyer_name,
                'quantity'        => $a->quantity,
                'warranty'        => $a->warranty,
                'invoice_no'      => $a->invoice_no,
                'amount'          => $a->amount,
                'description'     => $a->description,
            ],
            'lookups' => [
                'categories' => $this->categoryOptions(),
                'hubs'       => $this->hubOptions(),
            ],
            'urls' => [
                'submit' => route('asset.update'),
                'cancel' => route('asset.index'),
            ],
            't' => $this->labels([
                'title'      => 'Edit asset',
                'list_title' => __('asset.title') ?: 'Assets',
            ]),
        ]);
    }

    public function update(StoreRequest $request)
    {
        if ($this->repo->update($request)) {
            Toastr::success('Asset successfully Update.', __('message.success'));
            return redirect()->route('asset.index');
        }
        Toastr::error('Something went wrong.', __('message.error'));
        return redirect()->back()->withInput();
    }

    public function destroy($id)
    {
        $this->repo->delete($id);
        Toastr::success('Asset successfully deleted.', __('message.success'));
        return back();
    }

    public function show($id) {}

    private function categoryOptions(): array
    {
        $rows = $this->repo->assetcategorys();
        return collect($rows)->map(fn ($c) => ['value' => (string) $c->id, 'label' => $c->title])->values()->all();
    }

    private function hubOptions(): array
    {
        $rows = $this->repo->hubs();
        return collect($rows)->map(fn ($h) => ['value' => (string) $h->id, 'label' => $h->name])->values()->all();
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
            'add'        => __('levels.add') ?: 'Add',
            'edit'       => __('levels.edit') ?: 'Edit',
            'delete'     => __('levels.delete') ?: 'Delete',
            'actions'    => __('asset.action') ?: 'Actions',
            'name'       => __('asset.name') ?: 'Name',
            'category'   => __('asset.assetcategory_id') ?: 'Category',
            'hub'        => __('asset.hub_id') ?: 'Hub',
            'supplier'   => __('asset.supplyer_name') ?: 'Supplier',
            'quantity'   => __('asset.quantity') ?: 'Quantity',
            'warranty'   => __('asset.warranty') ?: 'Warranty',
            'invoice_no' => __('asset.invoice_no') ?: 'Invoice no',
            'amount'     => __('asset.amount') ?: 'Amount',
            'description'=> __('levels.description') ?: 'Description',
            'save'       => __('levels.save') ?: 'Save',
            'update'     => __('levels.update') ?: 'Update',
            'cancel'     => __('levels.cancel') ?: 'Cancel',
            'back'       => __('levels.back') ?: 'Back',
            'prev'       => 'Prev',
            'next'       => 'Next',
            'showing_results' => 'Showing :from – :to of :total',
        ], $extra);
    }
}
