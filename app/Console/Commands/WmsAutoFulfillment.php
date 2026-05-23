<?php

namespace App\Console\Commands;

use App\Enums\ParcelStatus;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\Backend\Wms\WmsProduct;
use App\Models\Tenant;
use App\Repositories\Wms\WmsFulfillmentRepositoryInterface;
use App\Repositories\Wms\WmsStockRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Auto-creates a WmsFulfillment when a new parcel arrives for a merchant that
 * is "WMS-managed" — defined here as any merchant that has at least one active
 * WmsProduct + sufficient stock at any single location.
 *
 * For now this is a SAFE, OPT-IN scan: we only act on parcels that have:
 *   - status = PENDING
 *   - a merchant that has WmsProducts
 *   - no existing wms_fulfillment_id
 *   - meta in the parcel.note field referencing a SKU we can fulfil
 *     (single-item only; multi-item parcel→WMS mapping is a future enhancement)
 */
class WmsAutoFulfillment extends Command
{
    protected $signature = 'wms:auto-fulfillment
                            {--tenant= : Run for a specific tenant only}
                            {--limit=50 : Max parcels to scan per tenant per run}';

    protected $description = 'Detect new pending parcels for WMS-managed merchants and auto-create fulfillment orders.';

    public function handle(
        WmsFulfillmentRepositoryInterface $fulRepo,
        WmsStockRepositoryInterface $stockRepo
    ): int {
        $tenants = $this->option('tenant')
            ? Tenant::whereKey($this->option('tenant'))->get()
            : Tenant::all();
        $limit = max(1, (int) $this->option('limit'));

        $totalCreated = 0;

        foreach ($tenants as $tenant) {
            try {
                tenancy()->initialize($tenant);

                if (!settings()) {
                    $this->line(sprintf('tenant=%s skipped (no settings)', $tenant->getTenantKey()));
                    continue;
                }

                // Which merchants are "WMS-managed"? Any with at least one active product.
                $wmsManagedMerchantIds = WmsProduct::companywise()
                    ->where('is_active', true)
                    ->distinct()
                    ->pluck('merchant_id')
                    ->all();

                if (empty($wmsManagedMerchantIds)) {
                    $this->line(sprintf('tenant=%s no WMS-managed merchants', $tenant->getTenantKey()));
                    continue;
                }

                $candidates = Parcel::companywise()
                    ->where('status', ParcelStatus::PENDING)
                    ->whereNull('wms_fulfillment_id')
                    ->whereIn('merchant_id', $wmsManagedMerchantIds)
                    ->limit($limit)
                    ->get();

                $created = 0;
                foreach ($candidates as $parcel) {
                    // Try to extract a SKU from the parcel note. Format: "SKU:XYZ qty:N".
                    // Production wiring will use a proper parcel-line table; this stub
                    // intentionally only fires when the note follows that pattern so we
                    // never accidentally fulfil a regular parcel.
                    if (!preg_match('/SKU:([A-Z0-9_-]+)\s+qty:(\d+)/i', (string) $parcel->note, $m)) {
                        continue;
                    }
                    $sku = $m[1];
                    $qty = (int) $m[2];

                    $product = WmsProduct::companywise()
                        ->where('merchant_id', $parcel->merchant_id)
                        ->where('sku', $sku)
                        ->first();
                    if (!$product) continue;

                    // Find a location with at least `qty` available units.
                    $stockRow = \App\Models\Backend\Wms\WmsStock::companywise()
                        ->where('product_id', $product->id)
                        ->whereRaw('(quantity - reserved_qty) >= ?', [$qty])
                        ->first();
                    if (!$stockRow) {
                        Log::info('wms.auto-fulfillment.no-stock', [
                            'tenant'      => $tenant->getTenantKey(),
                            'parcel_id'   => $parcel->id,
                            'sku'         => $sku,
                            'requested'   => $qty,
                            'available'   => $stockRepo->available($product->id),
                        ]);
                        continue;
                    }

                    try {
                        $f = $fulRepo->create(
                            [
                                'parcel_id'   => $parcel->id,
                                'hub_id'      => $product->hub_id,
                                'merchant_id' => $parcel->merchant_id,
                            ],
                            [[
                                'product_id'        => $product->id,
                                'location_id'       => $stockRow->location_id,
                                'quantity_required' => $qty,
                            ]]
                        );
                        $created++;
                        Log::info('wms.auto-fulfillment.created', [
                            'tenant'         => $tenant->getTenantKey(),
                            'fulfillment_id' => $f->id,
                            'parcel_id'      => $parcel->id,
                            'sku'            => $sku,
                            'qty'            => $qty,
                        ]);
                    } catch (\Throwable $e) {
                        Log::warning('wms.auto-fulfillment.failed', [
                            'parcel_id' => $parcel->id,
                            'error'     => $e->getMessage(),
                        ]);
                    }
                }

                $this->line(sprintf('tenant=%s scanned=%d created=%d', $tenant->getTenantKey(), $candidates->count(), $created));
                $totalCreated += $created;
            } catch (\Throwable $e) {
                $this->error(sprintf('tenant=%s failed: %s', $tenant->getTenantKey(), $e->getMessage()));
            } finally {
                tenancy()->end();
            }
        }

        $this->info(sprintf('done. %d fulfillment(s) auto-created across %d tenant(s).', $totalCreated, $tenants->count()));
        return self::SUCCESS;
    }
}
