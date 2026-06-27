<?php

namespace App\Http\Controllers\Backend;

use App\Exports\Performance\PerformanceExcelExport;
use App\Http\Controllers\Controller;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Hub;
use App\Models\Backend\Merchant;
use App\Models\Backend\SupplierCompany;
use App\Services\Performance\AiInsightsService;
use App\Services\Performance\CustomerPerformanceService;
use App\Services\Performance\DriverPerformanceService;
use App\Services\Performance\HubPerformanceService;
use App\Services\Performance\KpiAggregator;
use App\Services\Performance\OperatingCompanyPerformanceService;
use App\Services\Performance\PerformanceFilters;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf;

class PerformanceDashboardController extends Controller
{
    public function __construct(
        protected KpiAggregator $kpis,
        protected DriverPerformanceService $drivers,
        protected CustomerPerformanceService $customers,
        protected HubPerformanceService $hubs,
        protected OperatingCompanyPerformanceService $companies,
        protected AiInsightsService $insights,
    ) {}

    /** Inertia page render — also handles polling refresh via query string. */
    public function index(Request $request): Response
    {
        $filters = PerformanceFilters::fromRequest($request);
        $payload = $this->buildPayload($filters);

        return Inertia::render('Admin/Performance/Index', $payload + [
            'filters' => $filters->toArray(),
            'options' => $this->filterOptions(),
            'urls'    => [
                'refresh'      => route('performance.data'),
                'export_excel' => route('performance.export.excel'),
                'export_pdf'   => route('performance.export.pdf'),
            ],
        ]);
    }

    /** Lightweight JSON endpoint for live refresh polling — no Inertia roundtrip. */
    public function data(Request $request): JsonResponse
    {
        $filters = PerformanceFilters::fromRequest($request);
        return response()->json($this->buildPayload($filters) + ['filters' => $filters->toArray()]);
    }

    /** Excel export — same service layer => identical numbers. */
    public function exportExcel(Request $request)
    {
        $filters = PerformanceFilters::fromRequest($request);
        $p = $this->buildPayload($filters);

        return Excel::download(
            new PerformanceExcelExport(
                $filters,
                $p['kpi'], $p['drivers'], $p['customers'], $p['hubs'], $p['companies']
            ),
            'performance-'.$filters->from->toDateString().'_to_'.$filters->to->toDateString().'.xlsx'
        );
    }

    /** PDF export — renders a print-friendly Blade then streams via mPDF. */
    public function exportPdf(Request $request)
    {
        $filters = PerformanceFilters::fromRequest($request);
        $data = $this->buildPayload($filters) + ['filters' => $filters];

        $pdf = LaravelMpdf::loadView('backend.performance.print', $data);
        return $pdf->stream('performance-'.$filters->from->toDateString().'.pdf');
    }

    /** Shared payload assembled once per request and reused for Inertia / JSON / exports. */
    private function buildPayload(PerformanceFilters $filters): array
    {
        $kpi       = $this->kpis->all($filters);
        $drivers   = $this->drivers->payload($filters);
        $customers = $this->customers->payload($filters);
        $hubs      = $this->hubs->payload($filters);
        $companies = $this->companies->payload($filters);
        $insights  = $this->insights->payload($filters, $drivers, $customers, $hubs, $companies, $kpi);

        return compact('kpi', 'drivers', 'customers', 'hubs', 'companies', 'insights');
    }

    private function filterOptions(): array
    {
        return [
            'drivers' => DeliveryMan::companywise()
                ->with('user:id,name')
                ->get()
                ->map(fn ($d) => ['id' => $d->id, 'name' => $d->user?->name ?? ('Driver #' . $d->id)])
                ->values(),
            'hubs' => Hub::companywise()->select('id', 'name')->orderBy('name')->get(),
            'merchants' => Merchant::companywise()->select('id', 'business_name')->orderBy('business_name')->limit(500)->get()
                ->map(fn ($m) => ['id' => $m->id, 'name' => $m->business_name]),
            'supplier_companies' => SupplierCompany::query()
                ->where('company_id', settings()->id)
                ->select('id', 'name')->orderBy('name')->get(),
            'delivery_types' => [
                ['id' => 1, 'name' => 'Same-day'],
                ['id' => 2, 'name' => 'Next-day'],
                ['id' => 3, 'name' => 'Sub-city'],
                ['id' => 4, 'name' => 'Out-of-city'],
            ],
        ];
    }
}
