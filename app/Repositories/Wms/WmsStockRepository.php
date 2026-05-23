<?php

namespace App\Repositories\Wms;

use App\Enums\Wms\AdjustmentReason;
use App\Enums\Wms\PickingStrategy;
use App\Exceptions\Wms\InsufficientStockException;
use App\Models\Backend\Wms\WmsAdjustment;
use App\Models\Backend\Wms\WmsStock;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WmsStockRepository implements WmsStockRepositoryInterface
{
    public function adjustStock(
        int $productId,
        int $locationId,
        int $delta,
        string $strategy = PickingStrategy::FEFO,
        array $context = []
    ): WmsStock {
        if ($delta === 0) {
            return $this->getOrCreateRow($productId, $locationId, $context);
        }

        return DB::transaction(function () use ($productId, $locationId, $delta, $strategy, $context) {
            if ($delta > 0) {
                $row = $this->credit($productId, $locationId, $delta, $context);
            } else {
                $row = $this->debit($productId, $locationId, abs($delta), $strategy, $context);
            }

            // Audit row — only when the change isn't part of a fulfillment/grn flow
            // (those create their own audit via WmsGrn/WmsOutbound records).
            $reason = $context['reason'] ?? AdjustmentReason::COUNT_CORRECTION;
            $before = (int) ($context['before'] ?? ($row->quantity - $delta));
            $after  = (int) $row->quantity;
            WmsAdjustment::create([
                'company_id'      => settings()->id,
                'product_id'      => $productId,
                'location_id'     => $locationId,
                'adjusted_by'     => $context['user_id'] ?? (Auth::id() ?? 0),
                'quantity_before' => $before,
                'quantity_after'  => $after,
                'quantity_change' => $delta,
                'reason'          => $reason,
                'reference'       => $context['reference'] ?? null,
                'notes'           => $context['notes'] ?? null,
                'approval_status' => 'approved',
            ]);

            return $row;
        });
    }

    public function onHand(int $productId): int
    {
        return (int) WmsStock::companywise()->where('product_id', $productId)->sum('quantity');
    }

    public function available(int $productId): int
    {
        $row = WmsStock::companywise()
            ->where('product_id', $productId)
            ->selectRaw('SUM(quantity) - SUM(reserved_qty) AS avail')
            ->first();
        return max(0, (int) ($row->avail ?? 0));
    }

    public function reserve(int $productId, int $locationId, int $qty): void
    {
        DB::transaction(function () use ($productId, $locationId, $qty) {
            $row = WmsStock::companywise()
                ->where('product_id', $productId)
                ->where('location_id', $locationId)
                ->lockForUpdate()
                ->first();

            if (!$row || ($row->quantity - $row->reserved_qty) < $qty) {
                throw new InsufficientStockException(
                    $productId, $locationId, $qty,
                    $row ? ($row->quantity - $row->reserved_qty) : 0
                );
            }
            $row->reserved_qty += $qty;
            $row->save();
        });
    }

    public function release(int $productId, int $locationId, int $qty): void
    {
        DB::transaction(function () use ($productId, $locationId, $qty) {
            $row = WmsStock::companywise()
                ->where('product_id', $productId)
                ->where('location_id', $locationId)
                ->lockForUpdate()
                ->first();
            if (!$row) return;
            $row->reserved_qty = max(0, $row->reserved_qty - $qty);
            $row->save();
        });
    }

    // ===== Internal =====

    protected function credit(int $productId, int $locationId, int $qty, array $ctx): WmsStock
    {
        $row = WmsStock::companywise()
            ->where('product_id', $productId)
            ->where('location_id', $locationId)
            ->where('batch_number', $ctx['batch_number'] ?? null)
            ->lockForUpdate()
            ->first();

        if (!$row) {
            $row = WmsStock::create([
                'company_id'   => settings()->id,
                'product_id'   => $productId,
                'location_id'  => $locationId,
                'quantity'     => $qty,
                'reserved_qty' => 0,
                'batch_number' => $ctx['batch_number'] ?? null,
                'lot_number'   => $ctx['lot_number']   ?? null,
                'expiry_date'  => $ctx['expiry_date']  ?? null,
            ]);
        } else {
            $row->quantity += $qty;
            // Refresh expiry/batch metadata if a more specific value is provided.
            if (isset($ctx['expiry_date']) && $ctx['expiry_date']) $row->expiry_date = $ctx['expiry_date'];
            $row->save();
        }
        return $row;
    }

    protected function debit(int $productId, int $locationId, int $qty, string $strategy, array $ctx): WmsStock
    {
        // Pull all stock rows at this product+location, ordered by strategy.
        $base = WmsStock::companywise()
            ->where('product_id', $productId)
            ->where('location_id', $locationId)
            ->lockForUpdate();

        $rows = match ($strategy) {
            PickingStrategy::LIFO => $base->orderByDesc('id')->get(),
            PickingStrategy::FIFO => $base->orderBy('id')->get(),
            default              => $base->orderByRaw('expiry_date IS NULL, expiry_date ASC, id ASC')->get(),
        };

        $available = (int) $rows->sum(fn ($r) => max(0, $r->quantity - $r->reserved_qty));
        if ($available < $qty) {
            throw new InsufficientStockException($productId, $locationId, $qty, $available);
        }

        $left = $qty;
        $lastRow = null;
        foreach ($rows as $r) {
            if ($left <= 0) break;
            $usable = max(0, $r->quantity - $r->reserved_qty);
            if ($usable <= 0) continue;
            $take = min($usable, $left);
            $r->quantity -= $take;
            $r->save();
            $left   -= $take;
            $lastRow = $r;
        }
        // Always return *some* row representing the location for the caller's audit.
        return $lastRow ?? $rows->first();
    }

    protected function getOrCreateRow(int $productId, int $locationId, array $ctx): WmsStock
    {
        return WmsStock::firstOrCreate(
            [
                'product_id'   => $productId,
                'location_id'  => $locationId,
                'batch_number' => $ctx['batch_number'] ?? null,
            ],
            [
                'company_id'   => settings()->id,
                'quantity'     => 0,
                'reserved_qty' => 0,
                'lot_number'   => $ctx['lot_number'] ?? null,
                'expiry_date'  => $ctx['expiry_date'] ?? null,
            ]
        );
    }
}
