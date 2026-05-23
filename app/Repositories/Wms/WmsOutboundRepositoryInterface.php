<?php

namespace App\Repositories\Wms;

use App\Models\Backend\Wms\WmsOutbound;
use Illuminate\Http\Request;

interface WmsOutboundRepositoryInterface
{
    public function all(?Request $request = null);
    public function find(int $id): ?WmsOutbound;
    public function create(array $data, array $items): WmsOutbound;
    public function complete(WmsOutbound $o): bool;
    public function nextOutboundNumber(): string;
}
