<?php

namespace App\Repositories\Wms;

use App\Models\Backend\Wms\WmsAdjustment;
use Illuminate\Http\Request;

interface WmsAdjustmentRepositoryInterface
{
    public function all(?Request $request = null);
    public function find(int $id): ?WmsAdjustment;
    /**
     * Apply an adjustment. If ±change is ≥20% of current stock,
     * the record is saved with approval_status='pending_approval' and
     * stock IS NOT changed until a second supervisor approves.
     * Returns the WmsAdjustment row (saved either way).
     */
    public function submit(array $data): WmsAdjustment;
    public function approve(WmsAdjustment $a, int $approverUserId): bool;
    public function reject(WmsAdjustment $a, int $approverUserId, ?string $note = null): bool;
}
