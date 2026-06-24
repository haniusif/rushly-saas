<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\Repositories\Role\RoleInterface;
use App\Repositories\User\UserInterface;
use Brian2694\Toastr\Facades\Toastr;
class UserController extends Controller
{
    protected $repo;
    public function __construct(UserInterface $repo,RoleInterface $role)
    {
        $this->repo = $repo;
        $this->role =$role;
    }

    public function index(Request $request)
    {
        return $this->renderIndex($this->repo->all(), $request);
    }
    public function filter(Request $request)
    {
        return $this->renderIndex($this->repo->filter($request), $request);
    }

    private function renderIndex($paginator, Request $request)
    {
        $rows = collect($paginator->items())->map(fn ($u) => [
            'id'       => $u->id,
            'name'     => $u->name,
            'email'    => $u->email,
            'mobile'   => $u->mobile,
            'image'    => $u->image,
            'hub'      => optional($u->hub)->name,
            'role'     => optional($u->role)->name,
            'salary'   => (float) ($u->salary ?? 0),
            'status'   => (int) ($u->status ?? 1),
            'is_locked'=> $u->id == 1 || (string) $u->company_owner === 'yes',
            'urls' => [
                'edit'        => route('users.edit', $u->id),
                'delete'      => route('user.delete', $u->id),
                'permissions' => route('users.edit', $u->id),
            ],
        ])->values();

        return \Inertia\Inertia::render('Admin/User/Index', [
            'rows'       => $rows,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
                'total'        => $paginator->total(),
                'prev_url'     => $paginator->previousPageUrl(),
                'next_url'     => $paginator->nextPageUrl(),
            ],
            'filters' => [
                'name'  => (string) ($request->name ?? ''),
                'email' => (string) ($request->email ?? ''),
                'phone' => (string) ($request->phone ?? ''),
            ],
            'currency' => settings()->currency,
            'permissions' => [
                'create' => hasPermission('user_create'),
                'update' => hasPermission('user_update'),
                'delete' => hasPermission('user_delete'),
            ],
            'urls' => [
                'index'  => route('users.index'),
                'filter' => route('users.filter'),
                'create' => route('users.create'),
            ],
            't' => [
                'title'   => __('user.title') ?: 'Users',
                'list'    => __('levels.list') ?: 'List',
                'add'     => __('levels.add') ?: 'Add',
                'edit'    => __('levels.edit') ?: 'Edit',
                'delete'  => __('levels.delete') ?: 'Delete',
                'actions' => __('levels.actions') ?: 'Actions',
                'details' => __('levels.details') ?: 'Details',
                'hub'     => __('levels.hub') ?: 'Hub',
                'role'    => __('levels.role') ?: 'Role',
                'salary'  => __('levels.salary') ?: 'Salary',
                'status'  => __('levels.status') ?: 'Status',
                'name'    => __('levels.name') ?: 'Name',
                'email'   => __('levels.email') ?: 'Email',
                'phone'   => __('levels.phone') ?: 'Phone',
                'filter'  => __('levels.filter') ?: 'Filter',
                'clear'   => __('levels.clear') ?: 'Clear',
                'status_active'    => __('levels.active') ?: 'Active',
                'status_inactive'  => __('levels.inactive') ?: 'Inactive',
                'locked_hint' => 'Owner — cannot delete',
                'no_rows'     => 'No users yet.',
                'delete_confirm' => 'Delete this user?',
                'prev'    => 'Prev',
                'next'    => 'Next',
                'showing_results' => 'Showing :from – :to of :total',
            ],
        ]);
    }

    public function create()
    {
        $hubs         = $this->repo->hubs();
        $departments  = $this->repo->departments();
        $designations = $this->repo->designations();
        $roles        = $this->role->getRole();
        return view('backend.user.create',compact('hubs','departments','designations','roles'));
    }

    public function store(StoreUserRequest $request)
    {
        if($this->repo->store($request)){
            Toastr::success('User successfully added.',__('message.success'));
            return redirect()->route('users.index');
        }else{
            Toastr::error('Something went wrong.',__('message.error'));
            return redirect()->back();
        }
    }

    public function edit($id)
    {
        $user         = $this->repo->get($id);
        $hubs         = $this->repo->hubs();
        $departments  = $this->repo->departments();
        $designations = $this->repo->designations();
        $roles        = $this->role->getRole();
        return view('backend.user.edit',compact('user','hubs','departments','designations','roles'));
    }

    public function update(UpdateUserRequest $request)
    {

        if($this->repo->update($request->id, $request)){
            Toastr::success('User successfully updated.',__('message.success'));
            return redirect()->route('users.index');
        }else{
            Toastr::error('Something went wrong.',__('message.error'));
            return redirect()->back();
        }
    }

    public function destroy($id)
    {
       
        if($this->repo->delete($id) == 'delete'){
            Toastr::success('User successfully deleted.',__('message.success'));
            return back();
        }
        elseif($this->repo->delete($id) == 0){
            Toastr::warning('Super admin cannot be deleted!',__('message.warning'));
            return back();
        }
        else{
            Toastr::error('Something went wrong.',__('message.error'));
            return redirect()->back();
        }
    }
    //user permissions
    public function permission($id){
        $user        = User::where('id',$id)->first();
        $permissions = $this->role->permissions($user->role->slug);
       
        return view('backend.user.permissions',compact('user','permissions'));
    }
    public function permissionsUpdate(Request $request){
        if($this->repo->permissionUpdate($request->id,$request)){
            Toastr::success('Permissions successfully updated.',__('message.success'));
            return redirect()->route('users.index');
        }else{
            Toastr::error('Something went wrong.',__('message.error'));
            return redirect()->back();
        }
    }


}
