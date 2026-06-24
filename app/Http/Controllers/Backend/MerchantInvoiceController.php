<?php

namespace App\Http\Controllers\Backend;
 
use App\Exports\InvoiceExport;
use App\Http\Controllers\Controller;
use App\Models\Backend\InvoiceParcel;
use App\Repositories\Invoice\InvoiceInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Maatwebsite\Excel\Facades\Excel;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf;
use Inertia\Inertia;

class MerchantInvoiceController extends Controller
{
    protected $repo;
    public function __construct(InvoiceInterface $repo){
        $this->repo = $repo;
    }
    public function index($merchantId){
        $paginator = $this->repo->merchantInvoiceGet($merchantId);
        $m = \App\Models\Backend\Merchant::with('user')->find($merchantId);
        if (!$m) abort(404);

        // BUG FIX: $paginator is a LengthAwarePaginator. Iterating it via
        // collect()->map() walked toArray() keys ('current_page', 'data', ...),
        // so $inv was sometimes an int and $inv->id crashed. Always feed
        // ->items() into the projector.
        $rows = collect($paginator->items())->map(fn ($inv) => [
            'id'              => $inv->id,
            'invoice_id'      => $inv->invoice_id,
            'invoice_date'    => $inv->invoice_date,
            'cash_collection' => (float) ($inv->cash_collection ?? 0),
            'total_charge'    => (float) ($inv->total_charge ?? 0),
            'current_payable' => (float) ($inv->current_payable ?? 0),
            'status'          => (int) $inv->status,
            'urls' => [
                'details' => route('merchant.invoice.details', ['merchant_id' => $merchantId, 'invoice_id' => $inv->invoice_id]),
                'pdf'     => route('merchant.invoice.pdf',     ['merchant_id' => $merchantId, 'invoice_id' => $inv->invoice_id]),
                'csv'     => route('merchant.invoice.csv',     ['merchant_id' => $merchantId, 'invoice_id' => $inv->invoice_id]),
            ],
        ])->values();

        return Inertia::render('Admin/Merchant/Invoice/Index', [
            'merchant' => [
                'id'            => $m->id,
                'business_name' => $m->business_name,
                'unique_id'     => $m->merchant_unique_id,
                'name'          => optional($m->user)->name,
                'image'         => optional($m->user)->image,
            ],
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
            'currency'    => settings()->currency,
            'permissions' => [
                'status_update' => hasPermission('invoice_status_update'),
            ],
            'urls' => [
                'view'          => route('merchant.view', $m->id),
                'index'         => route('merchant.invoice.index', $m->id),
                'status_update' => route('merchant.invoice.status.update', $m->id),
            ],
            't' => [
                'title'           => 'Invoices',
                'title_index'     => 'Merchants',
                'invoice_id'      => __('invoice.id') ?: 'Invoice ID',
                'invoice_date'    => __('levels.date') ?: 'Date',
                'cash_collection' => __('parcel.cash_collection') ?: 'Cash collection',
                'total_charge'    => __('parcel.Total_Charge') ?: 'Total charge',
                'current_payable' => __('parcel.current_payable') ?: 'Current payable',
                'status'          => __('parcel.status') ?: 'Status',
                'actions'         => __('levels.actions') ?: 'Actions',
                'view'            => __('levels.view') ?: 'View',
                'pdf'             => 'PDF',
                'csv'             => 'CSV',
                'mark_paid'       => 'Mark paid',
                'no_rows'         => 'No invoices for this merchant.',
                'back_to_view'    => 'Back to merchant',
                'status_paid'     => __('invoice.3') ?: 'Paid',
                'status_unpaid'   => __('invoice.0') ?: 'Unpaid',
                'status_processing'=> __('invoice.2') ?: 'Processing',
            ],
        ]);
    }

    public function InvoiceDetails($merchantId,$invoiceId){
        $invoice = $this->repo->merchantInvoiceDetails($merchantId,$invoiceId);
        $invoiceParcels = InvoiceParcel::where('invoice_id',$invoice->id)->paginate(10);
        return view('backend.merchant.invoice.invoice_details', compact('invoice','invoiceParcels'));
    }

    public function StatusUpdate(Request $request,$merchant_id){

        if($this->repo->statusUpdate($request,$merchant_id)):
            Toastr::success(__('invoice.status_updated'),__('message.success'));
            return redirect()->back();
        else:
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        endif;
    }
 

