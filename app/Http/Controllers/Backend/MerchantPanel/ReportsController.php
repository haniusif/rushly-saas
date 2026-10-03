<?php

namespace App\Http\Controllers\Backend\MerchantPanel;

use App\Enums\AccountHeads;
use App\Enums\ParcelStatus;
use App\Enums\StatementType;
use App\Http\Controllers\Controller;
use App\Repositories\Reports\TotalSummeryReport\TotalSummeryReportInterface;
use App\Repositories\MerchantPanel\MerchantParcel\MerchantParcelInterface;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Inertia\Inertia;

class ReportsController extends Controller
{
    protected $repo;
    protected $merchant_parcel_repo;
    public function __construct(TotalSummeryReportInterface $repo , MerchantParcelInterface $merchant_parcel_repo){
        $this->repo = $repo;
        $this->merchant_parcel_repo = $merchant_parcel_repo;
    }

    public function TotalSummeryReports(Request $request)
    {
        return $this->renderTotalSummery($request, [
            'parcelsTotal' => $this->zeroParcelsTotal(),
            'parcelProfit' => $this->zeroParcelProfit(),
            'merchantTotalPayment' => ['paidAmount' => 0, 'pendingAmount' => 0],
            'has_data' => false,
        ]);
    }



public function parcelFinanceReports(Request $request)
{
    
      $merchant                                          = Auth::user()->merchant;
      
       
      $current_balance =  $merchant->current_balance;

     
 

    $from = Carbon::now()->startOfMonth();
    $to   = Carbon::now()->endOfMonth();

    $paginate = 500;

    $parcels = $this->merchant_parcel_repo->parcel_by_daterange(
        $merchant->id,
        $from,
        $to,
        $paginate
    );

    return view('backend.merchant_panel.reports.parcel_finance', compact('parcels' , 'current_balance'));
}

    
    public function TotalSummeryReportsFilter(Request $request){

        $merchant                                          = Auth::user()->merchant;
        $totalParcels                                      = $this->repo->merchantparcelTotalSummeryReports($request);
        $accounts                                       = Auth::user()->accounts;
        $parcelsStatus                                  = $totalParcels->groupBy('status');
        $parcelsMerchant                                = $totalParcels->groupBy('merchant_id');
        $parcels                                        = $totalParcels;
        $parcelsDelivered                               = $totalParcels->where('status',ParcelStatus::DELIVERED);
        $parcelsPartialDelivered                        = $totalParcels->where('partial_delivered',1);
        $parcelsTotal['totalBankOpeningBalance']        = $accounts->sum('opening_balance');
        $parcelsTotal['totalBankBalance']               = $accounts->sum('balance');
        $parcelsTotal['totalPaybleAmount']              = 0;
        $parcelsTotal['totalCashCollection']            = 0;
        $parcelsTotal['totalSellingPrice']              = 0;
        $parcelsTotal['totalDeliveryIncome']            = 0;
        $parcelsTotal['totalDeliveryExpense']           = 0;
        $parcelProfit['totalDeliveryChargeVat']         = 0;
        $parcelProfit['totalDeliveryCharge']            = 0;
        $parcelProfit['totalCOD']                       = 0;
        $parcelProfit['totalVat']                       = 0;
        $parcelProfit['totalLiquidFragileAmount']       = 0;
        $parcelProfit['packagingAmount']                = 0;

        $merchantID = [];
        foreach ($parcelsMerchant as $key => $value){
            $merchantID[]= $key;
        }
        $merchantTotalPayment                       = merchantPayments($merchantID);
        $parcelsTotal['totalCashCollection']        = $parcelsDelivered->sum('cash_collection')+$parcelsPartialDelivered->sum('cash_collection');
        $parcelsTotal['totalPaybleAmount']          = $parcelsDelivered->sum('current_payable')+$parcelsPartialDelivered->sum('current_payable');
        $parcelsTotal['totalSellingPrice']          = $parcelsDelivered->sum('selling_price')+$parcelsPartialDelivered->sum('selling_price');

        foreach ($parcels as $parcel){
            if(!blank($parcel->deliverymanStatement)){
                $parcelProfit['totalDeliveryChargeVat']     += $parcel->total_delivery_amount;
                $parcelProfit['totalDeliveryCharge']        += $parcel->delivery_charge;
                $parcelProfit['totalCOD']                   += $parcel->cod_amount;
                $parcelProfit['totalVat']                   += $parcel->vat_amount;
                $parcelProfit['totalLiquidFragileAmount']   += $parcel->liquid_fragile_amount;
                $parcelProfit['packagingAmount']            += $parcel->packaging_amount;

                foreach ($parcel->deliverymanStatement as $deliveryStatement){
                    if($deliveryStatement->type == StatementType::INCOME){
                        $parcelsTotal['totalDeliveryIncome'] += $deliveryStatement->amount;
                    }else {
                        $parcelsTotal['totalDeliveryExpense'] += $deliveryStatement->amount;
                    }
                }

            }
        }

        return $this->renderTotalSummery($request, [
            'parcelsTotal' => $parcelsTotal,
            'parcelProfit' => $parcelProfit,
            'merchantTotalPayment' => $merchantTotalPayment,
            'has_data' => true,
        ]);
    }

