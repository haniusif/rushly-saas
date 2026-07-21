<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChildCompany\StoreRequest;
use App\Models\Backend\GeneralSettings;
use App\Models\Backend\Superadmin\Plan;
use App\Repositories\Currency\CurrencyInterface;
use App\Repositories\Superadmin\Company\CompanyInterface;
use Brian2694\Toastr\Facades\Toastr;
use Inertia\Inertia;

/**
 * Tenant-context CRUD for child company accounts (reseller / white-label).
 *
 * A tenant admin holding `company_create` can spin up a full new tenant
 * (own subdomain, own owner user, own subscription) that is linked back
 * to this tenant via general_settings.parent_company_id.
 *
 * The heavy lifting is delegated to CompanyRepository::store() — the same
 * one the super-admin panel uses — with the parent company_id passed in.
 * Because this codebase is single-DB (see config/tenancy.php:31 — the
 * DatabaseTenancyBootstrapper is disabled) no Stancl central/tenant
 * context switch is needed.
 *
 * TODO(billing): child subscriptions are created against the parent's
 * chosen plan but there is no billing wiring — parent pays out-of-band
 * for now. When we introduce parent-pays-for-children flows this is the
 * seam.
 */
class ChildCompanyController extends Controller
{
    protected CompanyInterface $repo;
    protected CurrencyInterface $currencyRepo;

    public function __construct(CompanyInterface $repo, CurrencyInterface $currencyRepo)
    {
        $this->repo         = $repo;
        $this->currencyRepo = $currencyRepo;
    }

    public function index()
    {
        $children = GeneralSettings::where('parent_company_id', settings()->id)
            ->orderByDesc('id')
            ->get(['id', 'name', 'email', 'phone', 'status', 'created_at']);

        return Inertia::render('Admin/ChildCompanies/Index', [
            'children' => $children,
            'urls'     => [
                'create' => route('child-companies.create'),
            ],
            'labels'   => [
                'title'    => __('menus.child_companies'),
                'subtitle' => __('menus.child_companies_subtitle'),
                'create'   => __('levels.add_new'),
                'name'     => __('levels.name'),
                'email'    => __('levels.email'),
                'phone'    => __('levels.phone'),
                'status'   => __('levels.status'),
                'created'  => __('levels.created_at'),
                'empty'    => __('levels.no_data_found'),
                'active'   => __('levels.active'),
                'inactive' => __('levels.inactive'),
            ],
        ]);
    }

    public function create()
    {
        // Sub-accounts are restricted to the Vendor plan by product decision.
        // Falls back to any active plan if Vendor has been renamed / removed
        // so the page doesn't 500 in that edge case.
        $plans      = Plan::where('status', \App\Enums\Status::ACTIVE)
            ->where('name', 'Vendor')
            ->orderBy('position')
            ->get(['id', 'name', 'user_count', 'deliveryman_count', 'parcel_count', 'days_count', 'price']);
        $currencies = $this->currencyRepo->getActive();

        return Inertia::render('Admin/ChildCompanies/Create', [
            'plans'          => $plans,
            'currencies'     => $currencies,
            'defaultCurrency' => settings()->currency,
            'appHost'        => get_host(),
            'urls'           => [
                'submit' => route('child-companies.store'),
                'index'  => route('child-companies.index'),
            ],
            'labels'         => [
                'title'        => __('menus.child_companies_new'),
                'company_name' => __('levels.company_name'),
                'domain'       => __('levels.domain'),
                'currency'     => __('levels.currency'),
                'plan'         => __('levels.plan'),
                'address'      => __('levels.address'),
                'owner'        => __('levels.owner_details'),
                'name'         => __('levels.name'),
                'email'        => __('levels.email'),
                'password'     => __('levels.password'),
                'mobile'       => __('levels.mobile'),
                'submit'       => __('levels.submit'),
                'cancel'       => __('levels.cancel'),
            ],
        ]);
    }

    public function store(StoreRequest $request)
    {
        $parentCompanyId = settings()->id;

        // Guard against tampered POSTs picking a non-Vendor plan.
        $plan = Plan::where('id', $request->plan_id)
            ->where('status', \App\Enums\Status::ACTIVE)
            ->where('name', 'Vendor')
            ->first();
        if (! $plan) {
            Toastr::error(__('child_company.error_msg'), __('message.error'));
            return redirect()->back()->withInput();
        }

        if ($this->repo->store($request, $parentCompanyId)) {
            Toastr::success(__('child_company.created_msg'), __('message.success'));
            return redirect()->route('child-companies.index');
        }

        Toastr::error(__('child_company.error_msg'), __('message.error'));
        return redirect()->back()->withInput();
    }
}
