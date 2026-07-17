<?php

namespace App\Http\Controllers\Backend;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\Backend\HubInCharge;
use App\Repositories\HubInCharge\HubInChargeInterface;
use Illuminate\Http\Request;
use App\Http\Requests\HubInCharge\HubInChargeRequest;
use Brian2694\Toastr\Facades\Toastr;
use Inertia\Inertia;

class HubInChargeController extends Controller
{
    protected $repo;
    public function __construct(HubInChargeInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index($hubID)
    {
        $hubInCharges = $this->repo->all($hubID);
        $hub          = $this->repo->hub($hubID);
        if (!$hub) {
            return redirect()->route('hubs.index');
        }

        $rows = collect($hubInCharges)->map(fn ($ic) => [
            'id'        => $ic->id,
            'user_id'   => $ic->user_id,
            'name'      => optional($ic->user)->name,
            'email'     => optional($ic->user)->email,
            'mobile'    => optional($ic->user)->mobile,
            'unique_id' => optional($ic->user)->unique_id,
            'image'     => optional($ic->user)->image,
            'status'    => (int) $ic->status,
            'is_active' => (int) $ic->status === Status::ACTIVE,
            'urls' => [
                'edit'     => route('hub-incharge.edit',     ['hubID' => $hubID, 'id' => $ic->id]),
                'destroy'  => route('hub-incharge.destroy',  ['hubID' => $hubID, 'id' => $ic->id]),
                'assigned' => route('hub-incharge.assigned', ['hubID' => $hubID, 'id' => $ic->id]),
            ],
        ])->values();

        return Inertia::render('Admin/HubInCharge/Index', [
            'hub' => [
                'id'      => $hub->id,
                'name'    => $hub->name,
                'phone'   => $hub->phone,
                'address' => $hub->address,
            ],
            'rows'        => $rows,
            'permissions' => [
                'create'   => hasPermission('hub_incharge_create'),
                'update'   => hasPermission('hub_incharge_update'),
                'delete'   => hasPermission('hub_incharge_delete'),
                'assigned' => hasPermission('hub_incharge_assigned'),
            ],
            'urls' => [
                'hubs'    => route('hubs.index'),
                'hub_view'=> route('hub.view', $hub->id),
                'create'  => route('hub-incharge.create', $hub->id),
            ],
            't' => [
                'title'           => __('incharge.title') ?: 'Hub in-charges',
                'hubs'            => __('hub.title') ?: 'Hubs',
                'hub'             => __('levels.hub') ?: 'Hub',
                'back_to_hub'     => 'Back to hub',
                'add'             => __('levels.add') ?: 'Add in-charge',
                'name'            => __('levels.name') ?: 'Name',
                'email'           => __('levels.email') ?: 'Email',
                'phone'           => __('levels.phone') ?: 'Phone',
                'unique_id'       => __('levels.unique_id') ?: 'ID',
                'status'          => __('levels.status') ?: 'Status',
                'active'          => __('levels.active') ?: 'Active',
                'inactive'        => __('levels.inactive') ?: 'Inactive',
                'edit'            => __('levels.edit') ?: 'Edit',
                'delete'          => __('levels.delete') ?: 'Delete',
                'delete_confirm'  => 'Remove this in-charge?',
                'assign_active'   => 'Set as active in-charge',
                'no_rows'         => 'No in-charges assigned yet.',
                'actions'         => __('levels.actions') ?: 'Actions',
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function create($hubID)
    {
        $hub = $this->repo->hub($hubID);
        if (! $hub) return redirect()->route('hubs.index');
        return Inertia::render('Admin/HubInCharge/Form', $this->formProps($hub, null));
    }

    /**
     * Shared props for the create + edit Inertia form. Only differs in the
     * submit URL/method and the seed values; the users lookup is the same
     * pool in both modes (all users who could be assigned).
     */
    private function formProps($hub, $inCharge): array
    {
        $isEdit = $inCharge !== null;
        $users  = $this->repo->users();

        return [
            'mode' => $isEdit ? 'edit' : 'create',
            'hub'  => [
                'id'      => $hub->id,
                'name'    => (string) $hub->name,
                'phone'   => (string) $hub->phone,
                'address' => (string) $hub->address,
            ],
            'row'  => [
                'id'      => $isEdit ? $inCharge->id : null,
                'user_id' => $isEdit ? (string) $inCharge->user_id : '',
                'status'  => $isEdit ? (string) $inCharge->status  : (string) Status::ACTIVE,
            ],
            'lookups' => [
                'users' => collect($users)->map(fn ($u) => [
                    'value' => (string) $u->id,
                    'label' => $u->name . ($u->mobile ? ' (' . $u->mobile . ')' : ''),
                ])->values(),
                'statuses' => collect(trans('status'))->map(fn ($label, $key) => [
                    'value' => (string) $key,
                    'label' => $label,
                ])->values(),
            ],
            'urls' => [
                'submit' => $isEdit
                    ? route('hub-incharge.update', ['hubID' => $hub->id, 'id' => $inCharge->id])
                    : route('hub-incharge.store',  $hub->id),
                'index'  => route('hub-incharge.index', $hub->id),
                'hubs'   => route('hubs.index'),
            ],
            't' => [
                'title'      => ($isEdit ? __('levels.edit') : __('levels.create')) . ' · ' . (__('incharge.title') ?: 'Hub in-charge'),
                'section'    => ($isEdit ? __('levels.edit') : __('levels.create')) . ' ' . (__('incharge.title') ?: 'in-charge'),
                'hubs'       => __('hubs.title') ?: 'Hubs',
                'incharge'   => __('incharge.title') ?: 'In-charges',
                'user'       => __('levels.user') ?: 'User',
                'status'     => __('levels.status') ?: 'Status',
                'save'       => __('levels.save') ?: 'Save',
                'cancel'     => __('levels.cancel') ?: 'Cancel',
                'select_user'=> '-- Select user --',
            ],
        ];
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param HubInChargeRequest $request
     * @param $hubID
     * @return \Illuminate\Http\Response
     */
    public function store(HubInChargeRequest $request, $hubID)
    {

        if($this->repo->store($hubID,$request)){
            Toastr::success(__('incharge.added_msg'),__('message.success'));
            return redirect()->route('hub-incharge.index',$hubID);
        }else{
            Toastr::error(__('incharge.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($hubID,$id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($hubID, $id)
    {
        $hub      = $this->repo->hub($hubID);
        $inCharge = $this->repo->get($hubID, $id);
        if (! $hub) return redirect()->route('hubs.index');
        if (! $inCharge) return redirect()->route('hub-incharge.index', $hubID);
        return Inertia::render('Admin/HubInCharge/Form', $this->formProps($hub, $inCharge));
    }


    /**
     * Update the specified resource in storage.
     *
     * @param $hubID
     * @param int $id
     * @param HubInChargeRequest $request
     * @return \Illuminate\Http\Response
     */
    public function update($hubID, $id, HubInChargeRequest $request)
    {
        if($this->repo->update($hubID, $id, $request)){
            Toastr::success(__('incharge.update_msg'),__('message.success'));
            return redirect()->route('hub-incharge.index',$hubID);
        }else{
            Toastr::error(__('incharge.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($hubID,$id)
    {


        $this->repo->delete($id);

        Toastr::success(__('incharge.delete_msg'),__('message.success'));
        return back();
    }

    public function assigned($hubID,$id)
    {
        $inCharge                   = $this->repo->get($hubID,$id);
        $queryArray['user_id']      = $inCharge->user_id;
        $queryArray['status']       = Status::ACTIVE;
        $hubInCharge                = HubInCharge::where($queryArray)->where('id', '!=', $id)->first();

        if(!blank($hubInCharge)){
            Toastr::error(__('validation.attributes.user_assigned'),__('message.error'));
            return redirect()->back();
        }
        $queryHubArray['user_id']      = $inCharge->user_id;
        $queryHubArray['hub_id']       = $hubID;
        $userHubUnique = HubInCharge::where($queryHubArray)->where('id', '!=', $id)->first();

        if(!blank($userHubUnique)){
            Toastr::error(__('validation.attributes.user_exists'),__('message.error'));
            return redirect()->back();
        }

        if($this->repo->assignedHub($hubID,$inCharge)){
            Toastr::success(__('incharge.assigned_msg'),__('message.success'));
            return redirect()->route('hub-incharge.index',$hubID);
        }else{
            Toastr::error(__('incharge.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }
}
