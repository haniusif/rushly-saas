<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeliveryCharge\StoreRequest;
use App\Http\Requests\DeliveryCharge\UpdateRequest;
use App\Repositories\DeliveryCharge\DeliveryChargeInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DeliveryChargeController extends Controller
{
    protected $repo;

    public function __construct(DeliveryChargeInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request)
    {
        return $this->renderIndex($this->repo->all(), $request);
    }

    public function filter(Request $request)
    {
        return $this->renderIndex($this->repo->filter($request), $request);
    }

    public function create()
    {
        return Inertia::render('Admin/DeliveryCharge/Form', [
            'mode'    => 'create',
            'entity'  => null,
            'lookups' => [
                'categories' => $this->categoryOptions(),
                'statuses'   => $this->statusOptions(),
            ],
            'currency' => settings()->currency,
            'urls' => [
                'submit' => route('delivery-charge.store'),
                'cancel' => route('delivery-charge.index'),
            ],
            't' => $this->labels([
                'title'      => __('delivery_charge.create_delivery_charge') ?: 'Create delivery charge',
                'list_title' => __('delivery_charge.title') ?: 'Delivery charges',
            ]),
        ]);
    }

    public function store(StoreRequest $request)
    {
        if ($this->repo->store($request)) {
            Toastr::success(__('delivery_charge.added_msg'), __('message.success'));
            return redirect()->route('delivery-charge.index');
        }
        Toastr::error(__('delivery_charge.error_msg'), __('message.error'));
        return redirect()->back();
    }

    public function edit($id)
    {
        $c = $this->repo->get($id);
        if (!$c) abort(404);

        return Inertia::render('Admin/DeliveryCharge/Form', [
            'mode'   => 'edit',
            'entity' => [
                'id'                 => $c->id,
                'category'           => (string) $c->category_id,
                'weight'             => $c->weight,
                'extra_weight_price' => $c->extra_weight_price,
                'same_day'           => $c->same_day,
                'next_day'           => $c->next_day,
                'sub_city'           => $c->sub_city,
                'outside_city'       => $c->outside_city,
                'position'           => $c->position,
                'status'             => (string) $c->status,
            ],
            'lookups' => [
                'categories' => $this->categoryOptions(),
                'statuses'   => $this->statusOptions(),
            ],
            'currency' => settings()->currency,
            'urls' => [
                'submit' => route('delivery-charge.update'),
                'cancel' => route('delivery-charge.index'),
            ],
            't' => $this->labels([
                'title'      => 'Edit delivery charge',
                'list_title' => __('delivery_charge.title') ?: 'Delivery charges',
            ]),
        ]);
    }

    public function update(UpdateRequest $request)
    {
        if ($this->repo->update($request)) {
            Toastr::success(__('delivery_charge.update_msg'), __('message.success'));
            return redirect()->route('delivery-charge.index');
        }
        Toastr::error(__('delivery_charge.error_msg'), __('message.error'));
        return redirect()->back();
    }

    public function destroy($id)
    {
        $this->repo->delete($id);
        Toastr::success(__('delivery_charge.delete_msg'), __('message.success'));
        return back();
    }

    private function renderIndex($paginator, Request $request)
    {
        $rows = collect($paginator->items())->map(fn ($c) => [
            'id'                 => $c->id,
            'category'           => optional($c->category)->title,
            'weight'             => $c->weight ?? 0,
            'extra_weight_price' => $c->extra_weight_price ?? 0,
            'position'           => $c->position,
            'status'             => (int) $c->status,
            'status_label'       => trans('status.' . $c->status) ?: ($c->status == 1 ? 'Active' : 'Inactive'),
            'same_day'           => (float) $c->same_day,
            'next_day'           => (float) $c->next_day,
            'sub_city'           => (float) $c->sub_city,
            'outside_city'       => (float) $c->outside_city,
            'urls' => [
                'edit'   => route('delivery-charge.edit', $c->id),
                'delete' => route('delivery-charge.delete', $c->id),
            ],
        ])->values();

        return Inertia::render('Admin/DeliveryCharge/Index', [
            'rows'        => $rows,
            'pagination'  => $this->paginateMeta($paginator),
            'filters'     => [
                'category' => (string) ($request->category ?? ''),
                'weight'   => (string) ($request->weight ?? ''),
            ],
            'lookups'     => ['categories' => $this->categoryOptions()],
            'currency'    => settings()->currency,
            'permissions' => [
                'create' => hasPermission('delivery_charge_create'),
                'update' => hasPermission('delivery_charge_update'),
                'delete' => hasPermission('delivery_charge_delete'),
            ],
            'urls' => [
                'index'  => route('delivery-charge.index'),
                'filter' => route('delivery-charge.filter'),
                'create' => route('delivery-charge.create'),
            ],
            't' => $this->labels([
                'title'          => __('delivery_charge.title') ?: 'Delivery charges',
                'list'           => __('levels.list') ?: 'List',
                'no_rows'        => 'No delivery charges yet.',
                'delete_confirm' => 'Delete this delivery charge?',
            ]),
        ]);
    }

    private function categoryOptions(): array
    {
        $rows = $this->repo->categories();
        return collect($rows)->map(fn ($c) => ['value' => (string) $c->id, 'label' => $c->title])->values()->all();
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
            'add'           => __('levels.add') ?: 'Add',
            'edit'          => __('levels.edit') ?: 'Edit',
            'delete'        => __('levels.delete') ?: 'Delete',
            'actions'       => __('levels.actions') ?: 'Actions',
            'category'      => __('levels.category') ?: 'Category',
            'weight'        => __('levels.weight') ?: 'Weight',
            'extra_weight'  => __('levels.extra_weight') ?: 'Extra weight',
            'extra_weight_price' => __('levels.extra_weight_price') ?: 'Extra weight price',
            'position'      => __('levels.position') ?: 'Position',
            'status'        => __('levels.status') ?: 'Status',
            'same_day'      => __('levels.same_day') ?: 'Same day',
            'next_day'      => __('levels.next_day') ?: 'Next day',
            'sub_city'      => __('levels.sub_city') ?: 'Sub-city',
            'outside_city'  => __('levels.outside_city') ?: 'Outside city',
            'select'        => __('menus.select') ?: 'Select',
            'filter'        => __('levels.filter') ?: 'Filter',
            'clear'         => __('levels.clear') ?: 'Clear',
            'save'          => __('levels.save') ?: 'Save',
            'update'        => __('levels.update') ?: 'Update',
            'cancel'        => __('levels.cancel') ?: 'Cancel',
            'back'          => __('levels.back') ?: 'Back',
            'prev'          => 'Prev',
            'next'          => 'Next',
            'showing_results' => 'Showing :from – :to of :total',
            'placeholder_weight' => __('placeholder.Enter_weight') ?: 'Enter weight',
            'placeholder_same_day' => __('placeholder.enter_same_day') ?: 'Enter same-day price',
            'placeholder_next_day' => __('placeholder.enter_next_day') ?: 'Enter next-day price',
            'placeholder_sub_city' => __('placeholder.enter_sub_city') ?: 'Enter sub-city price',
            'placeholder_outside_city' => __('placeholder.enter_outside_city') ?: 'Enter outside-city price',
            'placeholder_position' => __('placeholder.Enter_Position') ?: 'Enter position',
        ], $extra);
    }
}
