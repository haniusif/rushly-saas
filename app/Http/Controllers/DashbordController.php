<?php

namespace App\Http\Controllers;

use App\Enums\AccountHeads;
use App\Enums\UserType;
use App\Enums\ParcelStatus;
use App\Enums\ApprovalStatus;
use App\Enums\BooleanStatus;
use App\Models\Backend\Role;
use App\Models\Backend\CourierStatement;
use App\Models\Backend\DeliverymanStatement;
use App\Models\Backend\MerchantStatement;
use App\Models\Backend\VatStatement;
use App\Models\User;
use App\Enums\StatementType;
use App\Models\Backend\Account;
use App\Models\Backend\BankTransaction;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Expense;
use App\Models\Backend\Hub;
use App\Models\Backend\HubStatement;
use App\Models\Backend\Income;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\Backend\Payment;
use App\Models\Backend\Fraud;
use App\Models\Backend\Subscription;
use App\Models\Backend\Superadmin\Plan;
use App\Models\MerchantShops;
use App\Models\SuperAdminPermission;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Repositories\Dashboard\DashboardInterface;
 
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class DashbordController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

     protected $repo;
     public function __construct(DashboardInterface $repo)
     {
        $this->repo    = $repo;

        
     }
    public function index(Request $request)
    {
    
  
        if(Auth::user()->user_type == UserType::SUPER_ADMIN){ 
       
            $data=[];
            $data['total_company']              = User::where('company_owner',BooleanStatus::YES)->where(function($query)use($request){
                if(!empty($request->filter_date)):
                    $query->where('created_at',$this->repo->FromTo($request));
                endif;
            })->count();
            $data['total_plans']                = Plan::where(function($query)use($request){
                if(!empty($request->filter_date)):
                    $query->where('created_at',$this->repo->FromTo($request));
                endif;
            })->count();
            $data['total_subscription']         = Subscription::where(function($query)use($request){
                if(!empty($request->filter_date)):
                    $query->where('created_at',$this->repo->FromTo($request));
                endif;
            })->count();
            $data['total_subscription_amount']  = Subscription::where(function($query)use($request){
                if(!empty($request->filter_date)):
                    $query->where('created_at',$this->repo->FromTo($request));
                endif;
            })->sum('price');


            $data['subscriptions']  = Subscription::where(function($query)use($request){
                if(!empty($request->filter_date)):
                    $query->where('created_at',$this->repo->FromTo($request));
                endif;
            })->orderBy('id','desc')->limit(10)->get();


            $data['recent_companies']      = User::where('company_owner',BooleanStatus::YES)->where(function($query)use($request){
                if(!empty($request->filter_date)):
                    $query->where('created_at',$this->repo->FromTo($request));
                endif;
            })->orderBy('id','desc')->limit(10)->get();



            $currencySymbol = settings()->currency ?: '$';

            return Inertia::render('Admin/Superadmin/Dashboard/Index', [
                'kpis' => [
                    'total_company'             => (int) $data['total_company'],
                    'total_plans'               => (int) $data['total_plans'],
                    'total_subscription'        => (int) $data['total_subscription'],
                    'total_subscription_amount' => (float) $data['total_subscription_amount'],
                ],
                'currency' => $currencySymbol,
                'recent_companies' => collect($data['recent_companies'])->map(fn ($c) => [
                    'id'          => $c->id,
                    'name'        => $c->name,
                    'email'       => $c->email,
                    'avatar'      => $c->image,
                    'status_html' => $c->my_status,
                    'company'     => [
                        'name' => optional($c->company)->name,
                        'logo' => optional($c->company)->LogoImage,
                    ],
                    'plan'        => optional($c->company)->plan ? [
                        'name'         => optional($c->company)->plan->name,
                        'module_count' => is_array(optional($c->company)->plan->modules)
                            ? count(optional($c->company)->plan->modules)
                            : 0,
                    ] : null,
                ])->values(),
                'recent_subscriptions' => collect($data['subscriptions'])->map(fn ($s) => [
                    'id'           => $s->id,
                    'company_name' => optional($s->company)->name,
                    'plan_name'    => optional($s->plan)->name,
                    'price'        => (float) $s->price,
                    'expired_date' => (string) $s->expired_date,
                ])->values(),
                'filter' => [
                    'from_date' => $request->filter_date,
                ],
                'urls' => [
                    'submit'          => route('dashboard.index'),
                    'companies_index' => route('company.index'),
                    'plans_index'     => route('plan.index'),
                ],
                't' => [
                    'title'                 => __('merchant.dashboard'),
                    'subtitle'              => __('levels.dashboard'),
                    'filter'                => __('levels.filter'),
                    'date_ph'               => 'YYYY-MM-DD ~ YYYY-MM-DD',
                    'total_company'         => __('dashboard.total_company'),
                    'total_plans'           => __('dashboard.total_plans'),
                    'total_subscription'    => __('dashboard.total_subscription'),
                    'total_subscription_price' => __('dashboard.total_subscription_price'),
                    'recent_company'        => __('levels.recent_company'),
                    'recent_subscriptions'  => __('levels.recent_subscriptions'),
                    'name'                  => __('levels.name'),
                    'user_details'          => __('levels.user_details'),
                    'modules'               => __('levels.modules'),
                    'status'                => __('levels.status'),
                    'company'               => __('levels.company'),
                    'plan'                  => __('levels.plan'),
                    'price'                 => __('levels.price'),
                    'expired_date'          => __('levels.expired_date'),
                    'view_all'              => __('levels.list'),
                    'no_data'               => __('levels.no_data_found'),
                ],
            ]);
        }elseif(Auth::user()->user_type == UserType::MERCHANT){

            if(!subscriptionCheck()):
                return redirect()->route('subscribe.index');
            endif;
            
            $merchant_id = Auth::user()->merchant->id;
 
            $t_parcel       = Parcel::where('merchant_id',$merchant_id)->count();
            
            $t_delivered    = Parcel::where('status',ParcelStatus::DELIVERED)->where('merchant_id',$merchant_id)->count();
            $t_return       = Parcel::where('status',ParcelStatus::RETURN_RECEIVED_BY_MERCHANT)->where('merchant_id',$merchant_id)->count();
            $t_shop         = MerchantShops::where('merchant_id',$merchant_id)->count();
            $t_parcel_bank  = Parcel::where('merchant_id',$merchant_id)->where('parcel_bank','on')->count();
            $merchant       = Merchant::where('id',$merchant_id)->first();
            $parcels        = Parcel::where('merchant_id',$merchant_id)->get();

            $t_cash_collection   = 0;
            $t_selling_price     = 0;
            $t_liquid_fragile    = 0;
            $t_vat_amount        = 0;
            $t_delivery_charge   = 0;
            $t_cod_amount        = 0;
            $t_packaging         = 0;
            $t_delivery_amount   = 0;
            $t_current_payable   = 0;

            foreach($parcels as $parcel){
                if($parcel->status != ParcelStatus::RETURN_RECEIVED_BY_MERCHANT){
                    $t_cash_collection = $t_cash_collection + $parcel->cash_collection;
                    $t_selling_price   = $t_selling_price   + $parcel->selling_price;
                    $t_current_payable = $t_current_payable + $parcel->current_payable;
                }
                $t_liquid_fragile  = $t_liquid_fragile  + $parcel->liquid_fragile_amount;
                $t_vat_amount      = $t_vat_amount      + $parcel->vat_amount;
                $t_delivery_charge = $t_delivery_charge + $parcel->delivery_charge;
                $t_cod_amount      = $t_cod_amount      + $parcel->cod_amount;
                $t_packaging       = $t_packaging       + $parcel->packaging_amount;
                $t_delivery_amount = $t_delivery_amount + $parcel->total_delivery_amount;

            }

            $dates        = [];
            $totals       = [];
            $pendings     = [];
            $delivers     = [];
            $par_delivers = [];
            $returns      = [];

            for($i = 7; $i >= 0; $i--){

                $date = date('Y-m-d', strtotime(' -'. $i .' day'));

                $total         = Parcel::where('merchant_id',$merchant_id)->where('updated_at','like', $date.'%')->count();
                $pending       = Parcel::where('merchant_id',$merchant_id)->where('status',ParcelStatus::PENDING)->where('updated_at','like', $date.'%')->count();
                $delivered     = Parcel::where('merchant_id',$merchant_id)->where('status',ParcelStatus::DELIVERED)->where('updated_at','like', $date.'%')->count();
                $par_delivered = Parcel::where('merchant_id',$merchant_id)->where('status',ParcelStatus::PARTIAL_DELIVERED)->where('updated_at','like', $date.'%')->count();
                $returned      = Parcel::where('merchant_id',$merchant_id)->where('status',ParcelStatus::RETURN_RECEIVED_BY_MERCHANT)->where('updated_at','like', $date.'%')->count();

                array_push($dates, $date);
                array_push($totals, $total);
                array_push($pendings, $pending);
                array_push($delivers, $delivered);
                array_push($par_delivers, $par_delivered);
                array_push($returns, $returned);
            }


            $t_sale         = Parcel::where('merchant_id',$merchant_id)->whereIn('status',[ParcelStatus::DELIVERED,ParcelStatus::PARTIAL_DELIVERED])->sum('cash_collection');
            $ts_vat         = Parcel::where('merchant_id',$merchant_id)->whereIn('status',[ParcelStatus::DELIVERED,ParcelStatus::PARTIAL_DELIVERED])->sum('vat_amount');
            $t_delivery_fee = Parcel::where('merchant_id',$merchant_id)->whereIn('status',[ParcelStatus::DELIVERED,ParcelStatus::PARTIAL_DELIVERED])->sum('total_delivery_amount');

            $t_balance_proc = Payment::where('merchant_id',$merchant_id)->where('status',ApprovalStatus::PENDING)->sum('amount');
            $t_balance_paid = Payment::where('merchant_id',$merchant_id)->where('status',ApprovalStatus::PROCESSED)->sum('amount');
            $t_request      = Payment::where('merchant_id',$merchant_id)->count();
            $t_fraud        = Fraud::where('created_by',Auth::user()->id)->count();

            $fromTo                         = $this->repo->FromTo($request);//from/to date
            //pie charts total
            $piedata = [];
            $piedata['total_parcels']          = Parcel::where(['merchant_id'=>$merchant_id])->count();
            $piedata['total_pending']          = Parcel::where(['merchant_id'=>$merchant_id,'status'=>ParcelStatus::PENDING])->count();
            $piedata['total_delivered']        = Parcel::where(['merchant_id'=> $merchant_id,'status'=>ParcelStatus::DELIVERED])->count();
            $piedata['total_partial_delivered']= Parcel::where(['merchant_id'=> $merchant_id,'status'=>ParcelStatus::PARTIAL_DELIVERED])->count();
            $piedata['total_return']           = Parcel::where(['merchant_id'=> $merchant_id,'status'=>ParcelStatus::RETURN_RECEIVED_BY_MERCHANT])->count();

            $currency      = settings()->currency;
            $rlMerchant    = Auth::user()->merchant;
            $rlServices    = $rlMerchant ? $rlMerchant->activeServices() : [];

            $netProfit     = ((float) $t_cash_collection) - ((float) $t_selling_price);
            $totalProfit   = ((float) $t_sale) - ((float) $t_delivery_fee) - ((float) $ts_vat);
            $inTransit     = max(0, (int) $t_parcel - ((int) $t_delivered + (int) $t_return));
            $computedBal   = (float) (optional($rlMerchant)->computed_balance ?? 0);
            $openingBal    = (float) (optional($rlMerchant)->opening_balance ?? 0);
            $merchantVat   = (float) (optional($rlMerchant)->vat ?? 0);

            return Inertia::render('Merchant/Dashboard/Index', [
                'currency'      => $currency,
                'merchant'      => $rlMerchant ? [
                    'id'            => $rlMerchant->id,
                    'business_name' => $rlMerchant->business_name,
                ] : null,
                'services'      => array_values($rlServices),
                'request_date'  => $request->date ?? null,
                'parcel_kpis'   => [
                    'total'     => (int) $t_parcel,
                    'delivered' => (int) $t_delivered,
                    'returned'  => (int) $t_return,
                    'in_transit'=> (int) $inTransit,
                ],
                'active_amounts' => [
                    'cash_collection' => (float) $t_cash_collection,
                    'selling_price'   => (float) $t_selling_price,
                    'net_profit'      => (float) $netProfit,
                ],
                'fees_amounts' => [
                    'liquid_fragile' => (float) $t_liquid_fragile,
                    'packaging'      => (float) $t_packaging,
                    'vat'            => (float) $t_vat_amount,
                ],
                'delivery_amounts' => [
                    'delivery_charge'  => (float) $t_delivery_charge,
                    'cod'              => (float) $t_cod_amount,
                    'delivery_total'   => (float) $t_delivery_amount,
                ],
                'reports' => [
                    'total_sales'         => (float) $t_sale,
                    'total_delivery_fees' => (float) $t_delivery_fee,
                    'total_vat'           => (float) $ts_vat,
                    'net_profit'          => (float) $totalProfit,
                    'current_balance'     => (float) $computedBal,
                    'opening_balance'     => (float) $openingBal,
                    'merchant_vat'        => (float) $merchantVat,
                    'payment_processing'  => (float) $t_balance_proc,
                    'paid_amount'         => (float) $t_balance_paid,
                    'total_shop'          => (int) $t_shop,
                    'total_parcel_bank'   => (int) $t_parcel_bank,
                    'total_payment_req'   => (int) $t_request,
                ],
                'series' => [
                    'dates'         => $dates,
                    'totals'        => array_map('intval', $totals),
                    'pendings'      => array_map('intval', $pendings),
                    'delivers'      => array_map('intval', $delivers),
                    'par_delivers'  => array_map('intval', $par_delivers),
                    'returns'       => array_map('intval', $returns),
                ],
                'pie' => [
                    'pending'           => (int) ($piedata['total_pending'] ?? 0),
                    'delivered'         => (int) ($piedata['total_delivered'] ?? 0),
                    'partial_delivered' => (int) ($piedata['total_partial_delivered'] ?? 0),
                    'returned'          => (int) ($piedata['total_return'] ?? 0),
                ],
                'urls' => [
                    'filter'           => route('merchant-panel.dashboard.filter'),
                    'parcels'          => route('merchant-panel.parcel.index'),
                    'parcels_delivered'=> route('merchant-panel.parcel.filter', ['parcel_status' => ParcelStatus::DELIVERED]),
                    'parcels_returned' => route('merchant-panel.parcel.filter', ['parcel_status' => ParcelStatus::RETURN_RECEIVED_BY_MERCHANT]),
                ],
                't' => $this->merchantDashboardLabels(),
            ]);
        }else{

            if(!subscriptionCheck()):
                return redirect()->route('subscribe.index');
            endif;
            
            $c_income       = CourierStatement::companywise()->whereNot('parcel_id',null)->where('type',StatementType::INCOME)->whereBetween('updated_at',$this->repo->FromTo($request))->sum('amount');
            $c_expense      = CourierStatement::companywise()->whereNot('parcel_id',null)->where('type',StatementType::EXPENSE)->whereBetween('updated_at',$this->repo->FromTo($request))->sum('amount');
            $d_income       = DeliverymanStatement::companywise()->where('type',StatementType::INCOME)->whereBetween('updated_at',$this->repo->FromTo($request))->sum('amount');
            $d_expense      = DeliverymanStatement::companywise()->where('type',StatementType::EXPENSE)->whereBetween('updated_at',$this->repo->FromTo($request))->sum('amount');
            $m_income       = MerchantStatement::companywise()->where('type',StatementType::INCOME)->whereBetween('updated_at',$this->repo->FromTo($request))->sum('amount');
            $m_expense      = MerchantStatement::companywise()->where('type',StatementType::EXPENSE)->whereBetween('updated_at',$this->repo->FromTo($request))->sum('amount');
            $v_income       = VatStatement::companywise()->where('type',StatementType::INCOME)->whereBetween('updated_at',$this->repo->FromTo($request))->sum('amount');
            $v_expense      = VatStatement::companywise()->where('type',StatementType::EXPENSE)->whereBetween('updated_at',$this->repo->FromTo($request))->sum('amount');
            $b_income       = BankTransaction::companywise()->where('type',StatementType::INCOME)->whereBetween('updated_at',$this->repo->FromTo($request))->sum('amount');
            $b_expense      = BankTransaction::companywise()->where('type',StatementType::EXPENSE)->whereBetween('updated_at',$this->repo->FromTo($request))->sum('amount');
            $h_income       = HubStatement::companywise()->where('type',StatementType::INCOME)->whereBetween('updated_at',$this->repo->FromTo($request))->sum('amount');
            $h_expense      = HubStatement::companywise()->where('type',StatementType::EXPENSE)->whereBetween('updated_at',$this->repo->FromTo($request))->sum('amount');
            $data           = [];

            $data['recent_parcels']             = Parcel::companywise()->whereBetween('updated_at',$this->repo->FromTo($request))->orderByDesc('id')->limit(5)->get();
            $data['total_parcel']               = Parcel::companywise()->whereBetween('updated_at',$this->repo->FromTo($request))->count();//total parcel
            $data['total_user']                 = User::companywise()->whereBetween('updated_at',$this->repo->FromTo($request))->count();//total user
            $data['total_merchant']             = Merchant::companywise()->whereBetween('updated_at',$this->repo->FromTo($request))->count();//total merchant
            $data['total_delivery_man']         = DeliveryMan::companywise()->whereBetween('updated_at',$this->repo->FromTo($request))->count();//total delivery man
            $data['total_hubs']                 = Hub::companywise()->whereBetween('updated_at',$this->repo->FromTo($request))->count();//total hubs
            $data['total_accounts']             = Account::companywise()->whereBetween('updated_at',$this->repo->FromTo($request))->count();//total accounts
            //status wise parcel count
            $data['total_deliveryman_assigned'] = $this->repo->parcelPosition($request,ParcelStatus::DELIVERY_MAN_ASSIGN,$this->repo->FromTo($request))->count();
            $data['total_partial_deliverd']     = $this->repo->parcelPosition($request,ParcelStatus::PARTIAL_DELIVERED,$this->repo->FromTo($request))->count();
            $data['total_deliverd']             = $this->repo->parcelPosition($request,ParcelStatus::DELIVERED,$this->repo->FromTo($request))->count();
            //end status wise parcel count
            $data['hub_parcels']                = Hub::with(['parcels'])->whereBetween('updated_at',$this->repo->FromTo($request))->limit(4)->get();
            //end salary

            $dates                           =  $this->repo->Dates($request);// 7days
            $data['incomeDates']             =   $dates;
            $data['expenseDates']            =   $dates;
            $data['merchantRevDates']        =   $dates;
            $data['DeliverymanRevDates']     =   $dates;

            $fromTo                         = $this->repo->FromTo($request);//from/to date
            $data['income']                 = $this->repo->income($fromTo);
            $data['expense']                = $this->repo->expense($fromTo);
            $data['merchantIncome']         = $this->repo->merchantIncome($fromTo);
            $data['merchantExpense']        = $this->repo->merchantExpense($fromTo);
            $data['deliverymanIncome']      = $this->repo->deliverymanIncome($fromTo);
            $data['deliverymanExpense']     = $this->repo->deliverymanExpense($fromTo);
            $data['bank_transactions']      = $this->repo->bankTransaction($fromTo);
            $data['courier_income']         = $this->repo->courierIncome($fromTo);
            $data['courier_expense']         = $this->repo->courierExpense($fromTo);

            return Inertia::render('Admin/Dashboard/Index', [
                'currency' => settings()->currency,
                'ledgers'  => [
                    [ 'key' => 'courier',     'label' => 'Courier',     'income' => (float) $c_income, 'expense' => (float) $c_expense ],
                    [ 'key' => 'deliveryman', 'label' => 'Couriers',    'income' => (float) $d_income, 'expense' => (float) $d_expense ],
                    [ 'key' => 'merchant',    'label' => 'Merchants',   'income' => (float) $m_income, 'expense' => (float) $m_expense ],
                    [ 'key' => 'vat',         'label' => 'VAT',         'income' => (float) $v_income, 'expense' => (float) $v_expense ],
                    [ 'key' => 'bank',        'label' => 'Bank',        'income' => (float) $b_income, 'expense' => (float) $b_expense ],
                    [ 'key' => 'hub',         'label' => 'Hubs',        'income' => (float) $h_income, 'expense' => (float) $h_expense ],
                ],
                'kpis' => [
                    'parcels'       => (int) $data['total_parcel'],
                    'users'         => (int) $data['total_user'],
                    'merchants'     => (int) $data['total_merchant'],
                    'deliverymen'   => (int) $data['total_delivery_man'],
                    'hubs'          => (int) $data['total_hubs'],
                    'accounts'      => (int) $data['total_accounts'],
                ],
                'pipeline' => [
                    'assigned'        => (int) $data['total_deliveryman_assigned'],
                    'partial_delivered' => (int) $data['total_partial_deliverd'],
                    'delivered'       => (int) $data['total_deliverd'],
                ],
                'hub_parcels' => collect($data['hub_parcels'])->map(fn ($h) => [
                    'id'           => $h->id,
                    'name'         => $h->name,
                    'parcels_count'=> $h->parcels?->count() ?? 0,
                ])->values(),
                'recent_parcels' => collect($data['recent_parcels'])->map(fn ($p) => [
                    'id'              => $p->id,
                    'tracking_id'     => $p->tracking_id ?? $p->code ?? '—',
                    'merchant_name'   => optional($p->merchant)->business_name ?? optional($p->merchant)->title,
                    'status'          => (int) $p->status,
                    'cash_collection' => (float) ($p->cash_collection ?? 0),
                    'created_at'      => optional($p->created_at)->toDateString(),
                ])->values(),
                'series' => [
                    'dates'              => $dates,
                    'income'             => $this->asSeries($data['income']             ?? null),
                    'expense'            => $this->asSeries($data['expense']            ?? null),
                    'merchantIncome'     => $this->asSeries($data['merchantIncome']     ?? null),
                    'merchantExpense'    => $this->asSeries($data['merchantExpense']    ?? null),
                    'deliverymanIncome'  => $this->asSeries($data['deliverymanIncome']  ?? null),
                    'deliverymanExpense' => $this->asSeries($data['deliverymanExpense'] ?? null),
                ],
                'totals' => [
                    'income'             => $this->asScalar($data['income']             ?? null),
                    'expense'            => $this->asScalar($data['expense']            ?? null),
                    'merchantIncome'     => $this->asScalar($data['merchantIncome']     ?? null),
                    'merchantExpense'    => $this->asScalar($data['merchantExpense']    ?? null),
                    'deliverymanIncome'  => $this->asScalar($data['deliverymanIncome']  ?? null),
                    'deliverymanExpense' => $this->asScalar($data['deliverymanExpense'] ?? null),
                ],
                't' => $this->adminDashboardLabels(),
            ]);
        }
    }

    /**
     * Whatever the repo hands back — array (chart series), Collection, or a
     * scalar sum — return a clean numeric list for the React sparkline. A
     * scalar collapses to a single-point series of zeros so the chart card
     * stays empty rather than crashing.
     */
    private function asSeries($v): array
    {
        if (is_array($v))            return array_values(array_map('floatval', $v));
        if ($v instanceof \Illuminate\Support\Collection) {
            return $v->values()->map(fn ($x) => (float) $x)->all();
        }
        return [];
    }

    private function asScalar($v): float
    {
        if (is_numeric($v))          return (float) $v;
        if (is_array($v))            return (float) array_sum($v);
        if ($v instanceof \Illuminate\Support\Collection) return (float) $v->sum();
        return 0.0;
    }

    /**
     * Flat translation map for the admin dashboard. Falls back to inline English
     * when the lang key is missing so the JSX never renders a raw key.
     */
    private function adminDashboardLabels(): array
    {
        // Reusable shared strings live in levels.* / menus.* (they appear on
        // many pages). Dashboard-specific copy lives in dashboard.* so the
        // wording can be tuned without affecting other screens.
        return [
            'dashboard'              => __('levels.dashboard'),
            'parcels'                => __('menus.parcel'),
            'users'                  => __('levels.users') ?: 'Users',
            'merchants'              => __('menus.merchants') ?: 'Merchants',
            'deliverymen'            => __('menus.deliveryman') ?: 'Couriers',
            'hubs'                   => __('menus.hubs') ?: 'Hubs',
            'accounts'               => __('menus.accounts') ?: 'Accounts',
            'income'                 => __('levels.income') ?: 'Income',
            'expense'                => __('levels.expense') ?: 'Expense',
            'merchant'               => __('levels.merchant') ?: 'Merchant',
            'status'                 => __('levels.status') ?: 'Status',
            'cash'                   => __('levels.cash_collection') ?: 'Cash',
            'created_at'             => __('levels.created_at') ?: 'Created',
            'no_data'                => __('levels.no_data_found') ?: 'No data',
            'parcels_label'          => __('menus.parcel'),

            'ledger_summary'         => __('dashboard.ledger_summary'),
            'recent_parcels'         => __('dashboard.recent_parcels'),
            'hub_parcels_title'      => __('dashboard.hub_parcels_title'),
            'pipeline_title'         => __('dashboard.pipeline_title'),
            'pipeline_assigned'      => __('dashboard.pipeline_assigned'),
            'pipeline_partial'       => __('dashboard.pipeline_partial'),
            'pipeline_delivered'     => __('dashboard.pipeline_delivered'),
            'tracking_id'            => __('dashboard.tracking_id'),
            'income_vs_expense'      => __('dashboard.income_vs_expense'),
            'merchant_revenue'       => __('dashboard.merchant_revenue'),
            'courier_revenue'        => __('dashboard.courier_revenue'),

            'status_pending'          => __('dashboard.status_pending'),
            'status_picked'           => __('dashboard.status_picked'),
            'status_in_transit'       => __('dashboard.status_in_transit'),
            'status_at_hub'           => __('dashboard.status_at_hub'),
            'status_assigned'         => __('dashboard.status_assigned'),
            'status_out_for_delivery' => __('dashboard.status_out_for_delivery'),
            'status_delivered'        => __('dashboard.status_delivered'),
            'status_partial'          => __('dashboard.status_partial'),
        ];
    }

    /**
     * Flat translation map for the merchant dashboard. Mirrors the admin
     * variant so the React component can render with no raw lang-keys
     * leaking through when an underlying language file is missing entries.
     */
    private function merchantDashboardLabels(): array
    {
        return [
            'dashboard'                   => __('merchant.dashboard') ?: 'Dashboard',
            'merchant_dashboard'          => __('merchant.merchant_dashboard') ?: 'Client Dashboard',
            'services'                    => __('merchant.services') ?: 'Services',
            'service_last_mile'           => __('merchant.service_last_mile') ?: 'Last mile',
            'service_fulfillment'         => __('merchant.service_fulfillment') ?: 'Fulfillment',
            'service_storage'             => __('merchant.service_storage') ?: 'Storage',
            'filter'                      => __('levels.filter') ?: 'Filter',
            'date_ph'                     => __('merchantPlaceholder.date') ?: 'YYYY-MM-DD ~ YYYY-MM-DD',
            // Parcel KPI tiles
            'total_parcel'                => __('dashboard.total_parcel') ?: 'Total shipments',
            'total_delivered'             => __('dashboard.total_deliverd_') ?: 'Total delivered',
            'total_return'                => __('dashboard.total_return') ?: 'Total return',
            'total_transit'               => __('dashboard.total_transit') ?: 'Total transit',
            // Active shipment amounts card
            'active_amounts_title'        => __('dashboard.active_shipments_amount') ?: 'Active Shipments Amount',
            'active_shipments_amount'     => __('dashboard.active_shipments_amount') ?: 'Active Shipments Amount',
            'total_selling_price'         => __('dashboard.total_selling_price') ?: 'Total selling price',
            'net_profit_amount'           => __('dashboard.net_profit_ammount') ?: 'Net profit amount',
            // Liquid / packaging / VAT card
            'liquid_amounts_title'        => __('dashboard.total_liquid_fragile_amount') ?: 'Total liquid fragile amount',
            'total_liquid_fragile_amount' => __('dashboard.total_liquid_fragile_amount') ?: 'Total liquid fragile amount',
            'total_packaging_amount'      => __('dashboard.total_packaging_amount') ?: 'Total packaging amount',
            'total_vat_amount'            => __('dashboard.total_vat_amount') ?: 'Total VAT amount',
            // Delivery / COD card
            'delivery_amounts_title'      => __('dashboard.total_total_delivery_amount') ?: 'Total delivery amount',
            'total_delivery_charge'       => __('dashboard.total_delivery_charge') ?: 'Total delivery charge',
            'total_cod_amount'            => __('dashboard.total_cod_amount') ?: 'Total COD amount',
            'total_total_delivery_amount' => __('dashboard.total_total_delivery_amount') ?: 'Total delivery amount',
            // Charts
            'parcels_chart'               => __('dashboard.parcels_chart') ?: 'Parcels — last 8 days',
            'parcels_breakdown'           => __('dashboard.parcels_breakdown') ?: 'Parcels by status',
            'series_total'                => __('dashboard.series_total') ?: 'Total',
            'series_pending'              => __('dashboard.status_pending') ?: 'Pending',
            'series_delivered'            => __('dashboard.status_delivered') ?: 'Delivered',
            'series_partial'              => __('dashboard.status_partial') ?: 'Partial',
            'series_returned'             => __('dashboard.total_return') ?: 'Returned',
            // All reports
            'all_reports'                 => __('dashboard.all_reports') ?: 'All reports',
            'total_sales_amount'          => __('dashboard.total_sales_amount') ?: 'Total sales amount',
            'total_delivery_fees_paid'    => __('dashboard.total_delivery_fees_paid') ?: 'Total delivery fees paid',
            'total_vat'                   => __('levels.total_vat') ?: 'Total VAT',
            'net_profit'                  => __('dashboard.net_profit_ammount') ?: 'Net profit',
            'current_balance'             => __('dashboard.current_balance') ?: 'Current balance',
            'opening_balance'             => __('dashboard.opening_balance') ?: 'Opening balance',
            'vat'                         => __('dashboard.vat') ?: 'VAT',
            'payment_processing'          => __('dashboard.payment_processing') ?: 'Payment processing',
            'paid_amount'                 => __('dashboard.paid_amount') ?: 'Paid amount',
            'total_shop'                  => __('dashboard.total_shop') ?: 'Total shops',
            'total_parcel_bank_items'     => __('dashboard.total_parcel_bank_items') ?: 'Total parcel bank items',
            'total_payment_request'       => __('dashboard.total_payment_request') ?: 'Total payment requests',
        ];
    }

    public function searchCharts(Request $request){
        $data    = [];
        $data['dates']                      = $this->repo->dates($request);
        $fromTo                             = $this->repo->FromTo($request);
        if($request->type     == 'income_expense'):
            $data['income']                 = $this->repo->income($fromTo);
            $data['expense']                = $this->repo->expense($fromTo);
        elseif($request->type == 'merchant'):
            $data['merchantIncome']         = $this->repo->merchantIncome($fromTo);
            $data['merchantExpense']        = $this->repo->merchantExpense($fromTo);
        elseif($request->type == 'deliveryman'):
            $data['deliverymanIncome']      = $this->repo->deliverymanIncome($fromTo);
            $data['deliverymanExpense']     = $this->repo->deliverymanExpense($fromTo);
        endif;

        return $data;

    }


    public function merchantDashboardFilter(Request $request){
       
       
        $from = date('Y-m-d');
        $to   = date('Y-m-d');
        if($request->date) {
            $date = explode('To', $request->date);
            if(is_array($date)) {
                $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
            }
        }
        
        $merchant_id = Auth::user()->merchant->id;

        $merchant       = Merchant::where('id',$merchant_id)->first();
        $t_fraud        = Fraud::where('created_by',Auth::user()->id)->count();
        $t_shop         = MerchantShops::where('merchant_id',$merchant_id)->count();

        $t_parcel       = Parcel::where('merchant_id',$merchant_id)->whereBetween('updated_at', [$from, $to])->count();
        $t_delivered    = Parcel::where('status',ParcelStatus::DELIVERED)->where('merchant_id',$merchant_id)->whereBetween('updated_at', [$from, $to])->count();
        $t_return       = Parcel::where('status',ParcelStatus::RETURN_RECEIVED_BY_MERCHANT)->where('merchant_id',$merchant_id)->whereBetween('updated_at', [$from, $to])->count();
        $t_parcel_bank  = Parcel::where('merchant_id',$merchant_id)->where('parcel_bank','on')->whereBetween('updated_at', [$from, $to])->count();
        $t_sale         = Parcel::where('merchant_id',$merchant_id)->whereBetween('updated_at', [$from, $to])->where('status',ParcelStatus::DELIVERED)->orwhere('status',ParcelStatus::PARTIAL_DELIVERED)->sum('cash_collection');
        $t_delivery_fee = Parcel::where('merchant_id',$merchant_id)->whereBetween('updated_at', [$from, $to])->where('status',ParcelStatus::DELIVERED)->orwhere('status',ParcelStatus::PARTIAL_DELIVERED)->sum('total_delivery_amount');
        $t_balance_proc = Payment::where('merchant_id',$merchant_id)->where('status',ApprovalStatus::PENDING)->whereBetween('updated_at', [$from, $to])->sum('amount');
        $t_balance_paid = Payment::where('merchant_id',$merchant_id)->where('status',ApprovalStatus::PROCESSED)->whereBetween('updated_at', [$from, $to])->sum('amount');
        $t_request      = Payment::where('merchant_id',$merchant_id)->whereBetween('updated_at', [$from, $to])->count();
        $parcels        = Parcel::where('merchant_id',$merchant_id)->whereBetween('updated_at', [$from, $to])->get();
        $ts_vat         = Parcel::where('merchant_id',$merchant_id)->whereIn('status',[ParcelStatus::DELIVERED,ParcelStatus::PARTIAL_DELIVERED])->whereBetween('updated_at', [$from, $to])->sum('vat_amount');
        $t_cash_collection   = 0;
        $t_selling_price     = 0;
        $t_liquid_fragile    = 0;
        $t_vat_amount        = 0;
        $t_delivery_charge   = 0;
        $t_cod_amount        = 0;
        $t_packaging         = 0;
        $t_delivery_amount   = 0;
        $t_current_payable   = 0;

        foreach($parcels as $parcel){
            if($parcel->status != ParcelStatus::RETURN_RECEIVED_BY_MERCHANT){
                $t_cash_collection = $t_cash_collection + $parcel->cash_collection;
                $t_selling_price   = $t_selling_price   + $parcel->selling_price;
                $t_current_payable = $t_current_payable + $parcel->current_payable;
            }
            $t_liquid_fragile  = $t_liquid_fragile  + $parcel->liquid_fragile_amount;
            $t_vat_amount      = $t_vat_amount      + $parcel->vat_amount;
            $t_delivery_charge = $t_delivery_charge + $parcel->delivery_charge;
            $t_cod_amount      = $t_cod_amount      + $parcel->cod_amount;
            $t_packaging       = $t_packaging       + $parcel->packaging_amount;
            $t_delivery_amount = $t_delivery_amount + $parcel->total_delivery_amount;
        }

        $dates        = [];
        $totals       = [];
        $pendings     = [];
        $delivers     = [];
        $par_delivers = [];
        $returns      = [];


        $new_from_date = substr($from,0,10);
        $new_to_date   = substr($to,0,10);
        $time          = strtotime($new_to_date);
        $diff          = Carbon::parse($new_from_date)->diffInDays($new_to_date);

        for($i = $diff; $i >= 0; $i--){
            $date = date('Y-m-d', strtotime(' -'. $i .' day', $time));
            $total         = Parcel::where('merchant_id',$merchant_id)->where('updated_at','like', $date.'%')->count();
            $pending       = Parcel::where('merchant_id',$merchant_id)->where('status',ParcelStatus::PENDING)->where('updated_at','like', $date.'%')->count();
            $delivered     = Parcel::where('merchant_id',$merchant_id)->where('status',ParcelStatus::DELIVERED)->where('updated_at','like', $date.'%')->count();
            $par_delivered = Parcel::where('merchant_id',$merchant_id)->where('status',ParcelStatus::PARTIAL_DELIVERED)->where('updated_at','like', $date.'%')->count();
            $returned      = Parcel::where('merchant_id',$merchant_id)->where('status',ParcelStatus::RETURN_RECEIVED_BY_MERCHANT)->where('updated_at','like', $date.'%')->count();

            array_push($dates, $date);
            array_push($totals, $total);
            array_push($pendings, $pending);
            array_push($delivers, $delivered);
            array_push($par_delivers, $par_delivered);
            array_push($returns, $returned);
        }



        //pie charts total
        $piedata = [];
        $piedata['total_parcels']          = Parcel::where(['merchant_id'=>$merchant_id])->whereBetween('updated_at', [$from, $to])->count();
        $piedata['total_pending']          = Parcel::where(['merchant_id'=>$merchant_id,'status'=>ParcelStatus::PENDING])->whereBetween('updated_at', [$from, $to])->count();
        $piedata['total_delivered']        = Parcel::where(['merchant_id'=> $merchant_id,'status'=>ParcelStatus::DELIVERED])->whereBetween('updated_at', [$from, $to])->count();
        $piedata['total_partial_delivered']= Parcel::where(['merchant_id'=> $merchant_id,'status'=>ParcelStatus::PARTIAL_DELIVERED])->whereBetween('updated_at', [$from, $to])->count();
        $piedata['total_return']           = Parcel::where(['merchant_id'=> $merchant_id,'status'=>ParcelStatus::RETURN_RECEIVED_BY_MERCHANT])->whereBetween('updated_at', [$from, $to])->count();
          
               
        // ── Inertia: re-render the same dashboard component with filtered values.
        $rlMerchant  = Auth::user()->merchant;
        $rlServices  = $rlMerchant ? $rlMerchant->activeServices() : [];

        $netProfit   = ((float) $t_cash_collection) - ((float) $t_selling_price);
        $totalProfit = ((float) $t_sale) - ((float) $t_delivery_fee) - ((float) $ts_vat);
        $inTransit   = max(0, (int) $t_parcel - ((int) $t_delivered + (int) $t_return));
        $computedBal = (float) (optional($rlMerchant)->computed_balance ?? 0);
        $openingBal  = (float) (optional($rlMerchant)->opening_balance ?? 0);
        $merchantVat = (float) (optional($rlMerchant)->vat ?? 0);

        return Inertia::render('Merchant/Dashboard/Index', [
            'currency'      => settings()->currency,
            'merchant'      => $rlMerchant ? [
                'id'            => $rlMerchant->id,
                'business_name' => $rlMerchant->business_name,
            ] : null,
            'services'      => array_values($rlServices),
            'request_date'  => $request->date ?? null,
            'parcel_kpis'   => [
                'total'      => (int) $t_parcel,
                'delivered'  => (int) $t_delivered,
                'returned'   => (int) $t_return,
                'in_transit' => (int) $inTransit,
            ],
            'active_amounts' => [
                'cash_collection' => (float) $t_cash_collection,
                'selling_price'   => (float) $t_selling_price,
                'net_profit'      => (float) $netProfit,
            ],
            'fees_amounts' => [
                'liquid_fragile' => (float) $t_liquid_fragile,
                'packaging'      => (float) $t_packaging,
                'vat'            => (float) $t_vat_amount,
            ],
            'delivery_amounts' => [
                'delivery_charge' => (float) $t_delivery_charge,
                'cod'             => (float) $t_cod_amount,
                'delivery_total'  => (float) $t_delivery_amount,
            ],
            'reports' => [
                'total_sales'         => (float) $t_sale,
                'total_delivery_fees' => (float) $t_delivery_fee,
                'total_vat'           => (float) $ts_vat,
                'net_profit'          => (float) $totalProfit,
                'current_balance'     => (float) $computedBal,
                'opening_balance'     => (float) $openingBal,
                'merchant_vat'        => (float) $merchantVat,
                'payment_processing'  => (float) $t_balance_proc,
                'paid_amount'         => (float) $t_balance_paid,
                'total_shop'          => (int) $t_shop,
                'total_parcel_bank'   => (int) $t_parcel_bank,
                'total_payment_req'   => (int) $t_request,
            ],
            'series' => [
                'dates'        => $dates,
                'totals'       => array_map('intval', $totals),
                'pendings'     => array_map('intval', $pendings),
                'delivers'     => array_map('intval', $delivers),
                'par_delivers' => array_map('intval', $par_delivers),
                'returns'      => array_map('intval', $returns),
            ],
            'pie' => [
                'pending'           => (int) ($piedata['total_pending'] ?? 0),
                'delivered'         => (int) ($piedata['total_delivered'] ?? 0),
                'partial_delivered' => (int) ($piedata['total_partial_delivered'] ?? 0),
                'returned'          => (int) ($piedata['total_return'] ?? 0),
            ],
            'urls' => [
                'filter'            => route('merchant-panel.dashboard.filter'),
                'parcels'           => route('merchant-panel.parcel.index'),
                'parcels_delivered' => route('merchant-panel.parcel.filter', ['parcel_status' => ParcelStatus::DELIVERED]),
                'parcels_returned'  => route('merchant-panel.parcel.filter', ['parcel_status' => ParcelStatus::RETURN_RECEIVED_BY_MERCHANT]),
            ],
            't' => $this->merchantDashboardLabels(),
        ]);
    }

 
}
