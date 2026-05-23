<?php

namespace App\Repositories\Wms;

use App\Models\Backend\Wms\WmsGrn;
use Illuminate\Http\Request;

interface WmsGrnRepositoryInterface
{
    public function all(?Request $request = null);
    public function find(int $id): ?WmsGrn;
    public function create(array $data, array $items): WmsGrn;
    /** Marks GRN COMPLETED (or DISCREPANCY) and credits stock for each line. */
    public function complete(WmsGrn $grn): bool;
    public function delete(WmsGrn $grn): bool;
    public function nextGrnNumber(): string;
}
