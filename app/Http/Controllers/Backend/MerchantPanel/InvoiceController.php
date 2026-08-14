<?php

namespace App\Http\Controllers\Backend\MerchantPanel;

use App\Enums\ParcelStatus;
use App\Http\Controllers\Controller;
use App\Models\Backend\InvoiceParcel;
use App\Models\Backend\Merchantpanel\Invoice;
use App\Models\Backend\Parcel;
use App\Repositories\Invoice\InvoiceInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    protected $repo;
    public function __construct(InvoiceInterface $repo){
        $this->repo = $repo;
    }
    public function index()
    {
        $paginator = $this->repo->get();
        $i = (($paginator->currentPage() - 1) * $paginator->perPage()) + 1;

        $rows = collect($paginator->items())->map(function ($iv) use (&$i) {
            return [
                'serial'          => $i++,
                'id'              => $iv->id,
                'invoice_id'      => $iv->invoice_id,
                'invoice_date'    => $iv->invoice_date,
                'cash_collection' => (float) ($iv->cash_collection ?? 0),
                'total_charge'    => (float) ($iv->total_charge ?? 0),
                'current_payable' => (float) ($iv->current_payable ?? 0),
                'status'          => (int) ($iv->status ?? 0),
                'status_label'    => strip_tags((string) $iv->my_status),
                'details_url'     => route('merchant.panel.invoice.details', $iv->invoice_id),
                'csv_url'         => route('merchant.panel.invoice.csv', [$iv->merchant_id, $iv->invoice_id]),
                'pdf_url'         => route('merchant.panel.invoice.pdf', [$iv->merchant_id, $iv->invoice_id]),
            ];
        })->values();

        return Inertia::render('Merchant/Invoice/Index', [
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
                'title'           => __('menus.invoice') ?: 'Invoice',
                'list'            => __('levels.list') ?: 'List',
                'dashboard'       => __('levels.dashboard') ?: 'Dashboard',
                'id'              => __('levels.id') ?: 'ID',
                'invoice_id'      => (__('menus.invoice') . ' ' . __('invoice.id')) ?: 'Invoice ID',
                'invoice_date'    => (__('menus.invoice') . ' ' . __('levels.date')) ?: 'Invoice date',
                'cash_collection' => __('parcel.cash_collection') ?: 'Cash collection',
                'total_charge'    => __('parcel.Total_Charge') ?: 'Total charge',
                'current_payable' => __('parcel.current_payable') ?: 'Current payable',
                'status'          => __('parcel.status') ?: 'Status',
                'actions'         => __('levels.actions') ?: 'Actions',
                'view'            => __('levels.view') ?: 'View',
                'csv'             => 'CSV',
                'pdf'             => 'PDF',
                'empty'           => __('levels.no_data_found') ?: 'No invoices yet.',
            ],
        ]);
    }

    public function InvoiceDetails($invoiceId){

        $invoice = $this->repo->InvoiceDetails($invoiceId);
        $invoiceParcels = InvoiceParcel::where('invoice_id',$invoice->id)->paginate(10);
        return view('backend.merchant_panel.invoice.invoice_details', compact('invoice','invoiceParcels'));
    }

}
