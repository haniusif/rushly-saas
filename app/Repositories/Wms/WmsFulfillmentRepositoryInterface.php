<?php

namespace App\Repositories\Wms;

use App\Models\Backend\Wms\WmsFulfillment;
use Illuminate\Http\Request;

interface WmsFulfillmentRepositoryInterface
{
    public function all(?Request $request = null);
    public function find(int $id): ?WmsFulfillment;
    public function create(array $data, array $items): WmsFulfillment;
    public function confirmPick(WmsFulfillment $f, int $userId, array $picks): bool;
    public function confirmPack(WmsFulfillment $f, int $userId): bool;
    public function dispatch(WmsFulfillment $f): bool;
    public function nextFulfillmentNumber(): string;
    /** SLA-breached open fulfillments (sla_deadline in the past). */
    public function breachedSla();
}
