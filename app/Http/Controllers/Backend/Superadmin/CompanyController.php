<?php

namespace App\Http\Controllers\Backend\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\SignUpRequest;
use App\Http\Requests\Company\StoreRequest;
use App\Http\Requests\Company\UpdateRequest;
use App\Http\Requests\Merchant\OtpRequest;
use App\Models\Backend\Superadmin\Plan;
use App\Models\User;
use App\Repositories\Currency\CurrencyInterface;
use App\Repositories\Superadmin\Company\CompanyInterface;
use App\Repositories\Superadmin\Plan\PlanInterface;
use App\Repositories\User\UserInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CompanyController extends Controller
{
    protected $repo,
        $userRepo,
        $currencyRepo,
        $planRepo;
    public function __construct(
        CompanyInterface  $repo,
        UserInterface     $userRepo,
        CurrencyInterface $currencyRepo,
        PlanInterface     $planRepo
    ) {
        $this->repo         = $repo;
        $this->userRepo     = $userRepo;
        $this->currencyRepo = $currencyRepo;
        $this->planRepo     = $planRepo;
    }

    public function index()
    {
        // Paginator of company-owner Users (user_type=ADMIN, company_owner=YES).
        $companies = $this->repo->get();

        // Flatten each row for React consumption. Legacy Blade relied on
        // magic property chains (`$c->company->plan->modules`) that produce
        // ugly output when relations are missing; here we normalize them all
        // up front so the JSX only deals with primitives.
        $rows = collect($companies->items())->map(function ($u) {
            $tenant  = optional($u->tenantDetails);
            $domains = $tenant ? collect($tenant->domains ?? [])->map(fn ($d) => [
                'id'    => $d->id ?? null,
                'name'  => $d->domain,
                // scheme_name($x) returns the full "https://x" URL — do NOT
                // re-append $d->domain or the host doubles up.
                'url'   => scheme_name($d->domain),
            ])->values() : collect();

            $general = optional($u->company); // GeneralSettings row
            $plan    = optional($general?->plan);

            $days = subscriptionCheck($u);

            return [
                'id'            => $u->id,
                'name'          => $u->name,
                'email'         => $u->email,
                'mobile'        => $u->mobile,
                'avatar'        => $u->image,
                'status_html'   => $u->my_status,
                'company'       => [
                    'id'    => $general?->id,
                    'name'  => $general?->name,
                    'logo'  => $general?->LogoImage,
                ],
                'plan'          => $plan?->id ? [
                    'id'          => $plan->id,
                    'name'        => $plan->name,
                    'module_count'=> is_array($plan->modules) ? count($plan->modules) : 0,
                ] : null,
                'subscription'  => [
                    'active'         => $days !== false,
                    'remaining_days' => $days === false ? null : (int) $days,
                ],
                'domains'       => $domains,
                'urls'          => [
                    'edit'      => route('company.edit', $u->id),
                    'delete'    => route('company.delete', $u->company_id ?? $u->id),
                    'subscribe' => route('company.subscription.switch', $u->id),
                ],
            ];
        })->values();

        return Inertia::render('Admin/Superadmin/Company/Index', [
            'rows'        => $rows,
            'pagination'  => [
                'current_page' => $companies->currentPage(),
                'per_page'     => $companies->perPage(),
                'total'        => $companies->total(),
                'from'         => $companies->firstItem(),
                'to'           => $companies->lastItem(),
                'last_page'    => $companies->lastPage(),
                'links'        => collect($companies->linkCollection())->map(fn ($l) => [
                    'url'    => $l['url'],
                    'label'  => $l['label'],
                    'active' => (bool) $l['active'],
                ])->values(),
            ],
            'permissions' => [
                'create'    => hasPermission('company_create'),
                'update'    => hasPermission('company_update'),
                'delete'    => hasPermission('company_delete'),
                'subscribe' => hasPermission('company_subscribe'),
            ],
            'urls'        => [
                'create'    => route('company.create'),
                'dashboard' => route('dashboard.index'),
            ],
            't'           => [
                'title'         => __('menus.company') ?: 'Companies',
                'breadcrumb'    => __('levels.dashboard'),
                'count_suffix'  => __('Showing') ?: 'total',
                'add'           => __('levels.add'),
                'name'          => __('levels.name'),
                'domain'        => __('levels.domain'),
                'owner'         => __('levels.user_details') ?: 'Owner',
                'plan'          => __('levels.plan'),
                'subscription'  => __('levels.subscription'),
                'status'        => __('levels.status'),
                'actions'       => __('levels.actions'),
                'modules'       => __('levels.modules'),
                'edit'          => __('levels.edit'),
                'delete'        => __('levels.delete'),
                'subscribe_now' => __('Subscribe Now'),
                'remaining'     => __('levels.remaining'),
                'days'          => __('levels.days'),
                'expired'       => __('levels.expired'),
                'no_data'       => __('levels.no_data_found'),
                'confirm_delete'=> __('delete.company') ?: 'Delete this company?',
            ],
        ]);
    }

    public function create()
    {
        $departments  = $this->userRepo->departments();
        $designations = $this->userRepo->designations();
        $currencies   = $this->currencyRepo->getActive();
        $plans        = $this->planRepo->getActive();
        return view('backend.super-admin.company.create', compact('designations', 'departments', 'currencies', 'plans'));
    }

    public function store(StoreRequest $request)
    {
        if ($this->repo->store($request)) {
            Toastr::success('Company successfully added.', __('message.success'));
            return redirect()->route('company.index');
        } else {
            Toastr::error('Something went wrong.', __('message.error'));
            return redirect()->back();
        }
    }

    public function edit($id)
    {
       
        $departments  = $this->userRepo->departments();
        $designations = $this->userRepo->designations();
        $currencies   = $this->currencyRepo->getActive();
        $plans        = $this->planRepo->getActive();
        $company      = $this->repo->getFind($id);
        
          
        return view('backend.super-admin.company.edit', compact('designations', 'departments', 'currencies', 'plans', 'company'));
    }

    public function update(UpdateRequest $request)
    {
        if ($this->repo->update($request->id, $request)) {
            Toastr::success('Company successfully updated.', __('message.success'));
            return redirect()->route('company.index');
        } else {
            Toastr::error('Something went wrong.', __('message.error'));
            return redirect()->back();
        }
    }

    public function delete($id)
    {
        if(env('DEMO')):
            Toastr::error('Delete system is disable for the demo mode.',__('message.error'));
            return redirect()->back();
        endif;
        if ($this->repo->delete($id)) {
            Toastr::success('Company successfully deleted.', __('message.success'));
            return redirect()->route('company.index');
        } else {
            Toastr::error('Something went wrong.', __('message.error'));
            return redirect()->back();
        }
    }

    public function switchSubscription($id)
    {
        $user_id      = $id;
        $user         = User::find($id);
        $plan         = Plan::find($user->company->plan_id);
        $plans        = $this->planRepo->getActive();
        return view('backend.super-admin.company.switch_subscription', compact('user_id', 'plans', 'plan'));
    }

    public function switchSubscriptionStore(Request $request)
    {
        if ($this->repo->switchPlan($request)) {
            Toastr::success('Subscribed successfully.', __('message.success'));
            return redirect()->route('company.index');
        } else {
            Toastr::error('Something went wrong.', __('message.error'));
            return redirect()->back();
        }
    }



    public function signUp(Request $request)
    {
        return view('backend.super-admin.company.company_signup', compact('request'));
    }

    public function signUpStore(SignUpRequest $request)
    {
        if ($this->repo->signUpStore($request)) {
            return redirect()->route('company.otp-verification-form');
        } else {
            Toastr::error('Something went wrong.', __('message.error'));
            return redirect()->back();
        }
    }


    public function otpVerificationForm()
    {
        return view('backend.super-admin.company.verification');
    }

    public function resendOTP(Request $request)
    {
        $this->repo->resendOTP($request);
        return redirect()->route('company.otp-verification-form')->with('success', 'Resend OTP');
    }

 
    public function otpVerification(OtpRequest $request)
    {
        $result     = $this->repo->otpVerification($request);
        if ($result != null) {
            Toastr::success('Successfully verified.', __('message.error')); 
            return redirect()->route('login'); 
        } elseif ($result == 0) {
            return redirect()->route('company.otp-verification-form')->with('warning', 'Invalid OTP');
        } else {
            Toastr::error(__('merchant.error_msg'), __('message.error'));
            return redirect()->back();
        }
    }

    
}
