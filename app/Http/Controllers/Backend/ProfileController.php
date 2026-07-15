<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Profile\UpdateRequest;
use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Http\Controllers\Backend\BrowserSessionsController;
use App\Repositories\Profile\ProfileInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ProfileController extends Controller
{
    protected $repo;
    public function __construct(ProfileInterface $repo)
    {
        $this->repo = $repo;
    }

    public function view($id, \Illuminate\Http\Request $request)
    {
        if(Auth::user()->id != $id):
            abort(500);
        endif;
        $u = $this->repo->get($id);
        $u->loadMissing(['role', 'hub', 'department', 'designation']);

        $bs = BrowserSessionsController::sessionsPayload($request);

        // Hard-coded destroy path so tinker + non-tenant contexts can still
        // resolve — the route lives inside the tenant-domain gate in
        // routes/web.php and route() can't find it at CLI-eval time.
        $bsDestroy = url('/admin/browser-sessions');

        return Inertia::render('Admin/Profile/View', [
            'browser_sessions'    => $bs['sessions'],
            'browser_sessions_t'  => $bs['t'],
            'browser_sessions_url'=> $bsDestroy,
            'user' => [
                'id'              => $u->id,
                'name'            => $u->name,
                'email'           => $u->email,
                'mobile'          => $u->mobile,
                'image'           => $u->image,
                'address'         => $u->address,
                'nid_number'      => $u->nid_number,
                'unique_id'       => $u->unique_id,
                'user_type'       => $u->user_type,
                'joining_date'    => $u->joining_date,
                'salary'          => (float) ($u->salary ?? 0),
                'status'          => (int) $u->status,
                'role'            => optional($u->role)->name,
                'hub'             => optional($u->hub)->name,
                'department'      => optional($u->department)->title,
                'designation'     => optional($u->designation)->title,
            ],
            'currency'    => settings()->currency,
            'urls' => [
                'edit'            => route('profile.edit', $u->id),
                'change_password' => route('password.change', $u->id),
                'dashboard'       => route('dashboard.index'),
            ],
            't' => [
                'title'          => __('menus.profile') ?: 'My profile',
                'edit'           => __('levels.edit') ?: 'Edit',
                'change_password'=> 'Change password',
                'name'           => __('levels.name') ?: 'Name',
                'email'          => __('levels.email') ?: 'Email',
                'phone'          => __('levels.phone') ?: 'Phone',
                'address'        => __('levels.address') ?: 'Address',
                'nid'            => __('levels.nid') ?: 'NID',
                'unique_id'      => __('levels.unique_id') ?: 'Unique ID',
                'role'           => __('levels.role') ?: 'Role',
                'hub'            => __('levels.hub') ?: 'Hub',
                'department'     => __('levels.department') ?: 'Department',
                'designation'    => __('levels.designation') ?: 'Designation',
                'joining_date'   => __('levels.joining_date') ?: 'Joining date',
                'salary'         => __('levels.salary') ?: 'Salary',
                'status'         => __('levels.status') ?: 'Status',
                'active'         => __('levels.active') ?: 'Active',
                'inactive'       => __('levels.inactive') ?: 'Inactive',
                'identity'       => 'Identity',
                'work'           => 'Work',
                'contact'        => 'Contact',
            ],
        ]);
    }

    public function create($id)
    {
        $user = $this->repo->get(auth()->user()->id);
        return view('backend.profile.update',compact('user'));
    }

    public function changePassword($id)
    {
        if ((int) auth()->user()->id !== (int) $id) {
            abort(403);
        }
        $u = $this->repo->get(auth()->user()->id);

        return Inertia::render('Admin/Profile/ChangePassword', [
            'user' => [
                'id'    => $u->id,
                'name'  => $u->name,
                'email' => $u->email,
                'image' => $u->image,
            ],
            'urls' => [
                'submit'  => route('profile.password.update', $u->id),
                'cancel'  => route('profile.index', $u->id),
                'profile' => route('profile.index', $u->id),
            ],
            't' => [
                'title'            => (__('menus.profile') ?: 'Profile').' · '.(__('menus.change_password') ?: 'Change password'),
                'heading'          => __('menus.change_password') ?: 'Change password',
                'intro'            => __('profile.password_intro') ?: 'Choose a strong new password. You will need to sign in again on other devices.',
                'old_password'     => __('levels.old_password') ?: 'Current password',
                'new_password'     => __('levels.new_password') ?: 'New password',
                'confirm_password' => __('levels.confirm_password') ?: 'Confirm new password',
                'save'             => __('levels.save_change') ?: 'Save changes',
                'cancel'           => __('levels.cancel') ?: 'Cancel',
                'requirements'     => __('profile.password_reqs') ?: 'Minimum 6 characters. Use a mix of letters, numbers, and symbols for stronger security.',
                'strength_weak'    => __('profile.strength_weak') ?: 'Weak',
                'strength_ok'      => __('profile.strength_ok') ?: 'Okay',
                'strength_strong'  => __('profile.strength_strong') ?: 'Strong',
            ],
        ]);
    }

    public function update($id, UpdateRequest $request)
    {
        if($this->repo->update(auth()->user()->id, $request)){
            Toastr::success('Profile updated successfully.',__('message.success'));
            return redirect()->route('profile.index', $id);
        }else{
            Toastr::error('Something went wrong.',__('message.error'));
            return redirect()->back();
        }
    }

    public function updatePassword($id, UpdatePasswordRequest $request)
    {
        $result = $this->repo->updatePassword(auth()->user()->id, $request);
        if($result == 1){
            Toastr::success('Password updated successfully',__('message.success'));
            return redirect()->route('profile.index', $id);
        }
        elseif($result == 0){
            Toastr::warning('Old password not match!',__('message.warning'));
            return redirect()->back()->withInput();
        }
        else
        {
            Toastr::error('Something went wrong.',__('message.error'));
            return redirect()->back();
        }
    }

}
