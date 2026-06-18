<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\MerchantProfile\UpdateRequest;
use App\Http\Requests\MerchantProfile\UpdatePasswordRequest;
use App\Repositories\MerchantProfile\MerchantProfileInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class MerchantProfileController extends Controller
{
    protected $repo;
    public function __construct(MerchantProfileInterface $repo)
    {
        $this->repo = $repo;
    }

    public function view($id) // auth id
    {
        if (Auth::user()->id != $id) {
            abort(403);
        }
        $merchant = $this->repo->get(Auth::user()->id);
        if (! $merchant) {
            abort(404);
        }

        $imageUrl = null;
        if ($merchant->user && $merchant->user->image_id) {
            $upload = \App\Models\Backend\Upload::find($merchant->user->image_id);
            if ($upload && $upload->original && file_exists(public_path($upload->original))) {
                $imageUrl = static_asset($upload->original);
            }
        }

        return Inertia::render('Merchant/Profile', [
            'profile' => [
                'user' => [
                    'id'     => $merchant->user->id ?? null,
                    'name'   => $merchant->user->name ?? '',
                    'email'  => $merchant->user->email ?? '',
                    'mobile' => $merchant->user->mobile ?? '',
                    'image'  => $imageUrl,
                ],
                'merchant' => [
                    'id'            => $merchant->id,
                    'business_name' => $merchant->business_name ?? '',
                    'address'       => $merchant->address ?? '',
                ],
            ],
        ]);
    }

    public function create($id) // user id
    {
        $merchat = $this->repo->get(auth()->user()->id);
        return view('backend.merchant_profile.update',compact('merchat'));
    }

    public function changePassword($id)
    {
        if(Auth::user()->facebook_id !== null || Auth::user()->google_id !== null):
            return redirect()->back();
        endif;
        $merchat = $this->repo->get(auth()->user()->id);
        return view('backend.merchant_profile.change_password',compact('merchat'));
    }

    public function update($id, UpdateRequest $request)
    {
        if ($this->repo->update(Auth::user()->id, $request)) {
            return redirect()->route('merchant-profile.index', $id)
                ->with('success', __('merchant.profile_updated'));
        }
        return redirect()->back()->with('error', __('merchant.error_msg'));
    }

    public function updatePassword($id, UpdatePasswordRequest $request)
    {
        $result = $this->repo->updatePassword(Auth::user()->id, $request);
        if ($result === true) {
            return redirect()->route('merchant-profile.index', $id)
                ->with('success', __('merchant.password_updated'));
        }
        if ($result === false || $result === 0) {
            return back()->withErrors(['old_password' => __('merchant.old_password_mismatch')])->withInput();
        }
        return back()->with('error', __('merchant.error_msg'));
    }
}
