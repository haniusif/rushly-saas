<?php

namespace App\Http\Controllers\Backend\MerchantPanel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\StoreRequest;
use App\Repositories\Wallet\WalletInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{
    protected $repo;
    public function __construct(WalletInterface $repo)
    {
        $this->repo   = $repo;
    }
    public function index(Request $request){
        $wallets                  = $this->repo->get($request);
        $recharge_transactions    = $this->repo->recharges($request);
        return view('backend.merchant_panel.mywallet.index',compact('wallets','request','recharge_transactions'));
    }
    public function recharge(){
        return view('backend.merchant_panel.mywallet.recharge');
    }

    public function rechargeAdd(Request $request){
         $validator = Validator::make($request->all(),[
            'amount'   => ['required','numeric','gt:0']
         ]);
        
         if($validator->fails()): 
            Toastr::error($validator->errors()->first(),__('message.error'));
            return redirect()->back()->withErrors($validator->errors());
         endif;
     
        if($wallet = $this->repo->store($request)):
            // $this->repo->approved($wallet->id);
            Toastr::success(__('parcel.wallet_addedd_successfully'),__('message.success'));
            return redirect()->back();
        endif;

        Toastr::error(__('parcel.error_msg'),__('message.error'));
        return redirect()->back();
    }
 
    //admin panel
    public function requestIndex(Request $request){
        $wallets               = $this->repo->get($request);
        $recharge_transactions = $this->repo->recharges($request);

        $rowShape = function ($w) {
            return [
                'id'             => $w->id,
                'merchant_name'  => optional($w->merchant)->business_name,
                'merchant_phone' => optional(optional($w->merchant)->user)->mobile,
                'merchant_addr'  => optional($w->merchant)->address,
                'user_image'     => optional($w->user)->image,
                'created_at'     => $w->created_at ? dateFormat($w->created_at) : null,
                'transaction_id' => $w->transaction_id,
                'payment_method' => $w->payment_method,
                'payment_method_label' => trans('WalletPaymentMethod.' . $w->payment_method) ?: $w->payment_method,
                'amount'         => (float) $w->amount,
                'type'           => (int) $w->type,
                'status'         => (int) $w->status,
                'status_label'   => $this->walletStatusLabel((int) $w->status),
            ];
        };

        return \Inertia\Inertia::render('Admin/Wallet/Index', [
            'rows_all' => collect($wallets->items())->map($rowShape)->values(),
            'rows_recharge' => collect($recharge_transactions->items())->map($rowShape)->values(),
            'pagination_all' => [
                'current_page' => $wallets->currentPage(),
                'last_page'    => $wallets->lastPage(),
                'from'         => $wallets->firstItem(),
                'to'           => $wallets->lastItem(),
                'total'        => $wallets->total(),
                'prev_url'     => $wallets->previousPageUrl(),
                'next_url'     => $wallets->nextPageUrl(),
            ],
            'pagination_recharge' => [
                'current_page' => $recharge_transactions->currentPage(),
                'last_page'    => $recharge_transactions->lastPage(),
                'from'         => $recharge_transactions->firstItem(),
                'to'           => $recharge_transactions->lastItem(),
                'total'        => $recharge_transactions->total(),
                'prev_url'     => $recharge_transactions->previousPageUrl(),
                'next_url'     => $recharge_transactions->nextPageUrl(),
            ],
            'summary' => [
                'total_recharge'   => (float) \App\Models\Backend\Wallet::companywise()->where('type', \App\Enums\Wallet\WalletType::INCOME)->sum('amount'),
                'total_deductions' => (float) \App\Models\Backend\Wallet::companywise()->where('type', \App\Enums\Wallet\WalletType::EXPENSE)->sum('amount'),
                'count_pending'    => \App\Models\Backend\Wallet::companywise()->where('status', \App\Enums\Wallet\WalletStatus::PENDING)->count(),
                'count_approved'   => \App\Models\Backend\Wallet::companywise()->where('status', \App\Enums\Wallet\WalletStatus::APPROVED)->count(),
                'count_rejected'   => \App\Models\Backend\Wallet::companywise()->where('status', \App\Enums\Wallet\WalletStatus::REJECTED)->count(),
            ],
            'filters' => [
                'date'        => (string) ($request->date ?? ''),
                'status'      => (string) ($request->status ?? ''),
                'merchant_id' => (string) ($request->merchant_id ?? ''),
                'search'      => (string) ($request->search ?? ''),
                'recharge_page' => (bool) $request->recharge_page,
            ],
            'lookups' => [
                'statuses' => $this->walletStatusOptions(),
            ],
            'permissions' => [
                'create'  => hasPermission('wallet_request_create'),
                'approve' => hasPermission('wallet_request_approve'),
                'reject'  => hasPermission('wallet_request_reject'),
                'delete'  => hasPermission('wallet_request_delete'),
            ],
            'currency' => settings()->currency,
            'urls' => [
                'index'    => route('wallet.request.index'),
                'recharge' => route('wallet.request.recharge'),
                'merchant_search' => route('parcel.merchant.get'),
                'approve_base' => url('/admin/wallet-request/approve'),
                'reject_base'  => url('/admin/wallet-request/reject'),
                'delete_base'  => url('/admin/wallet-request/delete'),
            ],
            't' => [
                'title'   => __('parcel.wallet_request') ?: 'Wallet requests',
                'list'    => __('levels.list') ?: 'List',
                'date'    => __('parcel.date') ?: 'Date',
                'status'  => __('parcel.status') ?: 'Status',
                'merchant'=> __('parcel.merchant') ?: 'Merchant',
                'search'  => __('parcel.search') ?: 'Search',
                'filter'  => __('levels.filter') ?: 'Filter',
                'clear'   => __('levels.clear') ?: 'Clear',
                'transaction_id' => __('parcel.transaction_id') ?: 'Transaction ID',
                'payment_method' => __('parcel.payment_method') ?: 'Method',
                'amount'  => __('parcel.amount') ?: 'Amount',
                'actions' => __('levels.actions') ?: 'Actions',
                'all_transactions' => 'All Transactions',
                'recharges' => 'Recharges',
                'total_recharge' => 'Total Recharge',
                'total_deductions' => 'Total Deductions',
                'pending' => 'Pending',
                'confirm' => 'Confirmed',
                'rejected' => 'Rejected',
                'recharge_wallet' => __('parcel.wallet_recharge') ?: 'Recharge Wallet',
                'approve' => __('parcel.approve') ?: 'Approve',
                'reject'  => __('parcel.reject') ?: 'Reject',
                'delete'  => __('levels.delete') ?: 'Delete',
                'add_to_wallet' => __('parcel.add_to_wallet') ?: 'Add to wallet',
                'quick_add' => __('parcel.quick_add') ?: 'Quick add',
                'quick_hint' => __('parcel.quickly_add_money_from_given_options_and_recharge_your_wallet') ?: 'Quickly add money from given options.',
                'approve_confirm' => __('parcel.are_you_approve_this_request') ?: 'Approve this request?',
                'reject_confirm'  => __('parcel.are_you_reject_this_request') ?: 'Reject this request?',
                'delete_confirm'  => __('parcel.delete_wallet') ?: 'Delete this entry?',
                'select_merchant' => (__('parcel.select') ?: 'Select') . ' ' . (__('merchant.title') ?: 'merchant'),
                'no_rows' => 'No transactions yet.',
                'prev' => 'Prev',
                'next' => 'Next',
                'showing_results' => 'Showing :from – :to of :total',
            ],
        ]);
    }

    private function walletStatusLabel(int $s): string
    {
        return match ($s) {
            \App\Enums\Wallet\WalletStatus::PENDING  => 'Pending',
            \App\Enums\Wallet\WalletStatus::APPROVED => 'Approved',
            \App\Enums\Wallet\WalletStatus::REJECTED => 'Rejected',
            default => (string) $s,
        };
    }

    private function walletStatusOptions(): array
    {
        $opts = [];
        foreach ((array) trans('WalletStatus') as $k => $v) {
            $opts[] = ['value' => (string) $k, 'label' => (string) $v];
        }
        return $opts;
    }
 
    public function approve($id){
        if($this->repo->approved($id)):
            Toastr::success(__('parcel.wallet_request_approved_successfully'));
            return redirect()->route('wallet.request.index');
        else:
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        endif;
    }
 
    public function reject($id){
        if($this->repo->rejected($id)):
            Toastr::success(__('parcel.wallet_request_rejected_successfully'));
            return redirect()->route('wallet.request.index');
        else:
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        endif;
    }


    //admin wallet  
 
    public function adminstore(StoreRequest $request)
    {
      
        if($this->repo->adminstore($request)):
            Toastr::success(__('parcel.wallet_recharge_successfully'),__('message.success'));
            return redirect()->route('wallet.request.index');
        else:
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        endif;
    }
    
    public function delete($id)
    {
         
        if($this->repo->delete($id)):
            Toastr::success(__('parcel.wallet_recharge_update_successfully'),__('success'));
            return redirect()->back();
        else:
            Toastr::error(__('error'),__('errors'));
            return redirect()->back();
        endif;
    }


}
