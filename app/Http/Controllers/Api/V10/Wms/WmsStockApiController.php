<?php

namespace App\Http\Controllers\Api\V10\Wms;

use App\Http\Controllers\Controller;
use App\Models\Backend\Wms\WmsStock;
use App\Repositories\Wms\WmsStockRepositoryInterface;
use App\Traits\ApiReturnFormatTrait;

class WmsStockApiController extends Controller
{
    use ApiReturnFormatTrait;

    public function __construct(protected WmsStockRepositoryInterface $stock) {}

    /**
     * GET /api/v10/wms/stock/{productId}
     * Real-time per-location stock for a product.
     */
    public function show(int $productId)
    {
        $rows = WmsStock::companywise()
            ->where('product_id', $productId)
            ->with(['location'])
            ->get(['id','location_id','quantity','reserved_qty','batch_number','expiry_date'])
            ->map(fn ($r) => [
                'stock_id'     => $r->id,
                'location_id'  => $r->location_id,
                'location'     => optional($r->location)->code,
                'quantity'     => (int) $r->quantity,
                'reserved'     => (int) $r->reserved_qty,
                'available'    => max(0, $r->quantity - $r->reserved_qty),
                'batch'        => $r->batch_number,
                'expiry'       => (string) $r->expiry_date,
            ]);

        return $this->responseWithSuccess('Stock for product', [
            'product_id' => $productId,
            'on_hand'    => $this->stock->onHand($productId),
            'available'  => $this->stock->available($productId),
            'rows'       => $rows,
        ], 200);
    }
}
