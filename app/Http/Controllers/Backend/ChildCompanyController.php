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
                'title'    => __('menus.child_companies') ?: 'Sub-accounts',
                'subtitle' => 'Companies you have created under your account.',
                'create'   => __('levels.add_new') ?: 'Add new',
                'name'     => __('levels.name') ?: 'Name',
                'email'    => __('levels.email') ?: 'Email',
                'phone'    => __('levels.phone') ?: 'Phone',
                'status'   => __('levels.status') ?: 'Status',
                'created'  => __('levels.created_at') ?: 'Created',
                'empty'    => __('levels.no_data_found') ?: 'No sub-accounts yet.',
            ],
        ]);
    }

    public function create()
    {
        $plans      = Plan::where('status', \App\Enums\Status::ACTIVE)->orderBy('position')->get(['id', 'name', 'user_count', 'deliveryman_count', 'parcel_count', 'days_count', 'price']);
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
                'title'        => __('menus.child_companies_new') ?: 'New sub-account',
                'company_name' => __('levels.company_name') ?: 'Company name',
                'domain'       => __('levels.domain') ?: 'Subdomain',
                'currency'     => __('levels.currency') ?: 'Currency',
                'plan'         => __('levels.plan') ?: 'Plan',
                'address'      => __('levels.address') ?: 'Address',
                'owner'        => __('levels.owner_details') ?: 'Owner login',
                'name'         => __('levels.name') ?: 'Full name',
                'email'        => __('levels.email') ?: 'Email',
                'password'     => __('levels.password') ?: 'Password',
                'mobile'       => __('levels.mobile') ?: 'Mobile',
                'submit'       => __('levels.submit') ?: 'Create sub-account',
                'cancel'       => __('levels.cancel') ?: 'Cancel',
            ],
        ]);
    }

    public function store(StoreRequest $request)
    {
        $parentCompanyId = settings()->id;

        if ($this->repo->store($request, $parentCompanyId)) {
            Toastr::success('Sub-account created successfully.', __('message.success'));
            return redirect()->route('child-companies.index');
        }

        Toastr::error('Something went wrong.', __('message.error'));
        return redirect()->back()->withInput();
    }
}
