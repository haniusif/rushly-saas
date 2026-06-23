<?php

namespace App\Http\Controllers\Backend;

use App\Enums\TodoStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Repositories\Todo\TodoInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class TodoController extends Controller
{
    protected $repo;

    public function __construct(TodoInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $paginator = $this->repo->all();

        $rows = collect($paginator->items())->map(fn ($t) => [
            'id'          => $t->id,
            'title'       => (string) $t->title,
            'description' => Str::limit(strip_tags((string) $t->description), 140),
            'description_full' => (string) $t->description,
            'date'        => $t->date ? dateFormat($t->date) : null,
            'date_raw'    => (string) $t->date,
            'user_name'   => optional($t->user)->name,
            'user_id'     => $t->user_id,
            'note'        => (string) $t->note,
            'status'      => (int) $t->status,
            'status_label'=> $this->statusLabel((int) $t->status),
            'urls'        => [
                'delete' => route('todo.delete', $t->id),
            ],
        ])->values();

        $users = User::companywise()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Admin/Todo/Index', [
            'rows'        => $rows,
            'pagination'  => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
                'total'        => $paginator->total(),
                'prev_url'     => $paginator->previousPageUrl(),
                'next_url'     => $paginator->nextPageUrl(),
            ],
            'lookups' => [
                'users' => $users->map(fn ($u) => ['value' => (string) $u->id, 'label' => $u->name])->values(),
            ],
            'permissions' => [
                'create' => hasPermission('todo_create'),
                'update' => hasPermission('todo_update'),
                'delete' => hasPermission('todo_delete'),
            ],
            'urls' => [
                'store'      => route('todo.store'),
                'update'     => route('todo.update'),
                'processing' => route('todo.processing'),
                'completed'  => route('todo.completed'),
            ],
            't' => [
                'title'        => __('to_do.to_do_list') ?: 'To-do list',
                'list'         => __('levels.list') ?: 'List',
                'add'          => __('to_do.to_do_add') ?: 'Add to-do',
                'edit'         => __('to_do.to_do_edit') ?: 'Edit',
                'delete'       => __('to_do.delete') ?: 'Delete',
                'title_field'  => __('to_do.title') ?: 'Title',
                'description'  => __('to_do.description') ?: 'Description',
                'assign'       => __('to_do.assign') ?: 'Assignee',
                'date'         => __('to_do.date') ?: 'Date',
                'note'         => __('to_do.note') ?: 'Note',
                'status'       => __('to_do.status') ?: 'Status',
                'status_update'=> __('to_do.status_update') ?: 'Status update',
                'actions'      => __('to_do.action') ?: 'Actions',
                'sl'           => __('to_do.sl') ?: 'SL',
                'pending'      => __('to_do.' . TodoStatus::PENDING) ?: 'Pending',
                'processing'   => __('to_do.' . TodoStatus::PROCESSING) ?: 'Processing',
                'completed'    => __('to_do.' . TodoStatus::COMPLETED) ?: 'Completed',
                'save'         => __('levels.save') ?: 'Save',
                'cancel'       => __('levels.cancel') ?: 'Cancel',
                'mark_processing' => 'Mark as processing',
                'mark_completed'  => 'Mark as completed',
                'select_user'  => (__('menus.select') ?: 'Select') . ' ' . (__('user.title') ?: 'user'),
                'no_rows'      => 'No items yet.',
                'delete_confirm' => 'Delete this to-do?',
                'prev'         => 'Prev',
                'next'         => 'Next',
                'showing_results' => 'Showing :from – :to of :total',
            ],
        ]);
    }

    public function store(Request $request)
    {
        if ($this->repo->store($request)) {
            Toastr::success(__('to_do.added_msg'), __('message.success'));
            return redirect()->route('todo.index');
        }
        Toastr::error(__('to_do.error_msg'), __('message.error'));
        return redirect()->back();
    }

    public function todoProcessing(Request $request)
    {
        if ($this->repo->todoProcessing($request->todo_id, $request)) {
            Toastr::success(__('to_do.todo_processing_success'), __('message.success'));
        } else {
            Toastr::error(__('to_do.error_msg'), __('message.error'));
        }
        return redirect()->route('todo.index');
    }

    public function todoComplete(Request $request)
    {
        if ($this->repo->todoComplete($request->todo_id, $request)) {
            Toastr::success(__('to_do.todo_compete_success'), __('message.success'));
        } else {
            Toastr::error(__('to_do.error_msg'), __('message.error'));
        }
        return redirect()->route('todo.index');
    }

    public function update(Request $request)
    {
        if ($this->repo->update($request)) {
            Toastr::success(__('to_do.update_msg'), __('message.success'));
        } else {
            Toastr::error(__('to_do.error_msg'), __('message.error'));
        }
        return redirect()->route('todo.index');
    }

    public function destroy($id)
    {
        $this->repo->delete($id);
        Toastr::success('Todo successfully deleted.', __('message.success'));
        return redirect()->route('todo.index');
    }

    public function create() {}

    private function statusLabel(int $s): string
    {
        return __('to_do.' . $s) ?: (string) $s;
    }
}
