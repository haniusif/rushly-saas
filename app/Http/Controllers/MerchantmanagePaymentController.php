<?php

namespace App\Http\Controllers;

use App\Enums\ApprovalStatus;
use App\Enums\Status;
use App\Http\Requests\Merchantmanage\Payment\ProcessRequest;
use App\Http\Requests\Merchantmanage\Payment\StoreRequest;
use App\Http\Requests\Merchantmanage\Payment\UpdateRequest;
use App\Models\Backend\Account;
use App\Models\Backend\Parcel;
use App\Models\Backend\Merchant;
use App\Models\Backend\Payment;
use App\Models\MerchantPayment;
use App\Repositories\Account\AccountInterface;
use App\Repositories\Merchant\MerchantInterface;
use App\Repositories\MerchantManage\Payment\PaymentInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Ramsey\Uuid\Type\Decimal;
use Brian2694\Toastr\Facades\Toastr;
use Inertia\Inertia;
class MerchantmanagePaymentController extends Controller
{

   protected $merchant;
   protected $account;
   protected $payment;

    public function __construct(
        MerchantInterface $merchant,
        AccountInterface $account,
        PaymentInterface $payment
        )
    {
            $this->merchant = $merchant;
            $this->account  = $account;
            $this->payment  = $payment;
    }
    public function index(Request $request)
    {
        $hasFilter = $request->filled('date') || $request->filled('merchant_id')
            || $request->filled('merchant_account') || $request->filled('from_account');

        $paginator = $hasFilter ? $this->payment->filter($request) : $this->payment->all();
        $paginator->load(['merchant.user.upload', 'merchantAccount', 'frompayment.user', 'referencefile']);

        $currency = settings()->currency;
        $can = [
            'create'  => hasPermission('payment_create'),
            'update'  => hasPermission('payment_update'),
            'delete'  => hasPermission('payment_delete'),
            'reject'  => hasPermission('payment_reject'),
            'process' => hasPermission('payment_process'),
        ];

        $rows = collect($paginator->items())->map(function (Payment $p) use ($can) {
            $m = $p->merchant;
            $u = optional($m)->user;
            return [
                'id'             => $p->id,
                'merchant'       => [
                    'name'     => optional($u)->name,
                    'email'    => optional($u)->email,
                    'business' => optional($m)->business_name,
                    'image'    => optional($u)->image,
                ],
                'payout_account' => $this->payoutAccountSummary($p->merchantAccount),
                'transaction_id' => $p->transaction_id,
                'created_at'     => optional($p->created_at)->format('d M Y'),
                'from_account'   => $this->fromAccountSummary($p->frompayment),
                'reference_url'  => $p->referencefile ? static_asset($p->referencefile->original) : null,
                'description'    => $p->description,
                'status'         => (int) $p->status,
                'status_label'   => __('approvalstatus.' . $p->status),
                'amount'         => (float) $p->amount,
                'urls'           => [
                    'edit'           => $can['update']  ? route('merchatmanage.payment.edit', $p->id)          : null,
                    'destroy'        => $can['delete']  ? route('merchantmanage.payment.delete', $p->id)       : null,
                    'reject'         => $can['reject']  ? route('merchantmanage.payment.reject', $p->id)       : null,
                    'cancel_reject'  => $can['reject']  ? route('merchantmanage.payment.cancel-reject', $p->id): null,
                    'process'        => $can['process'] ? route('merchantmanage.payment.process', $p->id)      : null,
                    'cancel_process' => $can['process'] ? route('merchantmanage.payment.cancel-process', $p->id): null,
                ],
            ];
        })->values();

        // Every payout account for the tenant, keyed by merchant so the
        // filter can narrow the list client-side when a merchant is picked.
        $merchantAccounts = MerchantPayment::whereIn(
                'merchant_id', Merchant::companywise()->select('id')
            )->get()
            ->map(fn ($a) => [
                'id'          => $a->id,
                'merchant_id' => $a->merchant_id,
                'label'       => $this->payoutAccountSummary($a)['label'],
            ])->values();

        return Inertia::render('Admin/MerchantPayment/Index', [
            'rows'       => $rows,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
                'total'        => $paginator->total(),
                'prev_url'     => $paginator->appends($request->query())->previousPageUrl(),
                'next_url'     => $paginator->appends($request->query())->nextPageUrl(),
            ],
            'total_amount' => (float) collect($paginator->items())->sum('amount'),
            'currency'     => $currency,
            'filters'      => [
                'date'             => $request->input('date', ''),
                'merchant_id'      => (string) $request->input('merchant_id', ''),
                'merchant_account' => (string) $request->input('merchant_account', ''),
                'from_account'     => (string) $request->input('from_account', ''),
            ],
            'lookups' => [
                'merchants'         => $this->merchant->merchantIdlist()->map(fn ($m) => ['id' => $m->id, 'name' => $m->business_name])->values(),
                'merchant_accounts' => $merchantAccounts,
                'from_accounts'     => $this->account->getAll()->map(fn ($a) => ['id' => $a->id, 'label' => $this->fromAccountSummary($a)['label']])->values(),
            ],
            'permissions' => $can,
            'urls' => [
                'index'  => route('merchant.manage.payment.index'),
                'create' => route('merchant-manage.payment.create'),
            ],
            't' => [
                'title'            => __('merchantmanage.merchant_payment_manage') ?: 'Client payments',
                'list'             => __('levels.list') ?: 'List',
                'section'          => __('merchantmanage.title') ?: 'Client manage',
                'add'              => __('levels.add') ?: 'Add',
                'filter'           => __('levels.filter') ?: 'Filter',
                'clear'            => __('levels.clear') ?: 'Clear',
                'all'              => __('levels.all') ?: 'All',
                'date'             => __('parcel.date') ?: 'Date',
                'date_from'        => __('levels.from') ?: 'From',
                'date_to'          => __('levels.to') ?: 'To',
                'merchant'         => __('merchant.title') ?: 'Client',
                'merchant_account' => __('merchantmanage.merchant_account') ?: 'Client account',
                'from_account'     => __('merchantmanage.from_account') ?: 'From account',
                'pick_merchant_first' => __('Select a client to list their accounts') ?: 'Select a client to list their accounts',
                'merchant_details' => __('merchantmanage.merchant_details') ?: 'Client details',
                'payout_account'   => __('merchantmanage.merchant_account') ?: 'Payout account',
                'transaction_id'   => __('merchantmanage.transaction_id') ?: 'Trans. ID',
                'created_at'       => __('levels.created_at') ?: 'Created',
                'reference'        => __('merchantmanage.reference') ?: 'Reference',
                'download'         => __('levels.download') ?: 'Download',
                'description'      => __('merchantmanage.description') ?: 'Description',
                'status'           => __('levels.status') ?: 'Status',
                'amount'           => __('merchantmanage.amount') ?: 'Amount',
                'actions'          => __('levels.actions') ?: 'Actions',
                'page_total'       => __('Total on this page') ?: 'Total on this page',
                'edit'             => __('levels.edit') ?: 'Edit',
                'delete'           => __('levels.delete') ?: 'Delete',
                'delete_confirm'   => __('delete.payment') ?: 'Delete this payment?',
                'reject'           => __('levels.reject') ?: 'Reject',
                'cancel_reject'    => __('levels.cancel_reject') ?: 'Cancel reject',
                'process'          => __('levels.process') ?: 'Process',
                'cancel_process'   => __('levels.cancel_process') ?: 'Cancel processed',
                'confirm_action'   => __('Are you sure?') ?: 'Are you sure?',
                'no_rows'          => __('levels.no_data_found') ?: 'No payments found.',
                'showing_results'  => 'Showing :from – :to of :total',
                'status_pending'   => __('approvalstatus.' . ApprovalStatus::PENDING),
                'status_processed' => __('approvalstatus.' . ApprovalStatus::PROCESSED),
                'status_rejected'  => __('approvalstatus.' . ApprovalStatus::REJECT),
            ],
        ]);
    }

    /** Flat, display-ready summary of a merchant payout account (bank / mobile / cash). */
    protected function payoutAccountSummary(?MerchantPayment $a): ?array
    {
        if (!$a) return null;
        $method = (string) $a->payment_method;
        $lines  = [];
        if ($method === 'bank') {
            $lines = array_values(array_filter([
                $a->holder_name, $a->bank_name, $a->account_no, $a->branch_name,
                $a->routing_no ? 'RTN ' . $a->routing_no : null,
            ]));
        } elseif ($method === 'mobile') {
            $lines = array_values(array_filter([$a->mobile_company, $a->mobile_no, $a->account_type]));
        }
        $methodLabel = __('merchant.' . $method) ?: ucfirst($method);
        return [
            'method'       => $method,
            'method_label' => $methodLabel,
            'lines'        => $lines,
            'label'        => $lines ? implode(' | ', $lines) : $methodLabel,
        ];
    }

    /** Flat, display-ready summary of a courier (from) account. */
    protected function fromAccountSummary(?Account $a): ?array
    {
        if (!$a) return null;
        $gateway = (int) $a->gateway;
        $mobileGateways = [3 => 'Bkash', 4 => 'Rocket', 5 => 'Nagad'];
        if ($gateway === 1) {
            $title = optional($a->user)->name;
            $lines = [__('merchant.cash') ?: 'Cash'];
        } elseif (isset($mobileGateways[$gateway])) {
            $title = $a->account_holder_name;
            $lines = array_values(array_filter([
                $mobileGateways[$gateway], $a->mobile,
                (int) $a->account_type === 1 ? (__('merchant.title') ?: 'Merchant') : (__('placeholder.persional') ?: 'Personal'),
            ]));
        } else {
            $title = $a->account_holder_name;
            $lines = array_values(array_filter([$a->account_no, $a->branch_name]));
        }
        $balance = (__('merchantmanage.current_balance') ?: 'Balance') . ': ' . $a->balance;
        return [
            'title'   => $title,
            'lines'   => $lines,
            'balance' => (float) $a->balance,
            'label'   => implode(' | ', array_filter(array_merge([$title], $lines, [$balance]))),
        ];
    }

    
