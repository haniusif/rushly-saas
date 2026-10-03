<?php

namespace App\Http\Controllers;

use App\Http\Requests\Merchantpayment\StoreBankRequest;
use App\Http\Requests\Merchantpayment\StoreMobileRequest;
use Illuminate\Http\Request;
use App\Repositories\Merchant\MerchantInterface;
use App\Repositories\MerchantPayment\PaymentInterface;
use Illuminate\Support\Facades\Redirect;
use Brian2694\Toastr\Facades\Toastr;
use Inertia\Inertia;
class MerchantPaymentAccountController extends Controller
{
    protected $repo;
    protected $payRepo;
    public function __construct(MerchantInterface $repo,PaymentInterface $payRepo)
    {
        $this->repo    = $repo;
        $this->payRepo = $payRepo;
    }
    public function index($id){
        $m = $this->repo->get($id);
        if(blank($m)){ abort(404); }
        $payments = $this->payRepo->get($id);

        $rows = collect($payments)->map(fn ($p) => [
            'id'             => $p->id,
            'method'         => $p->payment_method,
            'method_label'   => __('merchant.' . $p->payment_method) ?: ucwords(str_replace('_', ' ', (string) $p->payment_method)),
            'bank_name'      => $p->bank_name,
            'holder_name'    => $p->holder_name,
            'account_no'     => $p->account_no,
            'branch_name'    => $p->branch_name,
            'routing_no'     => $p->routing_no,
            'mobile_company' => $p->mobile_company,
            'mobile_no'      => $p->mobile_no,
            'account_type'   => $p->account_type,
            'status'         => (int) $p->status,
            'urls' => [
                'edit'   => route('merchant.payment.edit', ['mid' => $m->id, 'id' => $p->id]),
                'delete' => route('merchant.payment.delete', $p->id),
            ],
        ])->values();

        return Inertia::render('Admin/Merchant/Payment/Index', [
            'merchant' => [
                'id'            => $m->id,
                'business_name' => $m->business_name,
                'unique_id'     => $m->merchant_unique_id,
                'name'          => optional($m->user)->name,
                'image'         => optional($m->user)->image,
            ],
            'rows'        => $rows,
            'permissions' => [
                'create' => hasPermission('merchant_payment_create'),
                'update' => hasPermission('merchant_payment_update'),
                'delete' => hasPermission('merchant_payment_delete'),
            ],
            'urls' => [
                'view'   => route('merchant.view', $m->id),
                'index'  => route('merchant.paymentaccount.index', $m->id),
                'create' => route('merchant.payment.add', $m->id),
            ],
            't' => [
                'title'           => 'Payment accounts',
                'title_index'     => 'Merchants',
                'method'          => __('merchant.payment_method') ?: 'Method',
                'account_info'    => __('merchant.account_info') ?: 'Account info',
                'status'          => __('levels.status') ?: 'Status',
                'actions'         => __('levels.actions') ?: 'Actions',
                'active'          => __('merchantshops.active') ?: 'Active',
                'inactive'        => __('merchantshops.inactive') ?: 'Inactive',
                'add'             => __('levels.add') ?: 'Add',
                'edit'            => __('levels.edit') ?: 'Edit',
                'delete'          => __('levels.delete') ?: 'Delete',
                'delete_confirm'  => 'Delete this payment account?',
                'no_rows'         => 'No payment accounts registered.',
                'back_to_view'    => 'Back to merchant',
            ],
        ]);
    }
    public function paymentAdd($id){
        $singleMerchant = $this->repo->get($id);
        $merchant_id    = $id;
        return view('backend.merchant.payment.add_payment',compact('singleMerchant','merchant_id' ));
    }
    public function paymentEdit($mid,$id){
        $singleMerchant = $this->repo->get($mid);
        $paymentInfo    = $this->payRepo->edit($id);
        $merchant_id    = $mid;
        return view('backend.merchant.payment.edit_payment',compact('singleMerchant','merchant_id','paymentInfo'));
    }

    public function paymentChange(Request $request){
        $payment_method = $request->payment_method;
        $merchant_id    = $this->repo->get($request->merchant_id)->id;
        $editid         = $request->editid;
        if($request->payment_method == 'bank'){
            return view('backend.merchant.payment.bank',compact('payment_method','merchant_id' ,'editid'));
        }elseif($request->payment_method == 'mobile'){
            return view('backend.merchant.payment.mobile',compact('payment_method','merchant_id','editid'));
        }elseif($request->payment_method == 'cash'){
            return view('backend.merchant.payment.cash',compact('payment_method','merchant_id','editid'));
        }
    }

    // bank payment information store
    public function bankStore(StoreBankRequest $request){
        if($this->payRepo->bankstore($request)){
            if($request->editid !==null){
                Toastr::success(__('merchant.payment_update_msg'),__('message.success'));
            }else{
                Toastr::success(__('merchant.payment_added_msg'),__('message.success'));
            }
            return redirect()->route('merchant.paymentaccount.index',$request->merchant_id);
        }else{
            Toastr::error(__('merchant.payment_error_msg'),__('message.error'));
            return Redirect::back()->withInput();
        }
    }



    //mobile payment information store
    public function mobileStore(StoreMobileRequest $request){
        if($this->payRepo->mobilestore($request)){
            if($request->editid !==null){
                Toastr::success(__('merchant.payment_update_msg'),__('message.success'));
            }else{
                Toastr::success(__('merchant.payment_added_msg'),__('message.success'));
            }
            return redirect()->route('merchant.paymentaccount.index',$request->merchant_id);
        }else{
            Toastr::error(__('merchant.payment_error_msg'),__('message.error'));
            return Redirect::back()->withInput();
        }
    }


    //update

    public function bankUpdate(StoreBankRequest $request){
        if($this->payRepo->bankupdate($request)){
            Toastr::success(__('merchant.payment_update_msg'),__('message.success'));
            return redirect()->route('merchant.paymentaccount.index',$request->merchant_id);
        }else{
            Toastr::error(__('merchant.payment_error_msg'),__('message.error'));
            return Redirect::back()->withInput();
        }
    }
    public function mobileUpdate(StoreMobileRequest $request){
        if($this->payRepo->mobileupdate($request)){
            Toastr::success(__('merchant.payment_update_msg'),__('message.success'));
            return redirect()->route('merchant.paymentaccount.index',$request->merchant_id);
        }else{
            Toastr::error(__('merchant.payment_error_msg'),__('message.error'));
            return Redirect::back()->withInput();
        }
    }
    public function destroy($id){
        $this->payRepo->delete($id);
        Toastr::success(__('merchant.payment_account_delete_msg'),__('message.success'));
        return back();
    }
}
