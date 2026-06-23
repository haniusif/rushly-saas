<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fraud\StoreRequest;
use App\Http\Requests\Fraud\UpdateRequest;
use App\Repositories\Fraud\FraudInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Str;
use Inertia\Inertia;

class FraudController extends Controller
{
    protected $repo;
    public function __construct(FraudInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $paginator = $this->repo->all();

        $rows = collect($paginator->items())->map(fn ($f) => [
            'id'          => $f->id,
            'phone'       => (string) $f->phone,
            'name'        => (string) $f->name,
            'tracking_id' => (string) $f->tracking_id,
            'details'     => Str::limit(strip_tags((string) $f->details), 140),
            'urls' => [
                'edit'   => route('fraud.edit', $f->id),
                'delete' => route('fraud.delete', $f->id),
            ],
        ])->values();

        return Inertia::render('Admin/Fraud/Index', [
            'rows'        => $rows,
            'pagination'  => $this->paginateMeta($paginator),
            'permissions' => [
                'create' => hasPermission('fraud_create'),
                'update' => hasPermission('fraud_update'),
                'delete' => hasPermission('fraud_delete'),
            ],
            'urls' => [
                'index'  => route('fraud.index'),
                'create' => route('fraud.create'),
            ],
            't' => $this->labels([
                'title'          => __('fraud.title') ?: 'Fraud',
                'list'           => __('levels.list') ?: 'List',
                'no_rows'        => 'No reports yet.',
                'delete_confirm' => 'Delete this entry?',
            ]),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Fraud/Form', [
            'mode'   => 'create',
            'entity' => null,
            'urls'   => [
                'submit' => route('fraud.store'),
                'cancel' => route('fraud.index'),
            ],
            't' => $this->labels([
                'title'      => __('fraud.create_fraud') ?: 'Create Fraud Report',
                'list_title' => __('fraud.title') ?: 'Fraud',
                'placeholder_phone' => __('placeholder.Enter_phone') ?: 'Enter phone',
                'placeholder_name'  => __('placeholder.Enter_name') ?: 'Enter name',
                'placeholder_tracking' => __('placeholder.Enter_tracking_id') ?: 'Enter tracking ID',
                'placeholder_details' => __('placeholder.Enter_description') ?: 'Describe the incident',
            ]),
        ]);
    }

    public function store(StoreRequest $request)
    {
        if ($this->repo->store($request)) {
            Toastr::success(__('fraud.added_msg'), __('message.success'));
            return redirect()->route('fraud.index');
        }
        Toastr::error(__('fraud.error_msg'), __('message.error'));
        return redirect()->back();
    }

    public function edit($id)
    {
        $entity = $this->repo->get($id);
        if (!$entity) abort(404);
        return Inertia::render('Admin/Fraud/Form', [
            'mode'   => 'edit',
            'entity' => [
                'id'          => $entity->id,
                'phone'       => $entity->phone,
                'name'        => $entity->name,
                'tracking_id' => $entity->tracking_id,
                'details'     => strip_tags((string) $entity->details),
            ],
            'urls' => [
                'submit' => route('fraud.update', $entity->id),
                'cancel' => route('fraud.index'),
            ],
            't' => $this->labels([
                'title'      => 'Edit Fraud Report',
                'list_title' => __('fraud.title') ?: 'Fraud',
                'placeholder_phone' => __('placeholder.Enter_phone') ?: 'Enter phone',
                'placeholder_name'  => __('placeholder.Enter_name') ?: 'Enter name',
                'placeholder_tracking' => __('placeholder.Enter_tracking_id') ?: 'Enter tracking ID',
                'placeholder_details' => __('placeholder.Enter_description') ?: 'Describe the incident',
            ]),
        ]);
    }

    public function update(UpdateRequest $request)
    {
        if ($this->repo->update($request->id, $request)) {
            Toastr::success(__('fraud.update_msg'), __('message.success'));
            return redirect()->route('fraud.index');
        }
        Toastr::error(__('fraud.error_msg'), __('message.error'));
        return redirect()->back();
    }

    public function destroy($id)
    {
        $this->repo->delete($id);
        Toastr::success(__('fraud.delete_msg'), __('message.success'));
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
            'add'         => __('levels.add') ?: 'Add',
            'edit'        => __('levels.edit') ?: 'Edit',
            'delete'      => __('levels.delete') ?: 'Delete',
            'actions'     => __('levels.actions') ?: 'Actions',
            'phone'       => __('levels.phone') ?: 'Phone',
            'name'        => __('levels.name') ?: 'Name',
            'tracking_id' => __('levels.track_id') ?: 'Tracking ID',
            'details'     => __('levels.details') ?: 'Details',
            'save'        => __('levels.save') ?: 'Save',
            'update'      => __('levels.update') ?: 'Update',
            'cancel'      => __('levels.cancel') ?: 'Cancel',
            'back'        => __('levels.back') ?: 'Back',
            'prev'        => 'Prev',
            'next'        => 'Next',
            'showing_results' => 'Showing :from – :to of :total',
        ], $extra);
    }
}