    private function zeroParcelsTotal(): array
    {
        return [
            'totalBankOpeningBalance' => 0,
            'totalBankBalance'        => 0,
            'totalPaybleAmount'       => 0,
            'totalCashCollection'     => 0,
            'totalSellingPrice'       => 0,
            'totalDeliveryIncome'     => 0,
            'totalDeliveryExpense'    => 0,
        ];
    }

    private function zeroParcelProfit(): array
    {
        return [
            'totalDeliveryChargeVat'  => 0,
            'totalDeliveryCharge'     => 0,
            'totalCOD'                => 0,
            'totalVat'                => 0,
            'totalLiquidFragileAmount'=> 0,
            'packagingAmount'         => 0,
        ];
    }

    private function renderTotalSummery(Request $request, array $data)
    {
        $totals = $data['parcelsTotal'];
        $profit = $data['parcelProfit'];
        $pay    = $data['merchantTotalPayment'];

        $netProfit = ($totals['totalCashCollection'] ?? 0) - ($totals['totalSellingPrice'] ?? 0);

        return Inertia::render('Merchant/Reports/TotalSummery', [
            'currency'   => settings()->currency,
            'has_data'   => (bool) ($data['has_data'] ?? false),
            'filters'    => [
                'date' => $request->date,
            ],
            'profit'     => [
                'delivery_charge'  => (float) $profit['totalDeliveryCharge'],
                'cod'              => (float) $profit['totalCOD'],
                'vat'              => (float) $profit['totalVat'],
                'liquid_fragile'   => (float) $profit['totalLiquidFragileAmount'],
                'packaging'        => (float) $profit['packagingAmount'],
                'net_profit'       => (float) $netProfit,
                'delivery_charge_vat' => (float) $profit['totalDeliveryChargeVat'],
            ],
            'totals'     => [
                'cash_collection'   => (float) $totals['totalCashCollection'],
                'selling_price'     => (float) $totals['totalSellingPrice'],
                'payable_amount'    => (float) $totals['totalPaybleAmount'],
                'bank_opening'      => (float) $totals['totalBankOpeningBalance'],
                'bank_balance'      => (float) $totals['totalBankBalance'],
            ],
            'payments' => [
                'paid_amount'       => (float) ($pay['paidAmount'] ?? 0),
                'pending_amount'    => (float) ($pay['pendingAmount'] ?? 0),
            ],
            'urls' => [
                'filter' => route('merchant.parcel.filter.total.summery'),
                'reset'  => route('merchant.total.summery'),
            ],
            't' => [
                'title'             => __('menus.total_summery') ?: 'Total summary',
                'dashboard'         => __('levels.dashboard') ?: 'Dashboard',
                'reports'           => __('menus.reports') ?: 'Reports',
                'date'              => __('parcel.date') ?: 'Date',
                'date_ph'           => __('merchantPlaceholder.date') ?: 'YYYY-MM-DD To YYYY-MM-DD',
                'filter'            => __('levels.filter') ?: 'Filter',
                'clear'             => __('levels.clear') ?: 'Clear',
                'apply_filter_hint' => __('levels.apply_filter') ?: 'Apply a date filter to see totals.',
                'delivery_charge'   => __('dashboard.total_delivery_charge') ?: 'Total delivery charge',
                'cod'               => __('dashboard.total_cod_amount') ?: 'Total COD amount',
                'vat'               => __('dashboard.total_vat_amount') ?: 'Total VAT amount',
                'liquid_fragile'    => __('dashboard.total_liquid_fragile_amount') ?: 'Total liquid/fragile',
                'packaging'         => __('dashboard.total_packaging_amount') ?: 'Total packaging',
                'net_profit'        => __('dashboard.net_profit_ammount') ?: 'Net profit',
                'cash_collection'   => __('dashboard.total_cash_collection') ?: 'Cash collection',
                'selling_price'     => __('dashboard.total_selling_price') ?: 'Selling price',
                'payable_amount'    => __('dashboard.payable_amount') ?: 'Payable amount',
                'paid_amount'       => __('dashboard.paid_amount') ?: 'Paid amount',
                'pending_amount'    => __('dashboard.pending_amount') ?: 'Pending amount',
                'delivery_charge_vat'=> __('dashboard.total_delivery_charge_vat') ?: 'Delivery charge + VAT',
                'bank_opening'      => __('dashboard.bank_opening_balance') ?: 'Bank opening balance',
                'bank_balance'      => __('dashboard.bank_balance') ?: 'Bank balance',
                'group_profit'      => __('dashboard.parcels_profit') ?: 'Parcels profit',
                'group_sales'       => __('dashboard.parcels_sales') ?: 'Parcels sales',
                'group_payments'    => __('dashboard.payments') ?: 'Payments',
                'group_accounts'    => __('dashboard.accounts') ?: 'Accounts',
            ],
        ]);
    }

}
