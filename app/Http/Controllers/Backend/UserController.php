<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Mail\UserCredentialsMail;
use App\Models\User;
use App\Repositories\Role\RoleInterface;
use App\Repositories\User\UserInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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
                'view'        => route('users.show', $u->id),
                'edit'        => route('users.edit', $u->id),
                'delete'      => route('user.delete', $u->id),
                'permissions' => route('users.edit', $u->id),
                'change_password' => route('users.change-password.form', $u->id),
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
                'view'    => __('levels.view') ?: 'View',
                'change_password' => __('menus.change_password') ?: 'Change password',
            ],
        ]);
    }

    public function create()
    {
        return \Inertia\Inertia::render('Admin/User/Form', [
            'mode'   => 'create',
            'entity' => null,
            'lookups' => [
                'hubs'         => collect($this->repo->hubs())->map(fn ($h) => ['value' => (string) $h->id, 'label' => $h->name])->values(),
                'departments'  => collect($this->repo->departments())->map(fn ($d) => ['value' => (string) $d->id, 'label' => $d->title])->values(),
                'designations' => collect($this->repo->designations())->map(fn ($d) => ['value' => (string) $d->id, 'label' => $d->title])->values(),
                'roles'        => collect($this->role->getRole())->map(fn ($r) => ['value' => (string) $r->id, 'label' => $r->name])->values(),
                'statuses'     => collect((array) trans('status'))->map(fn ($v, $k) => ['value' => (string) $k, 'label' => (string) $v])->values(),
            ],
            'flags' => [
                'is_super_admin' => isSuperadmin(),
            ],
            'urls' => [
                'submit' => route('users.store'),
                'cancel' => route('users.index'),
            ],
            't' => [
                'title'      => __('user.create_user') ?: 'Create user',
                'list_title' => __('user.title') ?: 'Users',
                'name'       => __('levels.name') ?: 'Name',
                'phone'      => __('levels.phone') ?: 'Phone',
                'address'    => __('levels.address') ?: 'Address',
                'designation'=> __('levels.designation') ?: 'Designation',
                'department' => __('levels.department') ?: 'Department',
                'role'       => __('levels.role') ?: 'Role',
                'status'     => __('levels.status') ?: 'Status',
                'email'      => __('levels.email') ?: 'Email',
                'password'   => __('levels.password') ?: 'Password',
                'nid'        => __('levels.nid') ?: 'NID',
                'joining_date'=> __('levels.joining_date') ?: 'Joining date',
                'hub'        => __('levels.hub') ?: 'Hub',
                'salary'     => __('levels.salary') ?: 'Salary',
                'image'      => __('levels.image') ?: 'Image',
                'save'       => __('levels.save') ?: 'Save',
                'cancel'     => __('levels.cancel') ?: 'Cancel',
                'back'       => __('levels.back') ?: 'Back',
                'none'       => 'None',
                'placeholder_name'   => __('placeholder.Enter_name') ?: 'Enter name',
                'placeholder_mobile' => __('placeholder.Enter_mobile') ?: 'Enter mobile',
                'placeholder_address'=> __('placeholder.Enter_address') ?: 'Enter address',
                'placeholder_email'  => __('placeholder.enter_email') ?: 'Enter email',
                'placeholder_password'=> __('placeholder.Enter_password') ?: 'Enter password',
                'placeholder_nid'    => __('placeholder.Enter_nid_number') ?: 'Enter NID',
                'placeholder_salary' => __('salary.title') ?: 'Salary',
            ],
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        $seatCap = optional(settings()->subscription)->user_count;
        if ($seatCap !== null) {
            $current = \App\Models\User::companywise()->count();
            if ($current >= (int) $seatCap) {
                Toastr::error('User seat limit reached ('.$seatCap.'). Please upgrade your plan.', __('message.error'));
                return redirect()->back()->withInput($request->all());
            }
        }

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
        // Ported from backend.user.edit (Bootstrap 4 Blade) to Inertia so it
        // matches the Create page + the rest of the admin panel. The Form.jsx
        // component already handles mode === 'edit' — same lookups shape, same
        // labels; the only new pieces are `entity` (with the current row) and
        // `urls.submit` pointing at users.update instead of users.store.
        $user = $this->repo->get($id);

        return \Inertia\Inertia::render('Admin/User/Form', [
            'mode'   => 'edit',
            'entity' => [
                'id'             => $user->id,
                'name'           => $user->name,
                'email'          => $user->email,
                'mobile'         => $user->mobile,
                'address'        => $user->address,
                'designation_id' => $user->designation_id,
                'department_id'  => $user->department_id,
                'role_id'        => $user->role_id,
                'status'         => (int) $user->status,
                'nid_number'     => $user->nid_number,
                'joining_date'   => $user->joining_date,
                'hub_id'         => $user->hub_id,
                'salary'         => $user->salary,
                'image'          => $user->image,
            ],
            'lookups' => [
                'hubs'         => collect($this->repo->hubs())->map(fn ($h) => ['value' => (string) $h->id, 'label' => $h->name])->values(),
                'departments'  => collect($this->repo->departments())->map(fn ($d) => ['value' => (string) $d->id, 'label' => $d->title])->values(),
                'designations' => collect($this->repo->designations())->map(fn ($d) => ['value' => (string) $d->id, 'label' => $d->title])->values(),
                'roles'        => collect($this->role->getRole())->map(fn ($r) => ['value' => (string) $r->id, 'label' => $r->name])->values(),
                'statuses'     => collect((array) trans('status'))->map(fn ($v, $k) => ['value' => (string) $k, 'label' => (string) $v])->values(),
            ],
            'flags' => [
                'is_super_admin' => isSuperadmin(),
                // Same rule the Blade view enforced: id=1 hides most fields.
                'is_owner'       => (int) $user->id === 1,
            ],
            'urls' => [
                'submit' => route('users.update'),
                'cancel' => route('users.index'),
            ],
            't' => [
                'title'      => __('user.edit_user') ?: 'Edit user',
                'edit'       => __('levels.edit') ?: 'Edit',
                'list_title' => __('user.title') ?: 'Users',
                'name'       => __('levels.name') ?: 'Name',
                'phone'      => __('levels.phone') ?: 'Phone',
                'address'    => __('levels.address') ?: 'Address',
                'designation'=> __('levels.designation') ?: 'Designation',
                'department' => __('levels.department') ?: 'Department',
                'role'       => __('levels.role') ?: 'Role',
                'status'     => __('levels.status') ?: 'Status',
                'email'      => __('levels.email') ?: 'Email',
                'password'   => __('levels.password') ?: 'Password',
                'nid'        => __('levels.nid') ?: 'NID',
                'joining_date'=> __('levels.joining_date') ?: 'Joining date',
                'hub'        => __('levels.hub') ?: 'Hub',
                'salary'     => __('levels.salary') ?: 'Salary',
                'image'      => __('levels.image') ?: 'Image',
                'save'       => __('levels.save_change') ?: __('levels.save') ?: 'Save',
                'cancel'     => __('levels.cancel') ?: 'Cancel',
                'back'       => __('levels.back') ?: 'Back',
                'none'       => __('levels.none') ?: 'None',
                'placeholder_name'   => __('placeholder.Enter_name') ?: 'Enter name',
                'placeholder_mobile' => __('placeholder.Enter_mobile') ?: 'Enter mobile',
                'placeholder_address'=> __('placeholder.Enter_address') ?: 'Enter address',
                'placeholder_email'  => __('placeholder.enter_email') ?: 'Enter email',
                'placeholder_password'=> __('placeholder.Enter_password') ?: 'Leave blank to keep current password',
                'placeholder_nid'    => __('placeholder.Enter_nid_number') ?: 'Enter NID',
                'placeholder_salary' => __('salary.title') ?: 'Salary',
            ],
        ]);
    }

    public function update(UpdateUserRequest $request)
    {
        // Same flash-key swap done in ParcelController::store (213f935) so the
        // Inertia FlashBanner in AdminLayout renders the outcome. Toastr writes
        // to a session key nothing on the frontend reads.
        if ($this->repo->update($request->id, $request)) {
            return redirect()->route('users.index')
                ->with('success', __('User successfully updated.'));
        }
        return redirect()->back()->withInput()
            ->with('error', __('Something went wrong.'));
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
    /**
     * Read-only user detail page. Same shape as ProfileController::view but
     * for any user id — accessible to anyone with user_read permission.
     */
    public function show($id)
    {
        $u = $this->repo->get($id);
        abort_unless($u, 404);
        $u->loadMissing(['role', 'hub', 'department', 'designation']);

        return \Inertia\Inertia::render('Admin/User/View', [
            'user' => [
                'id'             => $u->id,
                'name'           => $u->name,
                'email'          => $u->email,
                'mobile'         => $u->mobile,
                'image'          => $u->image,
                'address'        => $u->address,
                'nid_number'     => $u->nid_number,
                'unique_id'      => $u->unique_id,
                'user_type'      => $u->user_type,
                'joining_date'   => $u->joining_date,
                'salary'         => (float) ($u->salary ?? 0),
                'status'         => (int) $u->status,
                'role'           => optional($u->role)->name,
                'hub'            => optional($u->hub)->name,
                'department'     => optional($u->department)->title,
                'designation'    => optional($u->designation)->title,
            ],
            'currency' => settings()->currency,
            'permissions' => [
                'update'          => hasPermission('user_update'),
                'change_password' => hasPermission('user_update'),
            ],
            'urls' => [
                'edit'            => $this->safeRoute('users.edit', $u->id, "/admin/users/edit/{$u->id}"),
                'change_password' => $this->safeRoute('users.change-password.form', $u->id, "/admin/users/change-password/{$u->id}"),
                'send_credentials'=> $this->safeRoute('users.send-credentials', $u->id, "/admin/users/send-credentials/{$u->id}"),
                'back'            => $this->safeRoute('users.index', null, '/admin/users'),
            ],
            't' => $this->userViewLabels(),
        ]);
    }

    private function safeRoute(string $name, $param = null, string $fallback = ''): string
    {
        try { return $param === null ? route($name) : route($name, $param); }
        catch (\Throwable $e) { return $fallback ?: url('/'); }
    }

    /**
     * Admin-side change password form. No "current password" step — admins
     * have authority. UI gets a "send new password to user's email" toggle
     * so the recipient can be notified as part of the same submission.
     */
    public function changePasswordForm($id)
    {
        $u = $this->repo->get($id);
        abort_unless($u, 404);

        return \Inertia\Inertia::render('Admin/User/ChangePassword', [
            'user' => [
                'id'    => $u->id,
                'name'  => $u->name,
                'email' => $u->email,
                'image' => $u->image,
            ],
            'urls' => [
                'submit' => $this->safeRoute('users.change-password.update', $u->id, "/admin/users/change-password/{$u->id}"),
                'cancel' => $this->safeRoute('users.show', $u->id, "/admin/users/view/{$u->id}"),
            ],
            't' => $this->userChangePasswordLabels($u),
        ]);
    }

    public function changePasswordUpdate(Request $request, $id)
    {
        $request->validate([
            'new_password'     => ['required', 'string', 'min:6'],
            'confirm_password' => ['required', 'same:new_password'],
        ], [
            'confirm_password.same' => 'Password confirmation does not match.',
        ]);

        $u = User::find($id);
        abort_unless($u, 404);

        $u->password = Hash::make($request->input('new_password'));
        $u->save();

        if ($request->boolean('send_email') && ! empty($u->email)) {
            try {
                Mail::to($u->email)->send(new UserCredentialsMail(
                    userName:  (string) $u->name,
                    email:     (string) $u->email,
                    password:  (string) $request->input('new_password'),
                    loginUrl:  url('/login'),
                ));
            } catch (\Throwable $e) {
                \Log::error('UserCredentialsMail failed: '.$e->getMessage(), ['user_id' => $u->id]);
                return redirect($this->safeRoute('users.show', $u->id, "/admin/users/view/{$u->id}"))
                    ->with('warning', __('Password updated, but the email could not be sent. Please share the new password manually.'));
            }
        }

        return redirect($this->safeRoute('users.show', $u->id, "/admin/users/view/{$u->id}"))
            ->with('success', __('Password updated.').($request->boolean('send_email') && !empty($u->email) ? ' '.__('Login info emailed to the user.') : ''));
    }

    /**
     * Send an email invite with the current login link (no password included
     * because it's already hashed). Useful when a user was created before the
     * mailer feature existed and needs a nudge to sign in.
     */
    public function sendCredentials(Request $request, $id)
    {
        $u = User::find($id);
        abort_unless($u, 404);
        abort_if(empty($u->email), 422, 'This user has no email on file.');

        try {
            Mail::to($u->email)->send(new UserCredentialsMail(
                userName: (string) $u->name,
                email:    (string) $u->email,
                password: null, // no plaintext available — reset flow will be prompted
                loginUrl: url('/login'),
            ));
        } catch (\Throwable $e) {
            \Log::error('UserCredentialsMail invite failed: '.$e->getMessage(), ['user_id' => $u->id]);
            return back()->with('error', __('Could not send the email. Check the mail configuration.'));
        }

        return back()->with('success', __('Login info emailed to the user.'));
    }

    private function userViewLabels(): array
    {
        return [
            'title'     => __('user.title') ?: 'Users',
            'view'      => __('levels.view') ?: 'View',
            'edit'      => __('levels.edit') ?: 'Edit',
            'change_password' => __('menus.change_password') ?: 'Change password',
            'send_credentials'=> __('user.send_credentials') ?: 'Send login info by email',
            'back'      => __('levels.back') ?: 'Back',
            'name'      => __('levels.name') ?: 'Name',
            'email'     => __('levels.email') ?: 'Email',
            'phone'     => __('levels.phone') ?: 'Phone',
            'address'   => __('levels.address') ?: 'Address',
            'nid'       => __('levels.nid') ?: 'NID',
            'unique_id' => __('levels.unique_id') ?: 'Unique ID',
            'role'      => __('levels.role') ?: 'Role',
            'hub'       => __('levels.hub') ?: 'Hub',
            'department' => __('levels.department') ?: 'Department',
            'designation'=> __('levels.designation') ?: 'Designation',
            'joining_date' => __('levels.joining_date') ?: 'Joining date',
            'salary'    => __('levels.salary') ?: 'Salary',
            'status'    => __('levels.status') ?: 'Status',
            'active'    => __('levels.active') ?: 'Active',
            'inactive'  => __('levels.inactive') ?: 'Inactive',
            'identity'  => 'Identity',
            'work'      => 'Work',
            'contact'   => 'Contact',
            'no_email_hint'  => 'This user has no email on file, so the email button is disabled.',
        ];
    }

    private function userChangePasswordLabels($u): array
    {
        return [
            'title'            => (__('user.title') ?: 'Users').' · '.(__('menus.change_password') ?: 'Change password'),
            'heading'          => __('menus.change_password') ?: 'Change password',
            'subheading'       => __('user.change_pw_for') ?: 'For: :name',
            'intro'            => __('user.change_pw_intro') ?: "Set a new password for this user. They'll be signed out of other devices on next login.",
            'new_password'     => __('levels.new_password') ?: 'New password',
            'confirm_password' => __('levels.confirm_password') ?: 'Confirm new password',
            'send_email'       => __('user.send_email_option') ?: 'Also email the new password to the user',
            'send_email_hint'  => $u->email ? __('user.send_email_hint', ['email' => $u->email]) : __('user.send_email_no_email'),
            'no_email'         => empty($u->email),
            'save'             => __('levels.save_change') ?: 'Save changes',
            'cancel'           => __('levels.cancel') ?: 'Cancel',
            'requirements'     => __('profile.password_reqs') ?: 'Minimum 6 characters. Use a mix of letters, numbers, and symbols.',
            'strength_weak'    => __('profile.strength_weak') ?: 'Weak',
            'strength_ok'      => __('profile.strength_ok') ?: 'Okay',
            'strength_strong'  => __('profile.strength_strong') ?: 'Strong',
        ];
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
