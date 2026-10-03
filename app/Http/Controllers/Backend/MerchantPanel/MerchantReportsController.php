<?php

namespace App\Http\Controllers\Backend\MerchantPanel;

use App\Enums\ParcelStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Backend\Parcel;
use App\Repositories\Reports\ReportsInterface;
use Inertia\Inertia;

class MerchantReportsController extends Controller
{
    protected $hub;
    protected $repo;
    public function __construct(ReportsInterface $repo)
    {
        $this->repo = $repo;
    }

    public function parcelReports(Request $request)
    {
        return $this->renderParcelReports($request, null, '');
    }

    public function parcelSReports(Request $request)
    {
        $parcels = $this->repo->merchantParcelReports($request);
        if (! $parcels) {
            return redirect()->back();
        }
        $parcelIds = '';
        foreach ($parcels as $group) {
            foreach ($group as $p) {
                $parcelIds = $p->id . ',' . $parcelIds;
            }
        }
        return $this->renderParcelReports($request, $parcels, rtrim($parcelIds, ','));
    }

    private function renderParcelReports(Request $request, $parcels, string $parcelIds)
    {
        $statusLabels = (array) config('rxparcelStatus.status_list');

        $rows = [];
        $totalCount = 0;
        $totalCash  = 0.0;
        if ($parcels) {
            $i = 1;
            foreach ($parcels as $statusKey => $group) {
                $cash = 0;
                foreach ($group as $p) {
                    $cash += (float) ($p->cash_collection ?? 0);
                }
                $rows[] = [
                    'serial' => $i++,
                    'status' => (int) $statusKey,
                    'status_label' => $statusLabels[$statusKey] ?? (string) $statusKey,
                    'count'  => count($group),
                    'cash'   => $cash,
                ];
                $totalCount += count($group);
                $totalCash  += $cash;
            }
        }

        $statusOptions = collect($statusLabels)
            ->map(fn ($label, $key) => ['value' => (string) $key, 'label' => (string) $label])
            ->values();

        return Inertia::render('Merchant/Reports/ParcelReports', [
            'rows'         => $rows,
            'totals'       => ['count' => $totalCount, 'cash' => $totalCash],
            'currency'     => settings()->currency,
            'has_data'     => (bool) $parcels,
            'parcel_ids'   => $parcelIds,
            'filters'      => [
                'parcel_date'   => $request->parcel_date,
                'parcel_status' => array_values((array) $request->parcel_status),
            ],
            'lookups'      => [
                'statuses' => $statusOptions,
            ],
            'urls' => [
                'filter' => route('merchant-panel.parcel.filter.reports'),
                'reset'  => route('merchant-panel.parcel.reports'),
                'print'  => $parcelIds ? route('merchant-panel.parcel.reports.print.page', $parcelIds) : null,
            ],
            't' => [
                'title'             => __('menus.parcel_reports') ?: 'Parcel reports',
                'dashboard'         => __('levels.dashboard') ?: 'Dashboard',
                'reports'           => __('menus.reports') ?: 'Reports',
                'date'              => __('parcel.date') ?: 'Date',
                'date_ph'           => __('merchantPlaceholder.date') ?: 'YYYY-MM-DD ~ YYYY-MM-DD',
                'status'            => __('parcel.status') ?: 'Status',
                'status_ph'         => __('merchantPlaceholder.status') ?: 'All statuses',
                'filter'            => __('levels.filter') ?: 'Filter',
                'clear'             => __('levels.clear') ?: 'Clear',
                'print'             => __('levels.print') ?: 'Print',
                'serial'            => '#',
                'count'             => __('reports.count') ?: 'Count',
                'cash_collection'   => __('parcel.cash_collection') ?: 'Cash collection',
                'total'             => __('levels.total') ?: 'Total',
                'empty'             => __('levels.no_data_found') ?: 'Apply a filter to see a parcel report.',
            ],
        ]);
    }
    public function parcelReportsPrint(Request $request,$array){
        $parcel_ids  = [];
        foreach (explode(',',$array) as  $id) {
            if($id !== ""):
            $parcel_ids [] = $id;
            endif;
        }
        $parcels    = Parcel::companywise()->whereIn('id',$parcel_ids)->orderBy('id')->get();
        $parcels    = $parcels->groupBy('status');
        return view('backend.merchant_panel.reports.parcel_reports_print',compact('parcels'));
    }
}
