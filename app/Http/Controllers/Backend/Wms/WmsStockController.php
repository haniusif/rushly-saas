<?php

namespace App\Http\Controllers\Backend\Wms;

use App\Http\Controllers\Backend\Wms\Concerns\RendersInertiaIndex;
use App\Http\Controllers\Controller;
use App\Models\Backend\Wms\WmsStock;
use App\Repositories\Hub\HubInterface;
use App\Repositories\Merchant\MerchantInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WmsStockController extends Controller
{
    use RendersInertiaIndex;

    public function __construct(
        protected MerchantInterface $merchantRepo,
        protected HubInterface $hubRepo
    ) {}

    public function index(Request $request)
    {
        $q = WmsStock::companywise()->with(['product.merchant', 'location.hub']);

        if ($request->filled('merchant_id')) {
            $q->whereHas('product', fn ($p) => $p->where('merchant_id', $request->input('merchant_id')));
        }
        if ($request->filled('hub_id')) {
            $q->whereHas('location', fn ($l) => $l->where('hub_id', $request->input('hub_id')));
        }
        if ($request->filled('q')) {
            $t = $request->input('q');
            $q->whereHas('product', fn ($p) => $p->where(function ($w) use ($t) {
                $w->where('name', 'like', "%$t%")->orWhere('sku', 'like', "%$t%");
            }));
        }
        if ($request->boolean('low_only')) {
            // Filter after the fact — small enough scope and avoids a subquery on aggregated columns.
        }

        $paginator = $q->orderByDesc('id')->paginate(50);
        $merchants = $this->merchantRepo->all();
        $hubs      = $this->hubRepo->all();

        $rows = collect($paginator->items())->map(function ($s) {
            $available = max(0, (int) $s->quantity - (int) $s->reserved_qty);
            $reorderPoint = optional($s->product)->reorder_point ?? 0;
            return [
                'id'            => $s->id,
                'sku'           => optional($s->product)->sku,
                'product_id'    => optional($s->product)->id,
                'product_name'  => optional($s->product)->name,
                'merchant'      => optional(optional($s->product)->merchant)->business_name,
                'location_id'   => optional($s->location)->id,
                'location_code' => optional($s->location)->code,
                'hub'           => optional(optional($s->location)->hub)->name,
                'quantity'      => (int) $s->quantity,
                'reserved'      => (int) $s->reserved_qty,
                'available'     => $available,
                'low'           => $reorderPoint > 0 && $available <= $reorderPoint,
                'batch'         => $s->batch_number,
                'expiry'        => optional($s->expiry_date)->format('Y-m-d'),
            ];
        })->values();

        return Inertia::render('Admin/Wms/Stock/Index', [
            'rows'        => $rows,
            'pagination'  => $this->paginateMeta($paginator),
            'filters'     => [
                'q'           => $request->input('q', ''),
                'merchant_id' => $request->input('merchant_id', ''),
                'hub_id'      => $request->input('hub_id', ''),
                'low_only'    => $request->boolean('low_only'),
            ],
            'lookups'     => [
                'merchants' => $this->lookupRows($merchants, fn ($m) => ['id' => $m->id, 'name' => $m->business_name]),
                'hubs'      => $this->lookupRows($hubs, fn ($h) => ['id' => $h->id, 'name' => $h->name]),
            ],
            'urls' => [
                'index'  => route('wms.stock.index'),
                'export' => route('wms.stock.export'),
            ],
            't' => $this->indexLabels([
                'title' => 'Stock', 'sku' => 'SKU', 'product' => 'Product', 'merchant' => 'Merchant',
                'location' => 'Location', 'hub' => 'Hub',
                'qty' => 'Qty', 'reserved' => 'Reserved', 'available' => 'Available',
                'batch' => 'Batch', 'expiry' => 'Expiry', 'low_only' => 'Low only', 'export' => 'Export CSV',
                'search' => 'Search SKU / product',
            ]),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filename = 'stock-' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($request) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['stock_id','product_sku','product_name','merchant','hub','location_code','quantity','reserved','available','batch','expiry']);
            $q = WmsStock::companywise()->with(['product.merchant', 'location.hub']);
            if ($request->filled('merchant_id')) {
                $q->whereHas('product', fn ($p) => $p->where('merchant_id', $request->input('merchant_id')));
            }
            if ($request->filled('hub_id')) {
                $q->whereHas('location', fn ($l) => $l->where('hub_id', $request->input('hub_id')));
            }
            $q->orderBy('id')->chunk(500, function ($chunk) use ($out) {
                foreach ($chunk as $r) {
                    fputcsv($out, [
                        $r->id,
                        optional($r->product)->sku,
                        optional($r->product)->name,
                        optional($r->product?->merchant)->business_name,
                        optional($r->location?->hub)->name,
                        optional($r->location)->code,
                        $r->quantity,
                        $r->reserved_qty,
                        max(0, $r->quantity - $r->reserved_qty),
                        $r->batch_number,
                        $r->expiry_date,
                    ]);
                }
            });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
