<?php

namespace App\Http\Controllers\Backend;

use App\Enums\LabelTemplate;
use App\Http\Controllers\Controller;
use App\Models\Backend\Merchant;
use App\Services\Label\LabelTemplateResolver;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Mpdf\Mpdf;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class LabelTemplateController extends Controller
{
    public function __construct(private readonly LabelTemplateResolver $resolver) {}

    public function index(Request $request): Response
    {
        $current   = $this->resolver->tenantDefault();
        $paginator = Merchant::query()->companywise()
            ->select('id', 'business_name', 'label_template')
            ->orderBy('business_name')
            ->paginate(20);

        // Shape each template with everything the React page needs so the
        // client doesn't have to reach back for descriptions / previews.
        $templates = collect(LabelTemplate::cases())->map(fn ($tpl) => [
            'value'       => $tpl->value,
            'label'       => $tpl->label(),
            'description' => $tpl->description(),
            'format'      => $tpl->format(), // [width, height] in mm
            'preview_url' => $this->safeRoute('label-templates.preview', $tpl->value, "/admin/settings/label-templates/preview/{$tpl->value}"),
        ])->values();

        $merchants = collect($paginator->items())->map(fn ($m) => [
            'id'             => (int) $m->id,
            'business_name'  => (string) ($m->business_name ?: 'Merchant #'.$m->id),
            'label_template' => (string) ($m->label_template ?: ''),
            'submit_url'     => $this->safeRoute('label-templates.update-merchant', $m->id, "/admin/settings/label-templates/merchant/{$m->id}"),
        ])->values();

        return Inertia::render('Admin/LabelTemplates/Index', [
            'templates'  => $templates,
            'current'    => $current->value,
            'merchants'  => $merchants,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
                'total'        => $paginator->total(),
                'prev_url'     => $paginator->previousPageUrl(),
                'next_url'     => $paginator->nextPageUrl(),
            ],
            'urls' => [
                'update_default' => $this->safeRoute('label-templates.update-default', null, '/admin/settings/label-templates'),
            ],
            't' => [
                'title'                    => __('label_template.title') ?: 'AWB Label Templates',
                'subtitle'                 => __('label_template.subtitle') ?: 'Choose the layout used when printing shipping labels. Merchants can override this per account below.',
                'current'                  => __('label_template.current') ?: 'Current default',
                'save_default'             => __('label_template.save_default') ?: 'Save default',
                'preview'                  => __('label_template.preview') ?: 'Preview',
                'size'                     => __('label_template.size') ?: 'Size',
                'merchant_overrides'       => __('label_template.merchant_overrides') ?: 'Per-merchant overrides',
                'merchant_overrides_hint'  => __('label_template.merchant_overrides_hint') ?: 'When a merchant has a specific template set here, it wins over the default.',
                'merchant'                 => __('label_template.merchant') ?: 'Merchant',
                'override'                 => __('label_template.override') ?: 'Template',
                'actions'                  => __('label_template.actions') ?: 'Actions',
                'use_default'              => __('label_template.use_default') ?: 'Use tenant default',
                'save'                     => __('levels.save') ?: 'Save',
                'saved_default'            => __('label_template.default_saved') ?: 'Default template saved.',
                'no_merchants'             => 'No merchants yet.',
                'prev'                     => 'Prev',
                'next'                     => 'Next',
                'showing_results'          => 'Showing :from – :to of :total',
            ],
        ]);
    }

    private function safeRoute(string $name, $param = null, string $fallback = ''): string
    {
        try { return $param === null ? route($name) : route($name, $param); }
        catch (\Throwable $e) { return $fallback; }
    }

    public function updateDefault(Request $request): RedirectResponse
    {
        $request->validate([
            'default_label_template' => ['required', 'in:' . implode(',', array_column(LabelTemplate::cases(), 'value'))],
        ]);

        $tpl = LabelTemplate::from($request->string('default_label_template'));
        $this->resolver->setTenantDefault($tpl);

        Toastr::success(__('label_template.default_saved'));
        return back();
    }

    public function updateMerchantOverride(Request $request, int $merchantId): RedirectResponse
    {
        $request->validate([
            'label_template' => ['nullable', 'in:' . implode(',', array_column(LabelTemplate::cases(), 'value'))],
        ]);

        $merchant = Merchant::query()->companywise()->findOrFail($merchantId);
        $merchant->forceFill([
            'label_template' => $request->filled('label_template') ? $request->string('label_template') : null,
        ])->save();

        Toastr::success(__('label_template.override_saved'));
        return back();
    }

    /** Render a single sample label so admin can preview each style. */
    public function preview(string $template): HttpResponse
    {
        $tpl = LabelTemplate::tryFrom($template);
        abort_unless($tpl, 404);

        $data = $this->sampleData();
        $mpdfTempDir = storage_path('app/mpdf');
        if (! is_dir($mpdfTempDir)) {
            @mkdir($mpdfTempDir, 0775, true);
        }

        $mpdf = new Mpdf([
            'tempDir' => $mpdfTempDir,
            'format'  => $tpl->format(),
            'margin_left' => 0, 'margin_right' => 0, 'margin_top' => 0, 'margin_bottom' => 0,
        ]);
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont   = true;
        $mpdf->AddPage();
        $mpdf->WriteHTML(view($tpl->view(), compact('data'))->render());

        return response($mpdf->Output("{$tpl->value}-preview.pdf", 'S'), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $tpl->value . '-preview.pdf"',
        ]);
    }

    private function sampleData(): array
    {
        return [
            'totalPages'      => 1,
            'currentPage'     => 1,
            'sender' => [
                'name' => 'Sample Merchant', 'phone' => '+966500000000',
                'addressLine1' => '123 King Fahd Rd', 'addressLine2' => 'Riyadh',
                'country' => 'SA',
            ],
            'receiver' => [
                'name' => 'Mohammed Ali', 'phone' => '+966550000000',
                'addressLine1' => '45 Olaya St', 'addressLine2' => 'Al Olaya',
                'country' => 'SA', 'city' => 'Riyadh',
                'city_code' => 'RUH', 'state' => 'Riyadh',
            ],
            'isCod' => true, 'codAmount' => 350.00,
            'awb' => '8686', 'rushlyAwb' => '8686',
            'date' => date('Y-m-d'),
            'description' => 'Sample preview shipment',
            'orderNumber' => 'ORD-PREVIEW',
            'reference_number' => 'REF-PREVIEW',
        ];
    }
}
