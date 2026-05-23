<?php

namespace App\Repositories\Wms;

use App\Models\Backend\Wms\WmsProduct;
use Illuminate\Http\Request;

class WmsProductRepository implements WmsProductRepositoryInterface
{
    public function all(?Request $request = null)
    {
        $q = WmsProduct::companywise()->with(['merchant', 'hub']);
        if ($request) {
            if ($request->filled('merchant_id')) $q->where('merchant_id', $request->input('merchant_id'));
            if ($request->filled('hub_id'))      $q->where('hub_id', $request->input('hub_id'));
            if ($request->filled('q'))           $q->where(function ($w) use ($request) {
                $t = $request->input('q');
                $w->where('name', 'like', "%$t%")
                  ->orWhere('sku', 'like', "%$t%")
                  ->orWhere('barcode', 'like', "%$t%");
            });
            if ($request->filled('low_stock'))   $q->whereHas('stocks', fn ($s) => $s->groupBy('product_id')->havingRaw('SUM(quantity) <= wms_products.reorder_point'));
        }
        return $q->latest('id')->paginate(25);
    }

    public function find(int $id): ?WmsProduct
    {
        return WmsProduct::companywise()->with(['merchant', 'hub', 'stocks.location'])->find($id);
    }

    public function findBySku(string $sku): ?WmsProduct
    {
        return WmsProduct::companywise()->where('sku', $sku)->first();
    }

    public function findByBarcode(string $barcode): ?WmsProduct
    {
        return WmsProduct::companywise()->where('barcode', $barcode)->first();
    }

    public function create(array $data): WmsProduct
    {
        $data['company_id'] = $data['company_id'] ?? settings()->id;
        return WmsProduct::create($data);
    }

    public function update(WmsProduct $p, array $data): bool
    {
        return (bool) $p->update($data);
    }

    public function delete(WmsProduct $p): bool
    {
        return (bool) $p->delete();
    }

    public function lowStock(): \Illuminate\Support\Collection
    {
        return WmsProduct::companywise()
            ->with('stocks')
            ->get()
            ->filter(fn (WmsProduct $p) => $p->isLowStock())
            ->values();
    }
}
