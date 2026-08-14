<?php

namespace App\Http\Controllers\Backend\MerchantPanel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Repositories\MerchantPanel\Support\SupportInterface;
use App\Http\Requests\Support\StoreRequest;
use Illuminate\Support\Facades\Validator;
use Brian2694\Toastr\Facades\Toastr;
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
        $i = (($paginator->currentPage() - 1) * $paginator->perPage()) + 1;

        $rows = collect($paginator->items())->map(function ($s) use (&$i) {
            return [
                'serial'         => $i++,
                'id'             => $s->id,
                'subject'        => $s->subject,
                'service'        => $s->service,
                'date'           => $s->date ? dateFormat($s->date) : null,
                'user_name'      => optional($s->user)->name,
                'user_email'     => optional($s->user)->email,
                'department'     => optional($s->department)->title,
                'status'         => (int) $s->status,
                'status_label'   => strip_tags((string) $s->my_status),
                'status_active'  => (int) $s->status === 1,
                'view_url'       => route('merchant-panel.support.view', $s->id),
                'edit_url'       => route('merchant-panel.support.edit', $s->id),
                'delete_url'     => route('merchant-panel.support.delete', $s->id),
            ];
        })->values();

        return Inertia::render('Merchant/Support/Index', [
            'rows'       => $rows,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
                'links'        => $paginator->linkCollection()->map(fn ($l) => [
                    'url'    => $l['url'],
                    'label'  => $l['label'],
                    'active' => (bool) $l['active'],
                ])->values(),
            ],
            'urls' => [
                'create' => route('merchant-panel.support.add'),
            ],
            't' => [
                'title'         => __('support.supprot') ?: 'Support',
                'list'          => __('levels.list') ?: 'List',
                'add'           => __('support.supprot_add') ?: 'Add support',
                'dashboard'     => __('levels.dashboard') ?: 'Dashboard',
                'sl'            => __('support.sl') ?: '#',
                'user_info'     => __('support.user_info') ?: 'User info',
                'subject'       => __('support.subject') ?: 'Subject',
                'date'          => __('support.date') ?: 'Date',
                'status'        => __('levels.status') ?: 'Status',
                'action'        => __('support.action') ?: 'Action',
                'name'          => __('support.name') ?: 'Name',
                'email'         => __('support.email') ?: 'Email',
                'department'    => __('support.department_id') ?: 'Department',
                'service'       => __('support.service') ?: 'Service',
                'view'          => __('levels.view') ?: 'View',
                'edit'          => __('levels.edit') ?: 'Edit',
                'delete'        => __('levels.delete') ?: 'Delete',
                'empty'         => __('levels.no_data_found') ?: 'No support tickets yet.',
            ],
        ]);
    }

    public function create()
    {
        $departments = $this->repo->departments();

        $services = collect((array) trans('SalaryService'))
            ->map(fn ($label, $key) => ['value' => (string) $key, 'label' => (string) $label])
            ->values();

        return Inertia::render('Merchant/Support/Create', [
            'lookups' => [
                'departments' => collect($departments)->map(fn ($d) => [
                    'id'   => $d->id,
                    'name' => $d->name,
                ])->values(),
                'services'    => $services,
                'priorities'  => [
                    ['value' => 'low',    'label' => __('levels.priority_low') ?: 'Low'],
                    ['value' => 'medium', 'label' => __('levels.priority_medium') ?: 'Medium'],
                    ['value' => 'high',   'label' => __('levels.priority_high') ?: 'High'],
                ],
            ],
            'urls' => [
                'store'  => route('merchant-panel.support.store'),
                'cancel' => route('merchant-panel.support.index'),
            ],
            't' => [
                'title'         => __('support.supprot') ?: 'Ticket',
                'title_index'   => __('menus.support') ?: 'Tickets',
                'add'           => __('support.supprot_add') ?: 'Add ticket',
                'department'    => __('support.department_id') ?: 'Department',
                'department_ph' => __('merchantPlaceholder.department') ?: 'Select department',
                'service'       => __('support.service') ?: 'Service',
                'service_ph'    => __('merchantPlaceholder.service') ?: 'Select service',
                'priority'      => __('support.priority') ?: 'Priority',
                'priority_ph'   => __('merchantPlaceholder.priority') ?: 'Select priority',
                'subject'       => __('support.subject') ?: 'Subject',
                'subject_ph'    => __('merchantPlaceholder.subject') ?: 'Subject',
                'date'          => __('levels.date') ?: 'Date',
                'description'   => __('levels.description') ?: 'Description',
                'attached_file' => __('levels.file') ?: 'Attachment',
                'save'          => __('levels.save') ?: 'Save',
                'cancel'        => __('levels.cancel') ?: 'Cancel',
            ],
        ]);
    }
 
    public function store(StoreRequest $request)
    {
        if($this->repo->store($request)){
            Toastr::success(__('support.added_msg'),__('message.success'));
            return redirect()->route('merchant-panel.support.index');
        }
        else{
            Toastr::error(__('support.error_msg'),__('message.error'));
            return redirect()->back()->withInput($request->all());
        }
    }

    public function show($id)
    {
        //
    }


    public function edit($id)
    {
        $departments   = $this->repo->departments();
        $singleSupport = $this->repo->get($id);
        return view('backend.merchant_panel.support.edit',compact('departments','singleSupport'));
    }


    public function update(StoreRequest $request)
    {
        if($this->repo->update($request->id,$request)){
            Toastr::success(__('support.update_msg'),__('message.success'));
            return redirect()->route('merchant-panel.support.index');
        }
        else{
            Toastr::error(__('support.error_msg'),__('message.error'));
            return redirect()->back()->withInput($request->all());
        }
    }

    public function destroy($id)
    {
        if($this->repo->delete($id)){
            Toastr::success(__('support.delete_msg'),__('message.success'));
            return redirect()->route('merchant-panel.support.index');
        }
        else{
            Toastr::error(__('support.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }


    public function view($id){
        $singleSupport = $this->repo->get($id);
        $chats         = $this->repo->chats($id);
        return view('backend.merchant_panel.support.view',compact('singleSupport','chats'));
    }

    public function supportReply(Request $request){
        $validator  = Validator::make($request->all(),[
            'message'   => 'required'
        ]);
        if($validator->fails()):
            return redirect()->back()->withErrors($validator)->withInput();
        endif;

        if($this->repo->reply($request)){
            Toastr::success(__('support.reply_msg'),__('message.success'));
            return redirect()->route('merchant-panel.support.view',$request->support_id);
        }else{
            Toastr::error(__('support.error_msg'),__('message.error'));
            return redirect()->back()->withInput($request->all());
        }
    }
}
