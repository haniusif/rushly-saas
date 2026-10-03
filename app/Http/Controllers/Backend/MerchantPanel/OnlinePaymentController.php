<?php

namespace App\Http\Controllers\Backend\MerchantPanel;

use App\Enums\PaymentType;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\Backend\Account;
use App\Models\Backend\Merchant;
use App\Models\Backend\MerchantOnlinePayment;
use App\Models\Backend\MerchantOnlinePaymentReceived;
use App\Repositories\Account\AccountInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Stripe;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
use Srmklive\PayPal\Services\ExpressCheckout;
use Inertia\Inertia;
class OnlinePaymentController extends Controller
{

    public function __construct(MerchantOnlinePayment $MOPModel,MerchantOnlinePaymentReceived $MOPRmodel,AccountInterface $accountRepo){
        $this->MOPModel = $MOPModel;
        $this->MOPRmodel = $MOPRmodel;
        $this->accountRepo = $accountRepo;
    }
    //start stripe payment gateway
    public function merchantPaymentReceived()
    {
        $paginator = $this->MOPRmodel->where('merchant_id', Auth::user()->merchant->id)
            ->orderByDesc('id')
            ->paginate(10);
        $i = (($paginator->currentPage() - 1) * $paginator->perPage()) + 1;

        $gatewayLabels = [
            1 => 'Cash',
            2 => 'Bank',
            3 => 'Bkash',
            4 => 'Rocket',
            5 => 'Nagad',
        ];

        $rows = collect($paginator->items())->map(function ($p) use (&$i, $gatewayLabels) {
            $a = $p->account;
            $accountLines = [];
            if ($a) {
                $gw = (int) ($a->gateway ?? 0);
                if ($gw === 1) {
                    $accountLines[] = (optional($a->user)->name ?? '') . ' (' . ($gatewayLabels[$gw] ?? 'Cash') . ')';
                } elseif ($gw === 2) {
                    if ($a->account_holder_name) $accountLines[] = $a->account_holder_name;
                    if ($a->account_no)          $accountLines[] = $a->account_no;
                    if ($a->branch_name)         $accountLines[] = $a->branch_name;
                } elseif ($gw > 0) {
                    $accountLines[] = $gatewayLabels[$gw] ?? ('Gateway ' . $gw);
                    if ($a->mobile)       $accountLines[] = $a->mobile;
                    if ($a->account_type) $accountLines[] = $a->account_type;
                }
            }
            return [
                'serial'         => $i++,
                'id'             => $p->id,
                'card_type'      => __('PaymentType.' . $p->payment_type) ?: (string) $p->payment_type,
                'account_lines'  => $accountLines,
                'transaction_id' => $p->transaction_id,
                'amount'         => (float) ($p->amount ?? 0),
            ];
        })->values();

        return Inertia::render('Merchant/PaymentReceived/Index', [
            'rows'       => $rows,
            'currency'   => settings()->currency,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
                'links'        => $paginator->linkCollection()->map(fn ($l) => [
                    'url'    => $l['url'],
                    'label'  => $l['label'],
                    'active' => (bool) $l['active'],
                ])->values(),
            ],
            't' => [
                'title'          => __('menus.payment_received') ?: 'Payments received',
                'list'           => __('levels.list') ?: 'List',
                'dashboard'      => __('levels.dashboard') ?: 'Dashboard',
                'id'             => __('levels.id') ?: 'ID',
                'card_type'      => __('levels.card_type') ?: 'Card type',
                'from_account'   => __('levels.from_account') ?: 'From account',
                'transaction_id' => __('levels.transaction_id') ?: 'Transaction ID',
                'amount'         => __('levels.amount') ?: 'Amount',
                'empty'          => __('levels.no_data_found') ?: 'No payments received yet.',
            ],
        ]);
    }
    //payout list
    public function index(){
        $oPayments   = $this->MOPModel->where(['merchant_id'=>Auth::user()->merchant->id])->orderByDesc('id')->paginate(10);
        return view('backend.merchant_panel.onlinepayment.payment_list',compact('oPayments'));
    }
    public function stripe(){
        $accounts    = $this->accountRepo->getAll();
        return view('backend.merchant_panel.onlinepayment.stripe',compact('accounts'));
    }
    public function stripePost(Request $request){
        \Config([
            'services.stripe.secret'        => globalSettings('stripe_secret_key'),
        ]);

        $stripe = Stripe::charges()->create([
            'source' => $request->get('tokenId'),
            'currency' => 'BDT',
            'amount' => $request->get('amount')
        ]);
        $paymnet = DB::table('merchant_online_payments')
        ->updateOrInsert([
            'company_id'     => settings()->id,
            'payment_type'   => PaymentType::STRIPE,
            'amount'         => $request->get('amount'),
            'note'           => 'Payment',
            'transaction_id' => $request->get('tokenId'),
            'merchant_id'    => Auth::user()->merchant->id,
            'account_id'     => $request->account_id
        ]);

        $merchant                   = Merchant::find(Auth::user()->merchant->id);
        $merchant->current_balance  =($merchant->current_balance - $request->get('amount'));
        $merchant->save();
        $account                   = Account::find($request->account_id);
        $account->balance          = ($account->balance + $request->get('amount'));
        $account->save();
        return response()->json(['success'=>true],200);
    }

    //Start Paypal Payment Gateway
    public function paypalIndex(){
        $accounts    = $this->accountRepo->getAll();
        return view('backend.merchant_panel.onlinepayment.paypal',compact('accounts'));
    }
    public function paypalpayment(Request $request)
    {
        try {
            $payment = DB::table('merchant_online_payments')
            ->updateOrInsert([
                'company_id'     => settings()->id,
                'payment_type'   => PaymentType::PAYPAL,
                'amount'         => $request->get('amount'),
                'note'           => 'Payment',
                'transaction_id' => $request->get('orderID'),
                'merchant_id'    => Auth::user()->merchant->id,
                'account_id'     => $request->account_id
            ]);
            $merchant                   = Merchant::find(Auth::user()->merchant->id);
            $merchant->current_balance  =($merchant->current_balance - $request->get('amount'));
            $merchant->save();

            $account                   = Account::find($request->account_id);
            $account->balance          = ($account->balance + $request->get('amount'));
            $account->save();
            return response()->json(['success' => true, 'data'=>[] ],200);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'data'=>[] ],500);
        }

    }

    public function sslcommerzIndex(){
        $accounts    = $this->accountRepo->getAll();
        return view('backend.merchant_panel.onlinepayment.sslcommerz',compact('accounts'));
    }

    public function aamarpayIndex(){
        $accounts    = $this->accountRepo->getAll();
        return view('backend.merchant_panel.onlinepayment.aamarpay',compact('accounts'));
    }
}
