<?php

namespace App\Repositories\Wms;

use App\Models\Backend\Wms\WmsAdjustment;
use App\Models\Backend\Wms\WmsStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WmsAdjustmentRepository implements WmsAdjustmentRepositoryInterface
{
    public const DUAL_APPROVAL_THRESHOLD = 0.20; // 20%

    public function __construct(protected WmsStockRepositoryInterface $stock) {}

    public function all(?Request $request = null)
    {
        $q = WmsAdjustment::companywise()->with(['product', 'location', 'adjustedBy', 'approvedBy']);
        if ($request) {
            if ($request->filled('product_id')) $q->where('product_id', $request->input('product_id'));
            if ($request->filled('reason'))     $q->where('reason', $request->input('reason'));
            if ($request->filled('status'))     $q->where('approval_status', $request->input('status'));
        }
        return $q->latest('id')->paginate(25);
    }

    public function find(int $id): ?WmsAdjustment
    {
        return WmsAdjustment::companywise()->with(['product', 'location', 'adjustedBy', 'approvedBy'])->find($id);
    }

    public function submit(array $data): WmsAdjustment
    {
        return DB::transaction(function () use ($data) {
            $stockRow = WmsStock::companywise()
                ->where('product_id', $data['product_id'])
                ->where('location_id', $data['location_id'])
                ->first();
            $before = (int) ($stockRow->quantity ?? 0);
            $after  = (int) $data['quantity_after'];
            $change = $after - $before;

            $pct = $before > 0 ? abs($change) / $before : ($change == 0 ? 0 : 1);
            $needsApproval = $pct >= self::DUAL_APPROVAL_THRESHOLD;

            $row = WmsAdjustment::create([
                'company_id'      => settings()->id,
                'product_id'      => $data['product_id'],
                'location_id'     => $data['location_id'],
                'adjusted_by'     => Auth::id() ?? 0,
                'quantity_before' => $before,
                'quantity_after'  => $after,
                'quantity_change' => $change,
                'reason'          => $data['reason'],
                'reference'       => $data['reference'] ?? null,
                'photo'           => $data['photo'] ?? null,
                'notes'           => $data['notes'] ?? null,
                'approval_status' => $needsApproval ? 'pending_approval' : 'approved',
            ]);

            // If auto-approved, apply immediately via the stock repo (which also writes a
            // duplicate audit row — that's the price of going through adjustStock so we
            // keep a single FIFO/FEFO codepath; harmless for ops).
            if (!$needsApproval && $change !== 0) {
                $this->applyChange($row);
            }

            return $row;
        });
    }

    public function approve(WmsAdjustment $a, int $approverUserId): bool
    {
        if ($a->approval_status !== 'pending_approval') return false;
        if ((int) $a->adjusted_by === (int) $approverUserId) {
            throw new \RuntimeException('Dual approval gate: the adjuster cannot also approve.');
        }
        return DB::transaction(function () use ($a, $approverUserId) {
            $a->approval_status = 'approved';
            $a->approved_by     = $approverUserId;
            $a->approved_at     = now();
            $a->save();
            $this->applyChange($a);
            return true;
        });
    }

    public function reject(WmsAdjustment $a, int $approverUserId, ?string $note = null): bool
    {
        $a->approval_status = 'rejected';
        $a->approved_by     = $approverUserId;
        $a->approved_at     = now();
        if ($note) $a->notes = trim(($a->notes ? $a->notes . "\n" : '') . "REJECTED: $note");
        return (bool) $a->save();
    }

    protected function applyChange(WmsAdjustment $a): void
    {
        $delta = (int) $a->quantity_change;
        if ($delta === 0) return;
        $this->stock->adjustStock(
            (int) $a->product_id,
            (int) $a->location_id,
            $delta,
            'FEFO',
            [
                'reason'    => $a->reason,
                'reference' => 'ADJ-' . $a->id,
                'notes'     => 'Applied adjustment',
                'user_id'   => $a->adjusted_by,
            ]
        );
    }
}
