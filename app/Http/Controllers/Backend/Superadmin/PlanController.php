<?php

namespace App\Http\Controllers\Backend\Superadmin;

use App\Enums\BooleanStatus;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Plan\StoreRequest;
use App\Models\Backend\GeneralSettings;
use App\Models\Backend\Setting;
use App\Models\Backend\Subscription;
use App\Models\Backend\Superadmin\Plan;
use App\Models\Permission;
use App\Models\User;
use App\Repositories\Role\RoleInterface;
use App\Repositories\Superadmin\Company\CompanyInterface;
use App\Repositories\Superadmin\Plan\PlanInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlanController extends Controller
{
    protected $repo,$roleRepo,$companyRepo;
    public function __construct(PlanInterface $repo,RoleInterface $roleRepo,CompanyInterface $companyRepo)
    {
        $this->repo     = $repo;
        $this->roleRepo = $roleRepo;
        $this->companyRepo = $companyRepo;
    }
    public function index (){
        $plans = $this->repo->get();
        return view('backend.super-admin.plan.index',compact('plans'));
    }
    public function create (){
        $modules = $this->roleRepo->adminPermissionsModules();
        return view('backend.super-admin.plan.create',compact('modules'));
    }
    public function store (StoreRequest $request){

        if($this->repo->store($request)){
            Toastr::success('Plan created successfully.',__('message.success'));
            return redirect()->route('plan.index');
        }else{
            Toastr::error(__('account.error_msg'),__('message.error'));
            return redirect()->back();
        }
        
    }
    public function edit ($id){
        $plan = $this->repo->getFind($id);
        $modules = $this->roleRepo->adminPermissionsModules();
        return view('backend.super-admin.plan.edit',compact('plan','modules'));
    }
    public function update (StoreRequest $request){
        if($this->repo->update($request->id,$request)){
            Toastr::success('Plan updated successfully.',__('message.success'));
            return redirect()->route('plan.index');
        }else{
            Toastr::error(__('account.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }
    public function delete ($id){
        if($this->repo->delete($id)){
            Toastr::success('Plan deleted successfully.',__('message.success'));
            return redirect()->route('plan.index');
        }else{
            Toastr::error(__('account.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    public function modulesView($plan_id){
        $plan = $this->repo->getFind($plan_id);
        return view('backend.super-admin.plan.plan_modules',compact('plan'));
    }
 
    public function subscription(){
        $plans      = $this->repo->getActive();
        $allmodules = $this->roleRepo->adminPermissionsModules();
        $settings   = GeneralSettings::find(1);
        $stripeOn   = (bool) optional(Setting::where('company_id', 1)->where('key', 'stripe_status')->first())->value;

        $userSub = Auth::user()->subscription;
        $rows = collect($plans)->map(function ($plan) use ($allmodules, $userSub) {
            return [
                'id'          => $plan->id,
                'name'        => $plan->name,
                'description' => $plan->description,
                'price'       => (float) $plan->price,
                'intval_name' => $plan->intval_name,
                'parcel_count'=> $plan->parcel_count,
                'modules'     => collect($allmodules)->map(fn ($m) => [
                    'key'      => $m,
                    'label'    => trans('permissions.' . $m) ?: $m,
                    'included' => in_array($m, (array) $plan->modules),
                ])->values(),
                'is_current'  => $userSub && $userSub->plan_id == $plan->id,
                'remaining_days' => ($userSub && $userSub->plan_id == $plan->id) ? subscriptionCheck(Auth::user()) : null,
            ];
        })->values();

        return \Inertia\Inertia::render('Admin/Subscription/Index', [
            'plans'   => $rows,
            'stripe_on' => $stripeOn,
            'admin' => [
                'name'  => optional($settings)->name,
                'email' => optional($settings)->email,
                'phone' => optional($settings)->phone,
            ],
            'currency' => optional($settings)->currency,
            'urls' => [
                'pay_base' => route('subscription.payment'),
            ],
            't' => [
                'title'   => 'Subscription',
                'pick'    => 'Choose your right plan!',
                'active'  => __('levels.active') ?: 'Active',
                'expired' => __('levels.expired') ?: 'Expired',
                'remaining'  => __('levels.remaining') ?: 'Remaining',
                'days'    => __('levels.days') ?: 'days',
                'parcel_count' => 'Total parcel count',
                'subscribe'    => 'Subscribe',
                'when_billed'  => 'when billed annually',
                'contact_title'=> 'Contact admin to subscribe.',
                'contact_name' => 'Name',
                'contact_email'=> 'Email',
                'contact_phone'=> 'Phone',
            ],
        ]);
    }

    public function subscriptionHistory(Request $request){
        $isSuper = Auth::user()->user_type == UserType::SUPER_ADMIN;

        $paginator = Subscription::with(['company','plan','user'])
            ->where(function ($query) use ($request, $isSuper) {
                if (! $isSuper) {
                    $query->where('company_id', settings()->id);
                }
                if ($request->company_id) {
                    $query->where('company_id', $request->company_id);
                }
            })
            ->orderByDesc('id')
            ->paginate(10);

        $rows = collect($paginator->items())->map(fn ($s) => [
            'id'               => $s->id,
            'company'          => optional($s->company)->name,
            'user_name'        => optional($s->user)->name,
            'user_mobile'      => optional($s->user)->mobile,
            'user_address'     => optional($s->user)->address,
            'plan'             => optional($s->plan)->name,
            'price'            => (float) $s->price,
            'parcel_count'     => $s->parcel_count,
            'deliveryman_count'=> $s->deliveryman_count,
            'days_count'       => $s->days_count,
            'start_date'       => (string) $s->start_date,
            'expired_date'     => (string) $s->expired_date,
        ])->values();

        $companies = GeneralSettings::whereNot('id', 1)->get(['id','name']);

        return \Inertia\Inertia::render('Admin/Subscription/History', [
            'rows' => $rows,
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
                'company_id' => (string) ($request->company_id ?? ''),
            ],
            'lookups' => [
                'companies' => $companies->map(fn ($c) => ['value' => (string) $c->id, 'label' => $c->name])->values(),
            ],
            'is_super_admin' => $isSuper,
            'urls' => [
                'index' => route('admin.subscription.history'),
            ],
            't' => [
                'title'      => __('parcel.subscription_history') ?: 'Subscription history',
                'list'       => __('levels.list') ?: 'List',
                'company'    => __('parcel.company') ?: 'Company',
                'user'       => __('levels.user_details') ?: 'User details',
                'plan'       => __('levels.plan') ?: 'Plan',
                'price'      => __('levels.price') ?: 'Price',
                'parcel_count'      => __('parcel.parcel_count') ?: 'Parcels',
                'deliveryman_count' => __('levels.deliveryman_count') ?: 'Drivers',
                'days_count' => __('parcel.days_count') ?: 'Days',
                'start_date' => __('parcel.start_date') ?: 'Start',
                'expired_date' => __('parcel.expired_date') ?: 'Expires',
                'filter'     => __('levels.filter') ?: 'Filter',
                'clear'      => __('levels.clear') ?: 'Clear',
                'select'     => __('menus.select') ?: 'Select',
                'no_rows'    => 'No subscriptions yet.',
                'prev'       => 'Prev',
                'next'       => 'Next',
                'showing_results' => 'Showing :from – :to of :total',
            ],
        ]);
    }
 

    public function subscriptionPayment(Request $request){
    
        $stripe_secret_key        =  Setting::where('company_id',1)->where('key','stripe_secret_key')->first(); 
        $plan  = Plan::find($request->plan_id);
        if(!$plan):
            Toastr::error(__('account.error_msg'),__('message.error'));
            return redirect()->back();
        endif; 
        \Stripe\Stripe::setApiKey($stripe_secret_key->value); 
 
        $session = \Stripe\Checkout\Session::create([ 
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => 'USD',
                        'product_data' => [     
                            'name' => "Payment"
                        ],
                        'unit_amount' => (double)$plan->price * 100?? 0,
                    ],
                    'quantity' => 1,
                ]
            ], 
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'client_reference_id' => Auth::user()->id,  
            'success_url' => route('subscription.success',['plan_id'=>$plan->id,'user_id'=>Auth::user()->id]),
            'cancel_url'  => route('subscription.cancel'),
        ]);  
        return redirect()->to($session->url);
    }
 
    public function StripePaymentSuccess(Request $request){ 
        $this->companyRepo->switchPlan($request);
        Toastr::success('Subscribed successfully.','Success');
        return redirect()->route('dashboard.index');
    }

    public function StripePaymentCancel(Request $request){
        Toastr::error(__('account.error_msg'),__('message.error'));
        return redirect()->back();
    }
 
}
