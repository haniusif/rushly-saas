<?php

namespace App\Http\Controllers\Backend\Zatca;

use App\Enums\Zatca\ZatcaInvoiceStatus;
use App\Enums\Zatca\ZatcaInvoiceType;
use App\Http\Controllers\Controller;
use App\Models\Backend\Zatca\ZatcaInvoice;
use App\Repositories\Zatca\ZatcaInvoiceRepositoryInterface;
use App\Services\Zatca\QrGenerator;
use App\Services\Zatca\ZatcaService;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly ZatcaInvoiceRepositoryInterface $repo,
        private readonly ZatcaService $service,
        private readonly QrGenerator $qr,
    ) {}

    public function index(Request $request): Response
    {
        $paginator = $this->repo->paginate($request);
        $stats     = $this->repo->stats();

        $rows = collect($paginator->items())->map(fn (ZatcaInvoice $z) => $this->serialize($z))->values();

        return Inertia::render('Admin/Zatca/Invoices/Index', [
            'rows' => $rows,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
                'total'        => $paginator->total(),
                'prev_url'     => $paginator->previousPageUrl(),
                'next_url'     => $paginator->nextPageUrl(),
            ],
            'filters' => array_merge([
                'status' => '', 'type' => '', 'q' => '', 'date_from' => '', 'date_to' => '',
            ], $request->only(['status', 'type', 'q', 'date_from', 'date_to'])),
            'stats' => $stats,
            'lookups' => [
                'statuses' => collect(ZatcaInvoiceStatus::cases())->map(
                    fn ($c) => ['value' => $c->value, 'label' => $c->label()]
                )->values(),
                'types' => collect(ZatcaInvoiceType::cases())->map(
                    fn ($c) => ['value' => $c->value, 'label' => ZatcaInvoiceType::options()[$c->value]]
                )->values(),
            ],
            'urls' => [
                'index' => route('zatca.invoices.index'),
            ],
            't' => $this->translations(),
        ]);
    }

    public function show(int $id): Response
    {
        $z = $this->repo->findOrFail($id);

        return Inertia::render('Admin/Zatca/Invoices/Show', [
            'invoice' => $this->serialize($z, full: true),
            'urls' => [
                'index'      => route('zatca.invoices.index'),
                'regenerate' => route('zatca.invoices.regenerate', $z->id),
                'pdf'        => route('zatca.invoices.pdf', $z->id),
                'qr'         => route('zatca.invoices.qr', $z->id),
            ],
            't' => $this->translations(),
        ]);
    }

    public function regenerate(int $id): \Illuminate\Http\RedirectResponse
    {
        $z = $this->repo->findOrFail($id);
        try {
            $this->service->regenerate($z);
            Toastr::success(__('zatca.regenerate_ok'));
        } catch (\Throwable $e) {
            $this->service->markFailed($z, $e->getMessage());
            Toastr::error($e->getMessage());
        }
        return back();
    }

    public function qr(int $id): HttpResponse
    {
        $z = $this->repo->findOrFail($id);
        $svg = $this->qr->svg((string) $z->qr_payload, 6);
        return response($svg, 200, [
            'Content-Type'        => 'image/svg+xml',
            'Content-Disposition' => 'inline; filename="' . $z->invoice_number . '-qr.svg"',
        ]);
    }

    public function pdf(int $id): HttpResponse
    {
        $z = $this->repo->findOrFail($id);
        $setting = \App\Models\Backend\Zatca\ZatcaSetting::query()
            ->where('company_id', $z->company_id)->first();

        $pdf = LaravelMpdf::loadView('backend.admin.zatca.invoice_pdf', [
            'z'       => $z,
            'setting' => $setting,
            'qrSvg'   => $this->qr->svg((string) $z->qr_payload, 4),
        ], [], [
            'mode'              => 'utf-8',
            'format'            => 'A4',
            'autoScriptToLang'  => true,
            'autoLangToFont'    => true,
            'default_font_size' => 10,
        ]);

        return $pdf->download($z->invoice_number . '.pdf');
    }

    private function serialize(ZatcaInvoice $z, bool $full = false): array
    {
        $data = [
            'id'              => $z->id,
            'uuid'            => $z->uuid,
            'invoice_number'  => $z->invoice_number,
            'invoice_type'    => $z->invoice_type,
            'type_label'      => ZatcaInvoiceType::options()[$z->invoice_type] ?? $z->invoice_type,
            'status'          => $z->status,
            'status_label'    => $z->statusEnum()->label(),
            'status_color'    => $z->statusEnum()->color(),
            'issued_at'       => optional($z->issued_at)->toIso8601String(),
            'buyer_name'      => $z->buyer_name,
            'subtotal'        => (float) $z->subtotal,
            'vat_amount'      => (float) $z->vat_amount,
            'vat_rate'        => (float) $z->vat_rate,
            'total_inclusive' => (float) $z->total_inclusive,
            'currency'        => $z->currency,
            'url'             => route('zatca.invoices.show', $z->id),
        ];

        if ($full) {
            $data['qr_payload']    = $z->qr_payload;
            $data['qr_image_url']  = $z->qr_image_path
                ? Storage::disk('public')->url($z->qr_image_path) : null;
            $data['hash']          = $z->hash;
            $data['previous_hash'] = $z->previous_hash;
            $data['sequence']      = $z->sequence;
            $data['error_message'] = $z->error_message;
            $data['generated_at']  = optional($z->generated_at)->toIso8601String();
            $data['buyer_vat_number'] = $z->buyer_vat_number;
            $data['buyer_address']    = $z->buyer_address;
        }

        return $data;
    }

    private function translations(): array
    {
        return [
            'title'           => __('zatca.invoices_title'),
            'list'            => __('zatca.list'),
            'invoice_number'  => __('zatca.invoice_number'),
            'buyer'           => __('zatca.buyer'),
            'type'            => __('zatca.type'),
            'status'          => __('zatca.status'),
            'subtotal'        => __('zatca.subtotal'),
            'vat_amount'      => __('zatca.vat_amount'),
            'total_inclusive' => __('zatca.total_inclusive'),
            'issued_at'       => __('zatca.issued_at'),
            'actions'         => __('zatca.actions'),
            'view'            => __('zatca.view'),
            'regenerate'      => __('zatca.regenerate'),
            'download_pdf'    => __('zatca.download_pdf'),
            'download_qr'     => __('zatca.download_qr'),
            'filter'          => __('zatca.filter'),
            'clear'           => __('zatca.clear'),
            'search'          => __('zatca.search'),
            'no_rows'         => __('zatca.no_rows'),
            'total_invoices'  => __('zatca.stat_total'),
            'generated'       => __('zatca.stat_generated'),
            'failed'          => __('zatca.stat_failed'),
            'vat_collected'   => __('zatca.stat_vat'),
            'date_from'       => __('zatca.date_from'),
            'date_to'         => __('zatca.date_to'),
            'all_statuses'    => __('zatca.all_statuses'),
            'all_types'       => __('zatca.all_types'),
            'qr_preview'      => __('zatca.qr_preview'),
            'tlv_payload'     => __('zatca.tlv_payload'),
        ];
    }
}
