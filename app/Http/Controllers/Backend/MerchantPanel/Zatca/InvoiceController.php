<?php

namespace App\Http\Controllers\Backend\MerchantPanel\Zatca;

use App\Http\Controllers\Controller;
use App\Models\Backend\Zatca\ZatcaInvoice;
use App\Models\Backend\Zatca\ZatcaSetting;
use App\Repositories\Zatca\ZatcaInvoiceRepositoryInterface;
use App\Services\Zatca\QrGenerator;
use App\Services\Zatca\ZatcaService;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly ZatcaInvoiceRepositoryInterface $repo,
        private readonly ZatcaService $service,
        private readonly QrGenerator $qr,
    ) {}

    public function index(Request $request): View
    {
        $merchantId = (int) (Auth::user()->merchant_id ?? Auth::user()->id);

        $invoices = $this->repo->paginate($request, $merchantId);
        $stats    = $this->repo->stats($merchantId);

        return view('backend.merchant_panel.zatca.invoices.index', compact('invoices', 'stats'));
    }

    public function show(int $id): View
    {
        $invoice = $this->findOwned($id);
        $qrSvg   = $invoice->qr_payload ? $this->qr->svg((string) $invoice->qr_payload, 5) : null;

        return view('backend.merchant_panel.zatca.invoices.show', compact('invoice', 'qrSvg'));
    }

    public function regenerate(int $id): RedirectResponse
    {
        $invoice = $this->findOwned($id);
        try {
            $this->service->regenerate($invoice);
            Toastr::success(__('zatca.regenerate_ok'));
        } catch (\Throwable $e) {
            $this->service->markFailed($invoice, $e->getMessage());
            Toastr::error($e->getMessage());
        }
        return back();
    }

    public function qr(int $id): HttpResponse
    {
        $invoice = $this->findOwned($id);
        $svg = $this->qr->svg((string) $invoice->qr_payload, 6);
        return response($svg, 200, [
            'Content-Type'        => 'image/svg+xml',
            'Content-Disposition' => 'inline; filename="' . $invoice->invoice_number . '-qr.svg"',
        ]);
    }

    public function pdf(int $id): HttpResponse
    {
        $invoice = $this->findOwned($id);
        $setting = ZatcaSetting::query()->where('company_id', $invoice->company_id)->first();

        $pdf = LaravelMpdf::loadView('backend.admin.zatca.invoice_pdf', [
            'z'       => $invoice,
            'setting' => $setting,
            'qrSvg'   => $this->qr->svg((string) $invoice->qr_payload, 4),
        ], [], [
            'mode'              => 'utf-8',
            'format'            => 'A4',
            'autoScriptToLang'  => true,
            'autoLangToFont'    => true,
            'default_font_size' => 10,
        ]);

        return $pdf->download($invoice->invoice_number . '.pdf');
    }

    private function findOwned(int $id): ZatcaInvoice
    {
        $merchantId = (int) (Auth::user()->merchant_id ?? Auth::user()->id);
        return ZatcaInvoice::query()
            ->companywise()
            ->where('merchant_id', $merchantId)
            ->findOrFail($id);
    }
}
