<?php

namespace App\Repositories\Wms;

use App\Models\Backend\Wms\WmsCycleCount;
use Illuminate\Http\Request;

interface WmsCycleCountRepositoryInterface
{
    public function all(?Request $request = null);
    public function find(int $id): ?WmsCycleCount;
    public function create(array $data): WmsCycleCount;
    public function start(WmsCycleCount $c): bool;
    public function complete(WmsCycleCount $c): bool;
    public function nextCountNumber(): string;
}
