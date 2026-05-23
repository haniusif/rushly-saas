<?php

namespace App\Http\Controllers\Api\V10\Wms;

use App\Http\Controllers\Controller;
use App\Repositories\Wms\WmsProductRepositoryInterface;
use App\Repositories\Wms\WmsStockRepositoryInterface;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;

class WmsProductApiController extends Controller
{
    use ApiReturnFormatTrait;

    public function __construct(
        protected WmsProductRepositoryInterface $repo,
        protected WmsStockRepositoryInterface $stock
    ) {}

    /**
     * GET /api/v10/wms/products/lookup?barcode=... or ?sku=...
     * Used by the scanner app to identify a product instantly.
     */
    public function lookup(Request $request)
    {
        $product = null;
        if ($request->filled('barcode')) {
            $product = $this->repo->findByBarcode((string) $request->input('barcode'));
        } elseif ($request->filled('sku')) {
            $product = $this->repo->findBySku((string) $request->input('sku'));
        } else {
            return $this->responseWithError('Provide ?barcode=… or ?sku=…', [], 422);
        }

        if (!$product) {
            return $this->responseWithError('Product not found', [], 404);
        }

        return $this->responseWithSuccess('Product found', [
            'product' => [
                'id'            => $product->id,
                'sku'           => $product->sku,
                'name'          => $product->name,
                'barcode'       => $product->barcode,
                'unit'          => $product->unit,
                'reorder_point' => $product->reorder_point,
                'merchant_id'   => $product->merchant_id,
                'hub_id'        => $product->hub_id,
                'on_hand'       => $this->stock->onHand($product->id),
                'available'     => $this->stock->available($product->id),
            ],
        ], 200);
    }
}