    public function InvoicePdf($merchant_id, $invoice_id)
    {
        $invoice = $this->repo->InvoicePdf($merchant_id, $invoice_id);
        if (!$invoice) {
            Toastr::error(__('parcel.error_msg'), __('message.error'));
            return redirect()->back();
        }

        $pdf = LaravelMpdf::loadView('backend.merchant.invoice.invoice_pdf', compact('invoice'), [], [
            'mode'              => 'utf-8',
            'format'            => 'A4',
            'autoScriptToLang'  => true,
            'autoLangToFont'    => true,
            'default_font_size' => 10,
        ]);

        return $pdf->download('invoice-' . $invoice->merchant->business_name . '-' . $invoice->invoice_date . '.pdf');
    }

    public function InvoiceCSV($merchant_id,$invoice_id){
       
        if($this->repo->invoiceGet($merchant_id,$invoice_id)):
            $invoice = $this->repo->invoiceGet($merchant_id,$invoice_id);
            $invoiceParcels = InvoiceParcel::where('invoice_id',$invoice->id)->get();  
            return Excel::download(new InvoiceExport($invoiceParcels,$invoice),'invoice-'.$invoice->merchant->business_name.'-'.$invoice->invoice_date.'.xlsx');
        else:
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        endif; 
    }
 
    public function InvoiceGenerateMenuallyIndex(){
        return view('backend.setting.invoice_generate.index');
    }

    public function InvoiceGenerateMenually(){
        try {
            Artisan::call('invoice:generate');
            Toastr::success(__('invoice.invoice_generated_successfully'),__('message.success'));
            return redirect()->back();
        } catch (\Throwable $th) {
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }


    public function PaidInvoice(Request $request){
        $shape = function ($inv) {
            return [
                'id'              => $inv->id,
                'merchant_name'   => optional($inv->merchant)->business_name,
                'merchant_email'  => optional(optional($inv->merchant)->user)->email,
                'invoice_id'      => $inv->invoice_id,
                'invoice_date'    => $inv->invoice_date,
                'cash_collection' => (float) ($inv->cash_collection ?? 0),
                'total_charge'    => (float) ($inv->total_charge ?? 0),
                'current_payable' => (float) ($inv->current_payable ?? 0),
                'status'          => (int) $inv->status,
                'status_label'    => trans('invoice.' . $inv->status) ?: (string) $inv->status,
            ];
        };

        $paid    = $this->repo->getPaidInvoices();
        $process = $this->repo->getProcessInvoices();
        $unpaid  = $this->repo->getUnpaidInvoices();

        $meta = fn ($p) => [
            'current_page' => $p->currentPage(),
            'last_page'    => $p->lastPage(),
            'from'         => $p->firstItem(),
            'to'           => $p->lastItem(),
            'total'        => $p->total(),
            'prev_url'     => $p->previousPageUrl(),
            'next_url'     => $p->nextPageUrl(),
        ];

        return \Inertia\Inertia::render('Admin/PaidInvoice/Index', [
            'tabs' => [
                'paid'       => ['rows' => collect($paid->items())->map($shape)->values(),    'pagination' => $meta($paid)],
                'processing' => ['rows' => collect($process->items())->map($shape)->values(), 'pagination' => $meta($process)],
                'unpaid'     => ['rows' => collect($unpaid->items())->map($shape)->values(),  'pagination' => $meta($unpaid)],
            ],
            'currency' => settings()->currency,
            'urls'     => ['index' => route('paid.invoice.index')],
            't' => [
                'title'           => __('parcel.paid_invoice') ?: 'Paid invoices',
                'list'            => __('levels.list') ?: 'List',
                'tab_paid'        => __('invoice.3') ?: 'Paid',
                'tab_processing'  => __('invoice.2') ?: 'Processing',
                'tab_unpaid'      => __('invoice.0') ?: 'Unpaid',
                'merchant'        => __('merchant.title') ?: 'Merchant',
                'invoice_id'      => (__('menus.invoice') ?: 'Invoice') . ' ' . (__('invoice.id') ?: 'ID'),
                'invoice_date'    => (__('menus.invoice') ?: 'Invoice') . ' ' . (__('levels.date') ?: 'date'),
                'cash_collection' => __('parcel.cash_collection') ?: 'Cash collection',
                'total_charge'    => __('parcel.Total_Charge') ?: 'Total charge',
                'current_payable' => __('parcel.current_payable') ?: 'Current payable',
                'status'          => __('parcel.status') ?: 'Status',
                'no_rows'         => 'No invoices.',
                'prev'            => 'Prev',
                'next'            => 'Next',
                'showing_results' => 'Showing :from – :to of :total',
            ],
        ]);
    }
}
