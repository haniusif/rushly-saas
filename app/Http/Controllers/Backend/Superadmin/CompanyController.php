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
        return Inertia::render('Admin/Superadmin/Company/Form', $this->formProps(null));
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
        $company = $this->repo->getFind($id);
        if (! $company) abort(404);
        return Inertia::render('Admin/Superadmin/Company/Form', $this->formProps($company));
    }

    /**
     * Shared props for the tenant create + edit Inertia form.
     * $user=null → fresh create (all fields empty, joining_date=today).
     * $user set  → seed from the company-owner User row + its tenant + owned GeneralSettings.
     */
    private function formProps($user): array
    {
        $isEdit       = $user !== null;
        $general      = $isEdit ? optional($user->company) : null;
        $tenant       = $isEdit ? optional($user->tenantDetails) : null;
        $firstDomain  = $isEdit && $tenant ? optional(collect($tenant->domains ?? [])->first()) : null;

        return [
            'mode'   => $isEdit ? 'edit' : 'create',
            'user'   => [
                'id'                 => $isEdit ? $user->id : null,
                'company_name'       => $isEdit ? (string) ($general?->name ?? '')             : '',
                'domain'             => $isEdit ? (string) ($firstDomain?->domain_name ?? '')  : '',
                'domain_id'          => $isEdit ? ($firstDomain?->id ?? null)                  : null,
                'currency'           => $isEdit ? (string) ($general?->currency ?? '')         : '',
                'plan_id'            => $isEdit ? (string) ($general?->plan_id ?? '')          : '',
                'par_track_prefix'   => $isEdit ? (string) ($general?->par_track_prefix ?? '') : '',
                'invoice_prefix'     => $isEdit ? (string) ($general?->invoice_prefix ?? '')   : '',
                'name'               => $isEdit ? (string) $user->name  : '',
                'email'              => $isEdit ? (string) $user->email : '',
                'mobile'             => $isEdit ? (string) $user->mobile: '',
                'address'            => $isEdit ? (string) $user->address : '',
                'nid_number'         => $isEdit ? (string) $user->nid_number : '',
                'designation_id'     => $isEdit ? (string) ($user->designation_id ?? '') : '',
                'department_id'      => $isEdit ? (string) ($user->department_id ?? '')  : '',
                'joining_date'       => $isEdit ? (string) $user->joining_date : date('Y-m-d'),
                'status'             => $isEdit ? (string) $user->status : (string) \App\Enums\Status::ACTIVE,
            ],
            'lookups' => [
                'currencies'   => collect($this->currencyRepo->getActive())->map(fn ($c) => [
                    'value' => $c->symbol,
                    'label' => $c->name.' '.$c->symbol,
                ])->values(),
                'plans'        => collect($this->planRepo->getActive())->map(fn ($p) => [
                    'value' => (string) $p->id,
                    'label' => $p->name,
                ])->values(),
                'designations' => collect($this->userRepo->designations())->map(fn ($d) => [
                    'value' => (string) $d->id,
                    'label' => $d->title,
                ])->values(),
                'departments'  => collect($this->userRepo->departments())->map(fn ($d) => [
                    'value' => (string) $d->id,
                    'label' => $d->title,
                ])->values(),
                'statuses'     => collect(trans('status'))->map(fn ($label, $key) => [
                    'value' => (string) $key,
                    'label' => $label,
                ])->values(),
            ],
            'assets' => [
                'logo_url'   => $isEdit ? ($general?->LogoImage ?: null) : null,
                'avatar_url' => $isEdit ? ($user->image ?: null) : null,
            ],
            'domain_suffix' => '.'.get_host(),
            'urls'   => [
                'submit' => $isEdit ? route('company.update') : route('company.store'),
                'index'  => route('company.index'),
            ],
            't'      => [
                'title'          => $isEdit ? __('levels.edit').' '.__('levels.company') : __('levels.create').' '.__('levels.company'),
                'breadcrumb'     => __('levels.dashboard'),
                'company_list'   => __('menus.company'),
                'save'           => __('levels.save'),
                'cancel'         => __('levels.cancel'),
                'company_info'   => __('levels.company').' '.__('levels.information'),
                'company_hint'   => 'Basic identity, domain, and plan.',
                'user_info'      => __('levels.user').' '.__('levels.information'),
                'user_hint'      => 'Primary admin user for this tenant.',
                'company_name'   => __('levels.company').' '.__('levels.name'),
                'domain'         => __('levels.domain'),
                'currency'       => __('levels.currency'),
                'plan'           => __('levels.plan'),
                'par_track_prefix' => __('settings.parcel_tracking').' '.__('levels.prefix'),
                'invoice_prefix' => __('invoice.invoice').' '.__('levels.prefix'),
                'logo'           => __('levels.logo'),
                'name'           => __('levels.name'),
                'email'          => __('levels.email'),
                'phone'          => __('levels.phone'),
                'password'       => __('levels.password'),
                'password_edit_hint' => __('Leave empty to keep current'),
                'address'        => __('levels.address'),
                'nid'            => __('levels.nid'),
                'designation'    => __('levels.designation'),
                'department'     => __('levels.department'),
                'opening_date'   => __('levels.opening_date'),
                'status'         => __('levels.status'),
                'image'          => __('levels.image'),
                'select_currency'=> 'Select currency',
            ],
        ];
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
        $user        = User::find($id);
        if (! $user) abort(404);
        $currentPlan = Plan::find(optional($user->company)->plan_id);
        $plans       = $this->planRepo->getActive();

        return Inertia::render('Admin/Superadmin/Company/SwitchSubscription', [
            'user_id'      => (int) $id,
            'company_name' => optional($user->company)->name ?? $user->name,
            'current_plan' => $currentPlan ? [
                'id'   => $currentPlan->id,
                'name' => $currentPlan->name,
            ] : null,
            'plans'        => collect($plans)->map(fn ($p) => [
                'value' => (string) $p->id,
                'label' => $p->name,
                'price' => (float) $p->price,
                'days'  => (int)   $p->days_count,
            ])->values(),
            'currency'     => settings()->currency ?: '$',
            'urls'         => [
                'submit' => route('company.subscription.switch.store'),
                'index'  => route('company.index'),
            ],
            't'            => [
                'title'        => __('levels.subscription'),
                'breadcrumb'   => __('levels.dashboard'),
                'company_list' => __('menus.company'),
                'current_plan' => __('levels.current_plan'),
                'plan'         => __('levels.plan'),
                'save'         => __('levels.save'),
                'cancel'       => __('levels.cancel'),
                'switching_for'=> 'Changing subscription for',
            ],
        ]);
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
