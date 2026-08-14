<?php

namespace App\Http\Controllers\Backend;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Http\Requests\HubManage\Payment\ProcessRequest;
use App\Http\Requests\HubManage\Payment\StoreRequest;
use App\Http\Requests\HubManage\Payment\UpdateRequest;
use App\Models\Backend\Account;
use App\Models\Backend\HubPayment;
use App\Models\Backend\Merchant;
use App\Models\Backend\Payment;
use App\Models\MerchantPayment;
use App\Repositories\Account\AccountInterface;
use App\Repositories\Hub\HubInterface;
use App\Repositories\HubManage\HubPayment\HubPaymentInterface;
use App\Repositories\Merchant\MerchantInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Ramsey\Uuid\Type\Decimal;
use Brian2694\Toastr\Facades\Toastr;
use Inertia\Inertia;
class HubPaymentController extends Controller
{

   protected $hub;
   protected $account;
   protected $payment;

    public function __construct(
        AccountInterface $account,
        HubInterface $hub,
        HubPaymentInterface $payment
        )
    {
            $this->account  = $account;
            $this->payment  = $payment;
            $this->hub     = $hub;
    }
    public function index(){
        $paginator = $this->payment->all();

        $gatewayLabel = ['1' => 'Cash', '2' => 'Bank', '3' => 'Bkash', '4' => 'Rocket', '5' => 'Nagad'];

        $rows = collect($paginator->items())->map(function ($p) use ($gatewayLabel) {
            $fp = $p->frompayment ?? null;
            $details = ['kind' => null];
            if ($fp) {
                $g = (int) $fp->gateway;
                $details['kind']    = $gatewayLabel[$g] ?? 'Other';
                $details['gateway'] = $g;
                if ($g === 1) {
                    // Cash — no extra fields
                } elseif ($g === 2) {
                    $details['account_holder'] = $fp->account_holder_name;
                    $details['account_no']     = $fp->account_no;
                    $details['branch']         = $fp->branch_name;
                } elseif (in_array($g, [3, 4, 5], true)) {
                    $details['account_holder'] = $fp->account_holder_name;
                    $details['mobile']         = $fp->mobile;
                    $details['account_type']   = (int) $fp->account_type === 1 ? 'Merchant' : 'Personal';
                }
            }

            return [
                'id'             => $p->id,
                'details'        => $details,
                'transaction_id' => $p->transaction_id,
                'reference_url'  => optional($p->referencefile)->original
                    ? static_asset($p->referencefile->original) : null,
                'description'    => $p->description,
                'amount'         => (float) $p->amount,
                'status'         => (int) $p->status,
                'urls' => [
                    'reject'         => route('hub-payment.reject',         $p->id),
                    'process'        => route('hub-payment.process',        $p->id),
                    'cancel_reject'  => route('hub-payment.cancel-reject',  $p->id),
                    'cancel_process' => route('hub-payment.cancel-process', $p->id),
                    'edit'           => route('hub.hub-payment.edit',       $p->id),
                    'delete'         => route('hub.hub-payment.delete',     $p->id),
                ],
            ];
        })->values();

        return Inertia::render('Admin/HubPayment/Index', [
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
                'create'  => hasPermission('hub_payment_create'),
                'reject'  => hasPermission('hub_payment_reject'),
                'process' => hasPermission('hub_payment_process'),
                'update'  => hasPermission('hub_payment_update'),
                'delete'  => hasPermission('hub_payment_delete'),
            ],
            'currency' => settings()->currency,
            'urls' => [
                'index'  => route('hub.hub-payment.index'),
                'create' => route('hub.hub-payment.create'),
            ],
            't' => [
                'title'           => __('hub_payment.title') ?: 'Hub payments',
                'list'            => __('levels.list') ?: 'List',
                'add'             => __('levels.add') ?: 'Add',
                'edit'            => __('levels.edit') ?: 'Edit',
                'delete'          => __('levels.delete') ?: 'Delete',
                'actions'         => __('levels.actions') ?: 'Actions',
                'details'         => __('hub_payment.details') ?: 'Details',
                'transaction_id'  => __('hub_payment.transaction_id') ?: 'Transaction ID',
                'reference'       => __('hub_payment.reference') ?: 'Reference',
                'description'     => __('hub_payment.description') ?: 'Description',
                'amount'          => __('hub_payment.amount') ?: 'Amount',
                'status'          => __('levels.status') ?: 'Status',
                'download'        => 'Download',
                'process'         => __('levels.process') ?: 'Process',
                'reject'          => __('levels.reject') ?: 'Reject',
                'cancel_process'  => __('levels.cancel_process') ?: 'Cancel process',
                'cancel_reject'   => __('levels.cancel_reject') ?: 'Cancel reject',
                'delete_confirm'  => __('delete.hub_payment') ?: 'Delete this payment?',
                'no_rows'         => 'No payment requests found',
                'showing_results' => 'Showing :from – :to of :total',
                'status_pending'  => __('approvalstatus.1') ?: 'Pending',
                'status_processed'=> __('approvalstatus.4') ?: 'Processed',
                'status_rejected' => __('approvalstatus.3') ?: 'Rejected',
            ],
        ]);
    }

    public function create(){
        $hubs        = $this->hub->all();
        $accounts   = $this->account->all();
        return view('backend.hub_payment.create',compact('hubs','accounts'));
    }


    //payment store
    public function paymentStore(StoreRequest $request){

        if($request->isprocess):
            $courier_account = Account::find($request->from_account);
            if((double) $request->amount > $courier_account->balance){
                Toastr::warning(__('hub_payment.not_enough_courier_balance'),__('message.warning'));
                return back()->withInput();
            }
        endif;

        if($this->payment->store($request)){
            Toastr::success(__('hub_payment.added_msg'),__('message.success'));
            return redirect()->route('hub.hub-payment.index');
        }else{
            Toastr::error(__('hub_payment.error_msg'),__('message.error'));
            return Redirect::back()->withInput();
        }

    }

    //edit
    public function edit($id){
        $singlePayment    = $this->payment->get($id);
        $hubs             = $this->hub->all();
        $accounts         = $this->account->all();
        return view('backend.hub_payment.edit',compact('singlePayment','hubs','accounts'));
    }

    public function update(UpdateRequest $request,$id){

        //courier account balance check
        if($request->isprocess):
            $courier_account = Account::find($request->from_account);
            if((double) $request->amount > $courier_account->balance){
                Toastr::warning(__('hub_payment.not_enough_courier_balance'),__('message.warning'));
                return back()->withInput();
            }
        endif;

        if($this->payment->update($id,$request)){
            Toastr::success(__('hub_payment.update_msg'),__('message.success'));
            return redirect()->route('hub.hub-payment.index');
        }else{
            Toastr::error(__('hub_payment.error_msg'),__('message.error'));
            return Redirect::back()->withInput();
        }
    }

    public function destroy($id){
        $this->payment->delete($id);
        Toastr::success(__('hub_payment.delete_msg'),__('message.success'));
        return back();
    }


    //process section
    public function reject($id){
        if($this->payment->reject($id)){
            Toastr::success(__('hub_payment.rejected_msg'),__('message.success'));
            return redirect()->back();
        }else{
            Toastr::error(__('hub_payment.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    public function cancelReject($id){
        if($this->payment->cancelReject($id)){
            Toastr::success(__('hub_payment.cancel_rejected_msg'),__('message.success'));
            return redirect()->back();
        }else{
            Toastr::error(__('hub_payment.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    public function process($id){

        try {
            $payment  = HubPayment::findOrFail($id);
            $accounts = $this->account->all();
            return view('backend.hub_payment.process',compact('payment','accounts'));
        } catch (\Exception $exception){
            return redirect()->back();
        }

    }

    public function cancelProcess($id){
        if($this->payment->cancelProcess($id)){
            Toastr::success(__('hub_payment.cancel_processed_msg'),__('message.success'));
            return redirect()->back();
        }else{
            Toastr::error(__('hub_payment.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    public function processed(ProcessRequest $request){
        $payment                    = HubPayment::where('id',$request->id)->first();
        $courier_account            = Account::find($request->from_account);
        if((double) $payment->amount > $courier_account->balance){
            Toastr::warning(__('hub_payment.not_enough_courier_balance'),__('message.warning'));
            return back()->withInput();
        }

        if($this->payment->processed($request)){
            Toastr::success(__('hub_payment.processed_msg'),__('message.success'));
            return redirect()->route('hub.hub-payment.index');
        }else{
            Toastr::error(__('hub_payment.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }
}
