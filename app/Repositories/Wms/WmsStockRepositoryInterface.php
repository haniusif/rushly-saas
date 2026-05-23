<?php

namespace App\Repositories\Wms;

use App\Models\Backend\Wms\WmsStock;

interface WmsStockRepositoryInterface
{
    /**
     * Apply a quantity change to stock for a product+location pair.
     *
     *   $delta > 0  → credit (add stock). Optionally specify batch/expiry via $context.
     *   $delta < 0  → debit (remove stock). Picks across batches using $strategy:
     *                   FEFO — earliest expiry first (default if any rows have expiry)
     *                   FIFO — oldest stock row first
     *                   LIFO — newest stock row first
     *
     * Always creates a WmsAdjustment row for audit. Throws InsufficientStockException
     * if a debit would drop stock below zero.
     */
    public function adjustStock(
        int $productId,
        int $locationId,
        int $delta,
        string $strategy = 'FEFO',
        array $context = []
    ): WmsStock;

    /** Aggregate on-hand quantity for a product across all locations. */
    public function onHand(int $productId): int;

    /** Aggregate available (on-hand − reserved) for a product. */
    public function available(int $productId): int;

    /** Reserve qty for a fulfillment (move qty → reserved_qty). */
    public function reserve(int $productId, int $locationId, int $qty): void;

    /** Release a prior reservation (subtract from reserved_qty). */
    public function release(int $productId, int $locationId, int $qty): void;
}
