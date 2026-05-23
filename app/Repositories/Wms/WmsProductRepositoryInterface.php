<?php

namespace App\Repositories\Wms;

use App\Models\Backend\Wms\WmsProduct;
use Illuminate\Http\Request;

interface WmsProductRepositoryInterface
{
    public function all(?Request $request = null);
    public function find(int $id): ?WmsProduct;
    public function findBySku(string $sku): ?WmsProduct;
    public function findByBarcode(string $barcode): ?WmsProduct;
    public function create(array $data): WmsProduct;
    public function update(WmsProduct $p, array $data): bool;
    public function delete(WmsProduct $p): bool;
    public function lowStock(): \Illuminate\Support\Collection;
}
