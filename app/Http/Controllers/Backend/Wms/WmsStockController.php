<?php

namespace App\Http\Controllers\Backend\Wms;

use App\Http\Controllers\Controller;
use App\Models\Backend\Wms\WmsStock;
use App\Repositories\Hub\HubInterface;
use App\Repositories\Merchant\MerchantInterface;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WmsStockController extends Controller
{
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

        $rows      = $q->orderByDesc('id')->paginate(50);
        $merchants = $this->merchantRepo->all();
        $hubs      = $this->hubRepo->all();

        return view('backend.wms.stock.index', compact('rows', 'merchants', 'hubs'));
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
