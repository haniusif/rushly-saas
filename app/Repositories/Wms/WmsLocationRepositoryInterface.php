<?php

namespace App\Repositories\Wms;

use App\Models\Backend\Wms\WmsLocation;
use Illuminate\Http\Request;

interface WmsLocationRepositoryInterface
{
    public function all(?Request $request = null);
    public function find(int $id): ?WmsLocation;
    public function findByCode(string $code): ?WmsLocation;
    public function create(array $data): WmsLocation;
    public function update(WmsLocation $l, array $data): bool;
    public function delete(WmsLocation $l): bool;
    /** Tree map: [zone => [aisle => [racks => [shelves...]]]] for the warehouse-map view. */
    public function tree(?int $hubId = null): array;
}
