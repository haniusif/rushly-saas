<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Support\StoreRequest;
use App\Repositories\Support\SupportInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class SupportController extends Controller
{
    protected $repo;

    public function __construct(SupportInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $paginator = $this->repo->all();

        $rows = collect($paginator->items())->map(function ($s) {
            $statusEnum = (int) $s->status;
            return [
                'id'           => $s->id,
                'user_name'    => optional($s->user)->name,
                'user_email'   => optional($s->user)->email,
                'service'      => $s->service,
                'service_label'=> trans('SalaryService.' . $s->service) ?: $s->service,
                'department'   => optional($s->department)->title,
                'subject'      => $s->subject,
                'priority'     => $s->priority,
                'date'         => $s->date ? dateFormat($s->date) : null,
                'status'       => $statusEnum,
                'status_label' => $this->statusLabel($statusEnum),
                'next_actions' => $this->nextStatusOptions($s),
                'urls' => [
                    'view'   => route('support.view', $s->id),
                    'edit'   => route('support.edit', $s->id),
                    'delete' => route('support.delete', $s->id),
                ],
            ];
        })->values();

        return Inertia::render('Admin/Support/Index', [
            'rows'        => $rows,
            'pagination'  => $this->paginateMeta($paginator),
            'permissions' => [
                'create'        => hasPermission('support_create'),
                'view'          => hasPermission('support_read'),
                'update'        => hasPermission('support_update'),
                'delete'        => hasPermission('support_delete'),
                'status_update' => hasPermission('support_status_update'),
            ],
            'urls' => [
                'index'  => route('support.index'),
                'create' => route('support.add'),
            ],
            't' => $this->labels([
                'title'          => __('support.supprot') ?: 'Support',
                'list'           => __('levels.list') ?: 'List',
                'no_rows'        => 'No tickets yet.',
                'delete_confirm' => 'Delete this ticket?',
            ]),
        ]);
    }

    public function create()
    {
        $departments = $this->repo->departments();

        return Inertia::render('Admin/Support/Form', [
            'mode'    => 'create',
            'entity'  => null,
            'lookups' => [
                'services'    => $this->serviceOptions(),
                'priorities'  => $this->priorityOptions(),
                'departments' => collect($departments)->map(fn ($d) => [
                    'value' => (string) $d->id,
                    'label' => $d->title,
                ])->values(),
            ],
            'urls' => [
                'submit' => route('support.store'),
                'cancel' => route('support.index'),
            ],
            't' => $this->labels([
                'title'      => __('support.supprot_add') ?: 'Add support ticket',
                'list_title' => __('support.supprot') ?: 'Support',
                'placeholder_subject' => __('placeholder.Enter_Subject') ?: 'Enter subject',
                'placeholder_desc'    => __('placeholder.Enter_description') ?: 'Describe the issue',
            ]),
        ]);
    }

    public function store(StoreRequest $request)
    {
        if ($this->repo->store($request)) {
            Toastr::success(__('support.added_msg'), __('message.success'));
            return redirect()->route('support.index');
        }
        Toastr::error(__('support.error_msg'), __('message.error'));
        return redirect()->back()->withInput($request->all());
    }

    public function show($id) {}

    public function edit($id)
    {
        $departments   = $this->repo->departments();
        $singleSupport = $this->repo->get($id);
        return view('backend.support.edit', compact('departments', 'singleSupport'));
    }

    public function update(StoreRequest $request)
    {
        if ($this->repo->update($request->id, $request)) {
            Toastr::success(__('support.update_msg'), __('message.success'));
            return redirect()->route('support.index');
        }
        Toastr::error(__('support.error_msg'), __('message.error'));
        return redirect()->back()->withInput($request->all());
    }

    public function view($id)
    {
        $singleSupport = $this->repo->get($id);
        $chats         = $this->repo->chats($id);
        return view('backend.support.view', compact('singleSupport', 'chats'));
    }

    public function supportReply(Request $request)
    {
        $validator = Validator::make($request->all(), ['message' => 'required']);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        if ($this->repo->reply($request)) {
            Toastr::success(__('support.reply_msg'), __('message.success'));
            return redirect()->route('support.view', $request->support_id);
        }
        Toastr::error(__('support.error_msg'), __('message.error'));
        return redirect()->back()->withInput($request->all());
    }

    public function destroy($id)
    {
        if ($this->repo->delete($id)) {
            Toastr::success(__('support.delete_msg'), __('message.success'));
            return redirect()->route('support.index');
        }
        Toastr::error(__('support.error_msg'), __('message.error'));
        return redirect()->back();
    }

    public function statusUpdate(Request $request, $id)
    {
        if ($this->repo->statusUpdate($id, $request)) {
            Toastr::success('Status updated successfully.', __('message.success'));
            return redirect()->route('support.index');
        }
        Toastr::error(__('support.error_msg'), __('message.error'));
        return redirect()->back();
    }

    private function statusLabel(int $s): string
    {
        return match ($s) {
            \App\Enums\SupportStatus::PENDING    => __('levels.pending') ?: 'Pending',
            \App\Enums\SupportStatus::PROCESSING => __('levels.processing') ?: 'Processing',
            \App\Enums\SupportStatus::RESOLVED   => __('levels.resolved') ?: 'Resolved',
            \App\Enums\SupportStatus::CLOSED     => __('levels.closed') ?: 'Closed',
            default => (string) $s,
        };
    }

    private function nextStatusOptions($s): array
    {
        $opts = [];
        $st   = (int) $s->status;
        if ($st === \App\Enums\SupportStatus::PENDING) {
            $opts[] = ['label' => __('levels.processing') ?: 'Processing',
                       'url'   => route('support.status.update', [$s->id, 'status' => \App\Enums\SupportStatus::PROCESSING])];
        } elseif ($st === \App\Enums\SupportStatus::PROCESSING) {
            $opts[] = ['label' => __('levels.resolved') ?: 'Resolved',
                       'url'   => route('support.status.update', [$s->id, 'status' => \App\Enums\SupportStatus::RESOLVED])];
            $opts[] = ['label' => __('levels.closed') ?: 'Closed',
                       'url'   => route('support.status.update', [$s->id, 'status' => \App\Enums\SupportStatus::CLOSED])];
        }
        return $opts;
    }

    private function serviceOptions(): array
    {
        $opts = [];
        foreach ((array) trans('SalaryService') as $k => $v) {
            $opts[] = ['value' => (string) $k, 'label' => (string) $v];
        }
        return $opts;
    }

    private function priorityOptions(): array
    {
        return [
            ['value' => 'low',    'label' => __('placeholder.low') ?: 'Low'],
            ['value' => 'medium', 'label' => __('placeholder.Medium') ?: 'Medium'],
            ['value' => 'high',   'label' => __('placeholder.High') ?: 'High'],
        ];
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
            'view'        => __('levels.view') ?: 'View',
            'actions'     => __('support.action') ?: 'Actions',
            'user_info'   => __('support.user_info') ?: 'User',
            'name'        => __('support.name') ?: 'Name',
            'email'       => __('support.email') ?: 'Email',
            'service'     => __('support.service') ?: 'Service',
            'department'  => __('support.department') ?: 'Department',
            'subject'     => __('support.subject') ?: 'Subject',
            'priority'    => __('support.priority') ?: 'Priority',
            'date'        => __('support.date') ?: 'Date',
            'status'      => __('levels.status') ?: 'Status',
            'status_update' => __('to_do.status_update') ?: 'Status update',
            'description' => __('support.description') ?: 'Description',
            'attached'    => __('support.attached') ?: 'Attachment',
            'save'        => __('levels.save') ?: 'Save',
            'cancel'      => __('levels.cancel') ?: 'Cancel',
            'back'        => __('levels.back') ?: 'Back',
            'prev'        => 'Prev',
            'next'        => 'Next',
            'showing_results' => 'Showing :from – :to of :total',
        ], $extra);
    }
}
