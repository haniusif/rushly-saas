<?php

namespace App\Http\Controllers\Backend\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\SignUpRequest;
use App\Http\Requests\Company\StoreRequest;
use App\Http\Requests\Company\UpdateRequest;
use App\Http\Requests\Merchant\OtpRequest;
use App\Enums\UserType;
use App\Models\Backend\Superadmin\Plan;
use App\Models\User;
use App\Repositories\Currency\CurrencyInterface;
use App\Repositories\Superadmin\Company\CompanyInterface;
use App\Repositories\Superadmin\Plan\PlanInterface;
use App\Repositories\User\UserInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
                    'edit'        => route('company.edit', $u->id),
                    'delete'      => route('company.delete', $u->company_id ?? $u->id),
                    'subscribe'   => route('company.subscription.switch', $u->id),
                    'impersonate' => route('company.impersonate', $u->id),
                ],
            ];
        })->values();

        // "Login as company" is super-admin only (no granular permission).
        $canImpersonate = (int) optional(\Auth::user())->user_type === UserType::SUPER_ADMIN;

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
                'create'      => hasPermission('company_create'),
                'update'      => hasPermission('company_update'),
                'delete'      => hasPermission('company_delete'),
                'subscribe'   => hasPermission('company_subscribe'),
                'impersonate' => $canImpersonate,
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
                'login_as'      => __('company.login_as') ?: 'Login as company',
                'impersonate_confirm' => __('company.impersonate_confirm') ?: 'Log in as this company owner? You can return to your admin session afterwards.',
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

    /**
     * Start a "login as company" session.
     *
     * The super-admin runs on the CENTRAL host, but tenancy is identified by
     * DOMAIN (InitializeTenancyByDomain) and the tenant dashboard + stop routes
     * are only registered on the tenant's own subdomain. So we can't just swap
     * the auth user here — we hand off across hosts:
     *
     *   1. (here, central) mint a single-use token in the cache and redirect the
     *      browser to the owner's tenant subdomain /impersonate/consume/{token}.
     *   2. (consume, tenant host) validate the token, log the owner in INSIDE
     *      their tenant context, and land on their real dashboard.
     *
     * This method is reached by a native form POST (see Company/Index.jsx), so a
     * plain cross-host redirect is followed by the browser directly.
     *
     * Hard-gated to SUPER_ADMIN.
     *
     * @param int $id  The company-owner User id (the id used on the index rows).
     */
    public function impersonate($id, Request $request)
    {
        $admin = \Auth::user();
        if (! $admin) {
            abort(403);
        }

        // Super-admin only — no granular permission for this action.
        if ((int) $admin->user_type !== UserType::SUPER_ADMIN) {
            abort(403);
        }

        // Target must be a company-owner admin user.
        $owner = User::where('id', $id)
            ->where('user_type', UserType::ADMIN)
            ->first();

        if (! $owner) {
            Toastr::error(__('merchant.error_msg'), __('message.error'));
            return redirect()->back();
        }

        if ($admin->id === $owner->id) {
            Toastr::error("Can't impersonate yourself.", __('message.error'));
            return redirect()->back();
        }

        // Resolve the owner's tenant subdomain — we have to land them there,
        // since that's where their tenant context and dashboard live.
        $domain = optional(optional(optional($owner->tenantDetails)->domains)->first())->domain;
        if (! $domain) {
            Toastr::error(__('company.no_domain') ?: 'This company has no domain to log in to.', __('message.error'));
            return redirect()->back();
        }

        // Single-use, 60s handoff token stored in the shared DB. Cache can't be
        // used here: the tenancy cache bootstrapper re-scopes (and tags) the
        // cache per tenant, so a key written on the central host is invisible
        // on the tenant host. The DB is NOT swapped per tenant (no database
        // tenancy bootstrapper), so this row is readable from both hosts.
        $token = Str::random(64);
        DB::table('impersonation_tokens')->insert([
            'token'           => $token,
            'user_id'         => $owner->id,
            'impersonator_id' => $admin->id,
            'company_id'      => $owner->company_id,
            'expires_at'      => now()->addSeconds(60),
            'created_at'      => now(),
        ]);

        // Audit trail — spatie/activitylog, as used by merchant impersonation.
        try {
            activity('impersonation')
                ->causedBy($admin)
                ->performedOn($owner)
                ->withProperties([
                    'admin_id'    => $admin->id,
                    'admin_email' => $admin->email,
                    'company_id'  => $owner->company_id,
                    'target_user' => $owner->email,
                    'domain'      => $domain,
                    'ip'          => $request->ip(),
                ])
                ->log('Started company impersonation');
        } catch (\Throwable $e) { /* activity log not critical */ }

        $url = rtrim(scheme_name($domain), '/') . '/impersonate/consume/' . $token;
        return redirect()->away($url);
    }

    /**
     * Consume a handoff token on the TENANT subdomain and log the owner in.
     * Runs inside the tenant route group (InitializeTenancyByDomain), guest-
     * accessible — logging in is the whole point.
     */
    public function consume($token, Request $request)
    {
        // Single-use: read then immediately delete the row. Also sweep expired
        // tokens so the table can't grow unbounded.
        $row = DB::table('impersonation_tokens')->where('token', $token)->first();
        DB::table('impersonation_tokens')->where('token', $token)->delete();
        DB::table('impersonation_tokens')->where('expires_at', '<', now())->delete();

        if (! $row || now()->greaterThan($row->expires_at)) {
            Toastr::error(__('company.impersonate_expired') ?: 'This login link has expired. Please try again.', __('message.error'));
            return redirect()->route('login');
        }

        $owner = User::where('id', $row->user_id)
            ->where('user_type', UserType::ADMIN)
            ->first();
        if (! $owner) {
            Toastr::error(__('merchant.error_msg'), __('message.error'));
            return redirect()->route('login');
        }

        // The token must be consumed on the owner's OWN tenant subdomain.
        $tenant = function_exists('tenant') ? tenant() : null;
        if ($tenant && (string) $tenant->company_id !== (string) $owner->company_id) {
            abort(403);
        }

        $request->session()->put('impersonator_id', $row->impersonator_id);
        \Auth::login($owner);

        try {
            activity('impersonation')
                ->causedBy(User::find($row->impersonator_id))
                ->performedOn($owner)
                ->withProperties(['company_id' => $owner->company_id, 'target_user' => $owner->email, 'ip' => $request->ip()])
                ->log('Entered company impersonation');
        } catch (\Throwable $e) { /* ignore */ }

        return redirect()->route('dashboard.index');
    }

    /**
     * End a "login as company" session (runs on the tenant subdomain, reached by
     * a native form POST from the impersonation banner). Destroys the owner
     * session here and sends the super-admin back to the central companies page —
     * their original central session was never touched, so they land logged in.
     */
    public function stopImpersonate(Request $request)
    {
        $adminId = $request->session()->pull('impersonator_id');

        \Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($adminId) {
            try {
                activity('impersonation')
                    ->causedBy(User::find($adminId))
                    ->withProperties(['admin_id' => $adminId, 'restored_at' => now()->toIso8601String()])
                    ->log('Stopped company impersonation');
            } catch (\Throwable $e) { /* ignore */ }
        }

        // Absolute central URL — route('company.index') isn't registered on the
        // tenant host this runs on.
        $central = rtrim(config('app.url'), '/') . '/super-admin/company';
        return redirect()->away($central);
    }

}
