<?php

namespace App\Http\Controllers\Backend\MerchantPanel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Backend\Payment;
use App\Models\MerchantPayment;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Inertia\Inertia;

class AccountTransactionController extends Controller
{
    public function index()
    {
        return $this->respond(new Request(), $this->baseQuery()->paginate(10));
    }

    private function baseQuery()
    {
        return Payment::where('merchant_id', Auth::user()->merchant->id)->orderByDesc('id');
    }

    private function respond(Request $request, $paginator)
    {
        $merchantId = Auth::user()->merchant->id;
        $accounts   = MerchantPayment::where('merchant_id', $merchantId)->get();

        $i = (($paginator->currentPage() - 1) * $paginator->perPage()) + 1;

        $rows = collect($paginator->items())->map(function ($tx) use (&$i, $accounts) {
            $account = $accounts->firstWhere('id', $tx->merchant_account);
            $accountLabel = null;
            if ($account) {
                if ($account->payment_method === 'bank') {
                    $accountLabel = trim(($account->branch_name ?? '') . ' (' . ($account->account_no ?? '') . ')');
                } elseif ($account->payment_method === 'mobile') {
                    $accountLabel = trim(($account->mobile_company ?? '') . ' (' . ($account->mobile_no ?? '') . ')');
                }
            }
            return [
                'serial'          => $i++,
                'id'              => $tx->id,
                'account_label'   => $accountLabel,
                'transaction_id'  => $tx->transaction_id,
                'date'            => $tx->created_at ? dateFormat($tx->created_at) : null,
                'status'          => (int) $tx->status,
                'status_label'    => __('Approvalstatus.' . $tx->status) ?: (string) $tx->status,
                'amount'          => (float) ($tx->amount ?? 0),
            ];
        })->values();

        $accountOptions = $accounts->map(function ($a) {
            if ($a->payment_method === 'bank') {
                $label = trim(($a->branch_name ?? '') . ' (' . ($a->account_no ?? '') . ')');
            } elseif ($a->payment_method === 'mobile') {
                $label = trim(($a->mobile_company ?? '') . ' (' . ($a->mobile_no ?? '') . ')');
            } else {
                $label = 'Account #' . $a->id;
            }
            return ['value' => (string) $a->id, 'label' => $label];
        })->values();

        $typeOptions = collect((array) config('rxcourier.approval_status'))
            ->map(fn ($v) => ['value' => (string) $v, 'label' => __('Approvalstatus.' . $v) ?: (string) $v])
            ->values();

        return Inertia::render('Merchant/Accounts/Transactions/Index', [
            'rows'       => $rows,
            'currency'   => settings()->currency,
            'filters'    => [
                'date'    => $request->date,
                'type'    => $request->type,
                'account' => $request->account,
            ],
            'lookups'    => [
                'accounts' => $accountOptions,
                'types'    => $typeOptions,
            ],
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
            'urls' => [
                'filter' => route('merchant.accounts.account-transaction.filter'),
                'reset'  => route('merchant.accounts.account-transaction.index'),
            ],
            't' => [
                'title'         => __('menus.account_transaction') ?: 'Account transactions',
                'list'          => __('levels.list') ?: 'List',
                'dashboard'     => __('levels.dashboard') ?: 'Dashboard',
                'id'            => __('levels.id') ?: 'ID',
                'account'       => __('levels.account') ?: 'Account',
                'account_ph'    => __('merchantPlaceholder.account') ?: 'All accounts',
                'account_details'=> __('paymentrequest.account_details') ?: 'Account',
                'transaction_id'=> __('merchantmanage.transaction_id') ?: 'Transaction ID',
                'request_date'  => __('paymentrequest.request_date') ?: 'Request date',
                'status'        => __('levels.status') ?: 'Status',
                'type'          => __('levels.type') ?: 'Type',
                'type_ph'       => __('merchantPlaceholder.type') ?: 'All types',
                'amount'        => __('merchantmanage.amount') ?: 'Amount',
                'date_filter'   => __('parcel.date') ?: 'Date',
                'date_ph'       => __('merchantPlaceholder.date') ?: 'YYYY-MM-DD To YYYY-MM-DD',
                'filter'        => __('levels.filter') ?: 'Filter',
                'clear'         => __('levels.clear') ?: 'Clear',
                'empty'         => __('levels.no_data_found') ?: 'No transactions found.',
            ],
        ]);
    }

    public function filter(Request $request)
    {
        $id = Auth::user()->merchant->id;
        if($request->date && $request->type == null && $request->account == null) {
            $date = explode('To', $request->date);
            if(is_array($date)) {
                $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
            }
            $transactions = Payment::where('merchant_id',$id)->orderByDesc('id')->whereBetween('created_at', [$from, $to])->paginate(10);
        }

        else if($request->type && $request->date == null && $request->account == null){
            $transactions = Payment::where('merchant_id',$id)->orderByDesc('id')->where('status', $request->type)->paginate(10);
        }

        else if($request->account && $request->type == null && $request->date == null){
            $transactions = Payment::where('merchant_id',$id)->orderByDesc('id')->where('merchant_account',$request->account)->paginate(10);
        }

        else if($request->date && $request->type && $request->account == null) {
            $date = explode('To', $request->date);
            if(is_array($date)) {
                $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
            }
            $transactions = Payment::where('merchant_id',$id)->orderByDesc('id')->whereBetween('created_at', [$from, $to])->where('status',$request->type)->paginate(10);
        }

        else if($request->date == null && $request->type && $request->account) {
            $transactions = Payment::where('merchant_id',$id)->orderByDesc('id')->where('status',$request->type)->where('merchant_account',$request->account)->paginate(10);
        }
        else if($request->date && $request->type == null && $request->account) {
            $date = explode('To', $request->date);
            if(is_array($date)) {
                $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
            }
            $transactions = Payment::where('merchant_id',$id)->orderByDesc('id')->whereBetween('created_at', [$from, $to])->where('merchant_account',$request->account)->paginate(10);
        }
        else if($request->date && $request->type && $request->account) {
            $date = explode('To', $request->date);

            if(is_array($date)) {
                $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
            }
            $transactions = Payment::where('merchant_id',$id)->orderByDesc('id')->whereBetween('created_at', [$from, $to])->where('status',$request->type)->where('merchant_account',$request->account)->paginate(10);
        }
        else{
            $transactions = Payment::where('merchant_id',$id)->orderByDesc('id')->paginate(10);
        }
        return $this->respond($request, $transactions);
    }
}
