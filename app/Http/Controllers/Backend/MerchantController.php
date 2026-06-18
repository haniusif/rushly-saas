<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Services\SmsService;
use Illuminate\Http\Request;
use App\Http\Requests\Merchant\StoreRequest;
use App\Http\Requests\Merchant\SignUpRequest;
use App\Http\Requests\Merchant\UpdateRequest;
use App\Http\Requests\Merchant\OtpRequest;
use App\Mail\MerchantSignup;
use App\Repositories\Invoice\InvoiceInterface;
use App\Repositories\Merchant\MerchantInterface;
use Illuminate\Support\Facades\Mail;
use Brian2694\Toastr\Facades\Toastr;
 
class MerchantController extends Controller
{
  
    protected $repo,$invoiceRepo;
    public function __construct(MerchantInterface $repo, InvoiceInterface $invoiceRepo)
    {
        $this->repo        = $repo;
        $this->invoiceRepo = $invoiceRepo;
    }

    public function index()
    {
       
        $merchants = $this->repo->all();
        
        
        return view('backend.merchant.index',compact('merchants'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $hubs = $this->repo->all_hubs();

        return view('backend.merchant.create', compact('hubs'));
    }

    public function signUp(Request $request)
    {

        $hubs       = $this->repo->all_hubs();
        return view('backend.merchant.sign_up',compact('hubs','request'));
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequest $request)
    {

        if($this->repo->store($request)){ 
            Toastr::success(__('merchant.added_msg'),__('message.success'));
            return redirect()->route('merchant.index');
        }else{
            Toastr::error(__('merchant.error_msg'),__('message.error'));
            return redirect()->back()->withInput($request->all());
        }

    }


    public function signUpStore(SignUpRequest $request)
    {
        if($this->repo->signUpStore($request)){
            return redirect()->route('merchant.otp-verification-form');
        }else{
            Toastr::error(__('merchant.error_msg'),__('message.error'));
            return redirect()->back()->withInput($request->all());
        }
    }

    // Public KYC application form (no auth required)
    public function apply(Request $request)
    {
        $this->setApplyLocale($request);
        return view('backend.merchant.apply');
    }

    private function setApplyLocale(Request $request): void
    {
        $lang = $request->query('lang');
        if (in_array($lang, ['ar','en'], true)) {
            app()->setLocale($lang);
            session()->put('locale', $lang);
        }
    }

    public function applyStore(Request $request)
    {
        $validated = $request->validate([
            'business_name' => ['required','string','max:191'],
            'name'          => ['required','string','max:191'],
            'mobile'        => ['required','string','max:32'],
            'email'         => ['nullable','email','max:191'],
            'address'       => ['required','string','max:500'],
        ]);

        $merchantId = $this->repo->applyStore($request);
        if ($merchantId) {
            return redirect()->route('merchant.apply.success')->with('merchant_application_id', $merchantId);
        }
        Toastr::error(__('merchant.error_msg'), __('message.error'));
        return redirect()->back()->withInput($request->all());
    }

    public function applySuccess(Request $request)
    {
        $this->setApplyLocale($request);
        $merchantId = session('merchant_application_id');
        return view('backend.merchant.apply-success', compact('merchantId'));
    }


    public function otpVerification(OtpRequest $request)
    {
        $result     = $this->repo->otpVerification($request);
        if($result != null){
            if(auth()->attempt([
                                'mobile' => $result->mobile,
                                'password' => session('password')
                            ]))
            {
                return redirect()->route('login');
            }
        }
        elseif($result == 0){
            return redirect()->route('merchant.otp-verification-form')->with('warning', 'Invalid OTP');
        }
        else{
            Toastr::error(__('merchant.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    public function otpVerificationForm()
    {
        return view('backend.merchant.verification');
    }

    public function resendOTP(Request $request)
    {
        $this->repo->resendOTP($request);
        return redirect()->route('merchant.otp-verification-form')->with('success', 'Resend OTP');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function view($id)
    {
        $singleMerchant = $this->repo->get($id);
        $merchant_shops =$this->repo->merchant_shops_get($id);
        if(blank($singleMerchant)){
            abort(404);
        }
        return view('backend.merchant.merchant-details',compact('singleMerchant','merchant_shops'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $hubs     = $this->repo->all_hubs();
        $merchant = $this->repo->get($id);
        if(blank($merchant)){
            abort(404);
        }
        // Eager-load for the Geography block so coverageSummary() and the
        // pre-selected options don't trigger separate lookups in the view.
        $merchant->load(['countries:id,name,en_name,code', 'cities:id,country_id,name,en_name']);
        $countries = \App\Models\Backend\Country::where('is_active', true)
            ->orderBy('sorting')->orderBy('name')->get(['id', 'name', 'en_name', 'code']);
        $cities = \App\Models\Backend\City::where('is_active', 1)
            ->orderBy('sorting')->orderBy('name')->get(['id', 'country_id', 'name', 'en_name']);
        return view('backend.merchant.edit', compact('merchant', 'hubs', 'countries', 'cities'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update($id, UpdateRequest $request)
    {

        if($this->repo->update($id,$request)){
            Toastr::success(__('merchant.update_msg'),__('message.success'));
            return redirect()->route('merchant.index');
        }else{
            Toastr::error(__('merchant.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if($this->repo->delete($id)){
            Toastr::success(__('merchant.delete_msg'),__('message.success'));
            return back();
        }else{
            Toastr::error(__('merchant.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }


    public function invoiceGenerate($id){
        $this->invoiceRepo->store($id);
        Toastr::success('Invoice generated successfully','Success');
        return redirect()->back();
    }

    /**
     * Sign in as the merchant's user — for support / debugging. The original admin
     * id is stashed in the session so we can switch back via stopImpersonate(). The
     * action is written to the activity log so it's auditable.
     */
    public function impersonate($id, Request $request)
    {
        $merchant = \App\Models\Backend\Merchant::with('user')->find($id);
        if (! $merchant || ! $merchant->user) {
            Toastr::error(__('merchant.error_msg'), __('message.error'));
            return redirect()->back();
        }
        $admin = \Auth::user();
        if (! $admin) abort(403);

        // Don't allow nested impersonation — must stop the current one first.
        if ($request->session()->has('impersonator_id')) {
            Toastr::error('Already impersonating. Stop the current session first.', __('message.error'));
            return redirect()->back();
        }
        if ($admin->id === $merchant->user->id) {
            Toastr::error("Can't impersonate yourself.", __('message.error'));
            return redirect()->back();
        }

        // Audit trail — spatie/activitylog is already used elsewhere in the project.
        try {
            activity('impersonation')
                ->causedBy($admin)
                ->performedOn($merchant)
                ->withProperties([
                    'admin_id'    => $admin->id,
                    'admin_email' => $admin->email,
                    'merchant_id' => $merchant->id,
                    'target_user' => $merchant->user->email,
                    'ip'          => $request->ip(),
                ])
                ->log('Started impersonation');
        } catch (\Throwable $e) { /* activity log not critical */ }

        $request->session()->put('impersonator_id', $admin->id);
        \Auth::login($merchant->user);

        return redirect()->route('dashboard.index');
    }

    /**
     * Restore the admin session captured by impersonate().
     */
    public function stopImpersonate(Request $request)
    {
        $adminId = $request->session()->pull('impersonator_id');
        if (! $adminId) {
            return redirect()->route('dashboard.index');
        }
        $admin = \App\Models\User::find($adminId);
        if (! $admin) {
            \Auth::logout();
            return redirect()->route('login');
        }

        try {
            activity('impersonation')
                ->causedBy($admin)
                ->withProperties(['admin_id' => $admin->id, 'restored_at' => now()->toIso8601String()])
                ->log('Stopped impersonation');
        } catch (\Throwable $e) { /* ignore */ }

        \Auth::login($admin);
        return redirect()->route('merchant.index');
    }


}