public function payment_get_cod(Request $request)
{
    $ids = $request->input('ids', []);

    if (!is_array($ids)) {
        $ids = explode(',', $ids);
    }

    $ids = array_filter(array_map('trim', $ids));

    if (empty($ids)) {
        return response()->json([
            'error' => true,
            'message' => 'No shipment IDs provided.'
        ], 400);
    }

    $parcels = Parcel::with('merchant:id,user_id')
        ->whereIn('tracking_id', $ids)
        ->get(['tracking_id', 'merchant_id', 'current_payable']);

    if ($parcels->isEmpty()) {
        return response()->json([
            'error' => true,
            'message' => 'No shipments found.'
        ], 404);
    }

    $merchantIds = $parcels->pluck('merchant_id')->unique();

    // If multiple merchants exist, highlight the difference
    if ($merchantIds->count() > 1) {
        $grouped = $parcels->groupBy('merchant_id')->map(function ($group, $merchant_id) {
            return [
                'merchant_id' => $merchant_id,
                'shipments' => $group->pluck('tracking_id')->values(),
            ];
        })->values();

        return response()->json([
            'error' => true,
            'message' => 'Shipments belong to multiple merchants. Please check the highlighted ones.',
            'grouped' => $grouped,
        ], 400);
    }

    $total_cod = $parcels->sum('current_payable');
    $merchant_id = $merchantIds->first();

    return response()->json([
        'error' => false,
        'total_cod' => number_format($total_cod, 2),
        'merchant_id' => $merchant_id,
    ]);
}





    
    public function create(){
        $merchants = $this->merchant->all();
        $accounts  = $this->account->all();
        return view('backend.merchantmanage.payment.create',compact('merchants','accounts'));
    }

    public function merchantAccount(Request $request){
        $merchantaccounts = MerchantPayment::where('merchant_id',$request->merchant_id)->get();
        $accounts         = "";
        $accounts        .= "<option selected disabled>". __('menus.select').' '.__('merchant.title').' '. __('account.title')."</option>";
        foreach ($merchantaccounts as $account) {
            if($account->payment_method == 'bank'){
                $accounts.="<option value='".$account->id."'>".$account->holder_name.' | '.$account->bank_name.' | '.$account->account_no.' | '.$account->branch_name."</option>";
            }elseif($account->payment_method == 'mobile'){
                $accounts.="<option value='".$account->id."'>".$account->mobile_company.' | '.$account->mobile_no.'|'.$account->account_type."</option>";
            }elseif($account->payment_method == 'cash'){
                $accounts.="<option value='".$account->id."'>".__('merchant.'.$account->payment_method)."</option>";
            }
        }
        return  $accounts;
    }

    public function merchantSearch(Request $request){
        $search         = $request->search;
        if($search == ''){
            $merchants  = [];
        }else{
            $merchants  = Merchant::companywise()->where('status',Status::ACTIVE)->orderby('business_name','asc')->select('id','business_name')->where('business_name', 'like', '%' .$search . '%')->limit(10)->get();
        }
        $response=[];
        foreach($merchants as $merchant){
            $response[] = array(
                "id"    => $merchant->id,
                "text"  => $merchant->business_name,
            );
        }
        return response()->json($response);
    }


    //payment store
    public function paymentStore(StoreRequest $request){
        $account  = Merchant::where('id',$request->merchant)->first();
        $balance = (double) $account->current_balance;
        if((double) $request->amount > $balance){
            Toastr::warning(__('merchantmanage.not_enough_merchant_balance'),__('message.warning'));
            return back()->withInput();
        }
        if($request->isprocess):
            $courier_account = Account::find($request->from_account);
            if((double) $request->amount > $courier_account->balance){

                Toastr::warning(__('merchantmanage.not_enough_courier_balance'),__('message.warning'));
                return back()->withInput();
            }
        endif;
        if($this->payment->store($request)){
            Toastr::success(__('merchantmanage.added_msg'),__('message.success'));
            return redirect()->route('merchant.manage.payment.index');
        }else{
            Toastr::error(__('merchantmanage.error_msg'),__('message.error'));
            return Redirect::back()->withInput();
        }
    }

    //edit
    public function edit($id){
        $singlePayment    = $this->payment->get($id);
        $merchants        = $this->merchant->all();
        $accounts         = $this->account->all();
        $merchantaccounts = MerchantPayment::where('merchant_id',$singlePayment->merchant_id)->get();
        return view('backend.merchantmanage.payment.edit',compact('singlePayment','merchants','accounts','merchantaccounts'));
    }

    public function update(UpdateRequest $request){

        //merchant balance check
        $account  = Merchant::where('id',$request->merchant)->first();
        $balance = (double) $account->current_balance;
        if((double) $request->amount > $balance){
            Toastr::warning(__('merchantmanage.not_enough_merchant_balance'),__('message.warning'));
            return back()->withInput();
        }
        //courier account balance check
        if($request->isprocess):
            $courier_account = Account::find($request->from_account);
            if((double) $request->amount > $courier_account->balance){
                Toastr::warning(__('merchantmanage.not_enough_courier_balance'),__('message.warning'));
                return back()->withInput();
            }
        endif;
        if($this->payment->update($request)){
            Toastr::success(__('merchantmanage.update_msg'),__('message.success'));
            return redirect()->route('merchant.manage.payment.index');
        }else{
            Toastr::error(__('merchantmanage.error_msg'),__('message.error'));
            return Redirect::back()->withInput();
        }
    }
    public function destroy($id){
        $this->payment->delete($id);
        Toastr::success(__('merchantmanage.delete_msg'),__('message.success'));
        return back();
    }
    //process section
    public function reject($id){
        if($this->payment->reject($id)){
            Toastr::success(__('merchantmanage.rejected_msg'),__('message.success'));
            return redirect()->back();
        }else{
            Toastr::error(__('merchantmanage.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }
    public function cancelReject($id){
        if($this->payment->cancelReject($id)){
            Toastr::success(__('merchantmanage.cancel_rejected_msg'),__('message.success'));
            return redirect()->back();
        }else{
            Toastr::error(__('merchantmanage.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }
    public function process($id){
        $payment  = Payment::where('id',$id)->first();
        $accounts = $this->account->all();
        return view('backend.merchantmanage.payment.process',compact('payment','accounts'));
    }

    public function cancelProcess($id){
        if($this->payment->cancelProcess($id)){
            Toastr::success(__('merchantmanage.cancel_processed_msg'),__('message.success'));
            return redirect()->back();
        }else{
            Toastr::error(__('merchantmanage.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    public function processed(ProcessRequest $request){

        $payment                    = Payment::where('id',$request->id)->first();
        $courier_account            = Account::find($request->from_account);
        if((double) $payment->amount > $courier_account->balance){
            Toastr::warning(__('merchantmanage.not_enough_courier_balance'),__('message.warning'));
            return back()->withInput();
        }

        if($this->payment->processed($request)){
            Toastr::success(__('merchantmanage.processed_msg'),__('message.success'));
            return redirect()->route('merchant.manage.payment.index');
        }else{
            Toastr::error(__('merchantmanage.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    public function merchantpaymentFilter(Request $request)
    {
        // Legacy filter URL — the index now applies filters itself.
        return $this->index($request);
    }

}
