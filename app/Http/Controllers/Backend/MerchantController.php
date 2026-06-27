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
use Inertia\Inertia;
 
class MerchantController extends Controller
{
  
    protected $repo,$invoiceRepo;
    public function __construct(MerchantInterface $repo, InvoiceInterface $invoiceRepo)
    {
        $this->repo        = $repo;
        $this->invoiceRepo = $invoiceRepo;
    }

    public function index(Request $request)
    {
        $paginator = $this->repo->all();
        return $this->renderMerchantIndex($paginator, $request);
    }

    private function renderMerchantIndex($paginator, Request $request)
    {
        $rows = collect($paginator->items())->map(function ($m) {
            $countries = collect($m->countries ?? []);
            $cityCount = $m->cities?->count() ?? 0;
            return [
                'id'              => $m->id,
                'unique_id'       => optional($m->user)->unique_id,
                'name'            => optional($m->user)->name,
                'email'           => optional($m->user)->email,
                'image'           => optional($m->user)->image,
                'mobile'          => optional($m->user)->mobile,
                'business_name'   => $m->business_name,
                'hub_name'        => optional(optional($m->user)->hub)->name,
                'countries'       => $countries->take(3)->map(fn ($c) => [
                    'code' => $c->code,
                    'name' => $c->en_name ?: $c->name,
                ])->values(),
                'countries_more'  => max(0, $countries->count() - 3),
                'covers_all_cities' => (bool) $m->covers_all_cities,
                'city_count'      => $cityCount,
                'services'        => is_array($m->services) ? $m->services : [],
                'status'          => (int) optional($m->user)->status,
                'wallet_active'   => (int) $m->wallet_use_activation === 1,
                'current_balance' => (float) ($m->current_balance ?? 0),
                'computed_balance'=> (float) ($m->computed_balance ?? 0),
                'urls' => [
                    'view'        => route('merchant.view', $m->id),
                    'edit'        => route('merchant.edit', $m->id),
                    'invoice'     => route('merchant.invoice.generate', $m->id),
                    'impersonate' => route('merchant.impersonate', $m->id),
                ],
                'impersonate_name'=> $m->business_name ?: (optional($m->user)->name ?: 'merchant'),
            ];
        })->values();

        return Inertia::render('Admin/Merchant/Index', [
            'rows'        => $rows,
            'pagination'  => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
                'total'        => $paginator->total(),
                'prev_url'     => $paginator->previousPageUrl(),
                'next_url'     => $paginator->nextPageUrl(),
            ],
            'permissions' => [
                'create' => hasPermission('merchant_create'),
                'view'   => hasPermission('merchant_view'),
                'update' => hasPermission('merchant_update'),
                'delete' => hasPermission('merchant_delete'),
            ],
            'currency' => settings()->currency,
            'urls' => [
                'index'  => route('merchant.index'),
                'create' => route('merchant.create'),
                'apply'  => route('merchant.apply'),
            ],
            't' => [
                'title'            => __('merchant.index_title'),
                'list'             => __('levels.list') ?: 'List',
                'add'              => __('levels.add') ?: 'Add',
                'edit'             => __('levels.edit') ?: 'Edit',
                'view'             => __('levels.view') ?: 'View',
                'delete'           => __('levels.delete') ?: 'Delete',
                'actions'          => __('levels.actions') ?: 'Actions',
                'unique_id'        => __('levels.unique_id') ?: 'ID',
                'business_name'    => __('levels.business_name') ?: 'Business',
                'hub'              => __('levels.hub') ?: 'Hub',
                'phone'            => __('levels.phone') ?: 'Phone',
                'status'           => __('levels.status') ?: 'Status',
                'status_active'    => __('status.1') ?: 'Active',
                'status_inactive'  => __('status.0') ?: 'Inactive',
                'wallet_on'        => __('merchant.index_wallet_active'),
                'wallet_off'       => __('merchant.index_wallet_off'),
                'current_balance'  => __('levels.current_balance') ?: 'Balance',
                'computed_balance' => __('merchant.index_computed_balance'),
                'geography'        => __('merchant.geography') ?: 'Coverage',
                'covers_all_cities'=> __('merchant.covers_all_cities') ?: 'All cities',
                'cities_covered'   => __('merchant.cities_covered') ?: 'cities',
                'card_view'        => __('merchant.card_view') ?: 'Card view',
                'list_view'        => __('merchant.list_view') ?: 'List view',
                'copy_apply_link'  => __('merchant.copy_apply_link') ?: 'Copy apply link',
                'copied'           => __('levels.copied') ?: 'Copied',
                'impersonate'      => __('merchant.impersonate') ?: 'View as',
                // String template — placeholder is substituted client-side
                // per row (the row carries impersonate_name).
                'impersonate_confirm' => __('merchant.index_impersonate_confirm'),
                'invoice_generate' => __('merchant.index_invoice_generate'),
                'search'           => __('merchant.index_search_placeholder'),
                'no_rows'          => __('levels.no_data_found') ?: 'No merchants found',
                'showing_results'  => __('merchant.index_showing_results'),
                'services_label'   => __('merchant.index_services'),
                'prev'             => __('merchant.index_prev'),
                'next'             => __('merchant.index_next'),
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $hubs = $this->repo->all_hubs();

        return Inertia::render('Admin/Merchant/Create', [
            'lookups' => $this->merchantFormLookups($hubs),
            'urls' => [
                'submit' => route('merchant.store'),
                'cancel' => route('merchant.index'),
            ],
            't' => $this->merchantFormLabels('create'),
        ]);
    }

    /**
     * Shared lookup arrays for the merchant create/edit Inertia form.
     * `countries` / `cities` are only passed in by the edit method (the create
     * form will refetch them lazily when geography is touched), so we merge
     * them in there directly.
     */
    private function merchantFormLookups(iterable $hubs): array
    {
        return [
            'hubs' => collect($hubs)->map(fn ($h) => ['id' => $h->id, 'name' => $h->name])->values(),
            'statuses' => [
                ['value' => 1, 'label' => __('merchant_form.status_active')],
                ['value' => 0, 'label' => __('merchant_form.status_inactive')],
            ],
            'services' => \App\Models\Backend\Merchant::SERVICE_KEYS,
            'cod_areas' => [
                ['key' => 'inside_city',  'label' => __('merchant_form.cod_inside_city')],
                ['key' => 'sub_city',     'label' => __('merchant_form.cod_sub_city')],
                ['key' => 'outside_city', 'label' => __('merchant_form.cod_outside_city')],
            ],
        ];
    }

    /**
     * Label dictionary for the merchant create/edit form. The 'mode' arg
     * picks the right page title; everything else is shared.
     */
    private function merchantFormLabels(string $mode): array
    {
        $base = trans('merchant_form');
        $base['title']        = $mode === 'edit' ? $base['title_edit'] : $base['title_create'];
        // Service labels keyed by SERVICE_KEYS — the frontend looks these
        // up to translate the chip text (last_mile, fulfillment, storage).
        $base['service_labels'] = [
            'last_mile'   => $base['service_last_mile'],
            'fulfillment' => $base['service_fulfillment'],
            'storage'     => $base['service_storage'],
        ];
        return $base;
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
        $m = $this->repo->get($id);
        if(blank($m)){ abort(404); }
        $m->loadMissing(['user.hub', 'countries', 'cities']);
        $shops = $this->repo->merchant_shops_get($id);

        return Inertia::render('Admin/Merchant/View', [
            'merchant' => [
                'id'               => $m->id,
                'business_name'    => $m->business_name,
                'unique_id'        => $m->merchant_unique_id,
                'opening_balance'  => (float) $m->opening_balance,
                'current_balance'  => (float) $m->current_balance,
                'computed_balance' => (float) ($m->computed_balance ?? 0),
                'vat'              => (float) ($m->vat ?? 0),
                'cod_charges'      => $m->my_cod_charges,
                'payment_period'   => $m->payment_period,
                'covers_all_cities'=> (bool) $m->covers_all_cities,
                'city_count'       => $m->cities?->count() ?? 0,
                'countries'        => collect($m->countries ?? [])->map(fn ($c) => [
                    'code' => $c->code, 'name' => $c->en_name ?: $c->name,
                ])->values(),
                'services'         => is_array($m->services) ? $m->services : [],
                'nid_url'          => $m->nid,
                'trade_url'        => $m->trade,
                'status'           => (int) optional($m->user)->status,
                'user' => [
                    'name'    => optional($m->user)->name,
                    'email'   => optional($m->user)->email,
                    'mobile'  => optional($m->user)->mobile,
                    'image'   => optional($m->user)->image,
                    'hub'     => optional(optional($m->user)->hub)->name,
                    'address' => optional($m->user)->address,
                ],
            ],
            'shops' => collect($shops)->map(fn ($s) => [
                'id'         => $s->id,
                'name'       => $s->name ?? $s->title ?? null,
                'address'    => $s->address ?? null,
                'is_default' => (bool) ($s->default_shop ?? 0),
            ])->values(),
            'currency' => settings()->currency,
            'permissions' => [
                'edit'        => hasPermission('merchant_update'),
                'impersonate' => hasPermission('merchant_update'),
            ],
            'urls' => [
                'index'        => route('merchant.index'),
                'edit'         => route('merchant.edit', $m->id),
                'impersonate'  => route('merchant.impersonate', $m->id),
                'shops'        => route('merchant.shops.index', $m->id),
                'payments'     => route('merchant.paymentaccount.index', $m->id),
                'invoices'     => route('merchant.invoice.index', $m->id),
                'delivery'     => route('merchant.deliveryCharge.index', $m->id),
            ],
            't' => [
                'title'           => __('merchant.view_title'),
                'title_index'     => __('merchant.view_title_index'),
                'business_name'   => __('levels.business_name') ?: 'Business',
                'hub'             => __('levels.hub') ?: 'Hub',
                'unique_id'       => __('levels.unique_id') ?: 'Unique ID',
                'email'           => __('merchant.view_email'),
                'mobile'          => __('merchant.view_mobile'),
                'opening_balance' => __('merchant.opening_balance') ?: 'Opening balance',
                'current_balance' => __('levels.current_balance') ?: 'Current balance',
                'computed_balance'=> __('merchant.view_computed_balance'),
                'vat'             => __('merchant.vat') ?: 'VAT',
                'cod_charges'     => __('merchant.cod_charges') ?: 'COD charges',
                'nid'             => __('levels.nid') ?: 'NID',
                'trade'           => __('levels.trade_license') ?: 'Trade license',
                'address'         => __('levels.address') ?: 'Address',
                'payment_period'  => __('levels.payment_period') ?: 'Payment period',
                'status'          => __('levels.status') ?: 'Status',
                'active'          => __('status.1') ?: 'Active',
                'inactive'        => __('status.0') ?: 'Inactive',
                'edit'            => __('levels.edit') ?: 'Edit',
                'impersonate'     => __('merchant.impersonate') ?: 'Impersonate',
                'impersonate_confirm' => __('merchant.impersonate_confirm', ['name' => $m->business_name]) ?: 'Continue as merchant?',
                'finance'         => __('merchant.view_finance'),
                'manage'          => __('merchant.view_manage'),
                'kyc_documents'   => __('merchant.view_kyc_documents'),
                'shops'           => __('merchant.view_shops'),
                'payments'        => __('merchant.view_payments'),
                'invoices'        => __('merchant.view_invoices'),
                'delivery'        => __('merchant.view_delivery'),
                'coverage'        => __('merchant.view_coverage'),
                'covers_all_cities'=> __('merchant.covers_all_cities') ?: 'All cities',
                'cities_covered'  => __('merchant.cities_covered') ?: 'cities',
                'services'        => __('merchant.view_services'),
                'default'         => __('merchant.view_default'),
                'no_shops'        => __('merchant.view_no_shops'),
                // Translation map for the service chips rendered next to the
                // coverage card. Falls back to the raw enum key in the JSX.
                'service_labels'  => [
                    'last_mile'   => __('merchant.view_service_last_mile'),
                    'fulfillment' => __('merchant.view_service_fulfillment'),
                    'storage'     => __('merchant.view_service_storage'),
                ],
            ],
        ]);
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
        if (blank($merchant)) {
            abort(404);
        }

        // Eager-load relations the form pre-selects from.
        $merchant->load([
            'user',
            'countries:id,name,en_name,code',
            'cities:id,country_id,name,en_name',
        ]);

        $countries = \App\Models\Backend\Country::where('is_active', true)
            ->orderBy('sorting')->orderBy('name')->get(['id', 'name', 'en_name', 'code']);
        $cities    = \App\Models\Backend\City::where('is_active', 1)
            ->orderBy('sorting')->orderBy('name')->get(['id', 'country_id', 'name', 'en_name']);

        $codCharges = (array) ($merchant->cod_charges ?? []);

        return Inertia::render('Admin/Merchant/Create', [
            'mode' => 'edit',
            'merchant' => [
                'id'                    => $merchant->id,
                'name'                  => optional($merchant->user)->name,
                'mobile'                => optional($merchant->user)->mobile,
                'email'                 => optional($merchant->user)->email,
                'business_name'         => $merchant->business_name,
                'address'               => $merchant->address ?? optional($merchant->user)->address,
                'hub'                   => optional($merchant->user)->hub_id ?? $merchant->hub_id,
                'status'                => optional($merchant->user)->status ?? $merchant->status,
                'opening_balance'       => $merchant->opening_balance,
                'vat'                   => $merchant->vat,
                'payment_period'        => $merchant->payment_period,
                'return_charges'        => $merchant->return_charges,
                'wallet_use_activation' => (int) ($merchant->wallet_use_activation ?? 0),
                'reference_name'        => $merchant->reference_name,
                'reference_phone'       => $merchant->reference_phone,
                'cod_charges'           => $codCharges,
                'services'              => (array) ($merchant->services ?? []),
                'covers_all_cities'     => (bool) ($merchant->covers_all_cities ?? false),
                'country_ids'           => $merchant->countries->pluck('id')->values(),
                'city_ids'              => $merchant->cities->pluck('id')->values(),
                'image'                 => optional($merchant->user)->image,
            ],
            'lookups' => $this->merchantFormLookups($hubs) + [
                'countries' => $countries->map(fn ($c) => [
                    'id'   => $c->id,
                    'name' => $c->en_name ?: $c->name,
                    'code' => $c->code,
                ])->values(),
                'cities' => $cities->map(fn ($c) => [
                    'id'         => $c->id,
                    'name'       => $c->en_name ?: $c->name,
                    'country_id' => $c->country_id,
                ])->values(),
            ],
            'urls' => [
                'submit' => route('merchant.update', $merchant->id),
                'cancel' => route('merchant.view', $merchant->id),
            ],
            't' => $this->merchantFormLabels('edit'),
        ]);
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
