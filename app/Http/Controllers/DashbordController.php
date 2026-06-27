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



            $data['request']   = $request;
            return view('backend.super-admin.dashboard',$data);
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
            return view('backend.merchant_panel.dashboard',
            compact(
                't_parcel',
                't_delivered',
                't_return',
                't_sale',
                't_delivery_fee',
                'ts_vat',
                't_balance_proc',
                't_balance_paid',
                't_request',
                'merchant',
                't_fraud',
                't_shop',
                't_parcel_bank',

                't_cash_collection',
                't_selling_price',
                't_liquid_fragile',
                't_vat_amount',
                't_delivery_charge',
                't_cod_amount',
                't_packaging',
                't_delivery_amount',
                't_current_payable',

                'dates',
                'totals',
                'pendings',
                'delivers',
                'par_delivers',
                'returns',
                'piedata'
            ));
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
          
               
        return view('backend.merchant_panel.dashboard',
        compact(
            'request',
            'ts_vat',
            'piedata',
            't_parcel',
            't_delivered',
            't_return',
            't_sale',
            't_delivery_fee',
            't_balance_proc',
            't_balance_paid',
            't_request',
            'merchant',
            't_fraud',
            't_shop',
            't_parcel_bank',
            't_cash_collection',
            't_selling_price',
            't_liquid_fragile',
            't_vat_amount',
            't_delivery_charge',
            't_cod_amount',
            't_packaging',
            't_delivery_amount',
            't_current_payable',
            'dates',
            'totals',
            'pendings',
            'delivers',
            'par_delivers',
            'returns',
        ));
    }

 
}
