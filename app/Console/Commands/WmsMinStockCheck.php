<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Repositories\Wms\WmsProductRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class WmsMinStockCheck extends Command
{
    protected $signature = 'wms:min-stock-check {--tenant= : Run for a specific tenant only}';
    protected $description = 'Find products whose total stock has hit the reorder point and notify merchants + admins.';

    public function handle(WmsProductRepositoryInterface $productRepo): int
    {
        $tenants = $this->option('tenant')
            ? Tenant::whereKey($this->option('tenant'))->get()
            : Tenant::all();

        $totalLow = 0;

        foreach ($tenants as $tenant) {
            try {
                tenancy()->initialize($tenant);

                $low = $productRepo->lowStock();
                $totalLow += $low->count();

                if ($low->isNotEmpty()) {
                    $this->warn(sprintf('tenant=%s low_stock=%d', $tenant->getTenantKey(), $low->count()));
                    foreach ($low as $p) {
                        Log::warning('wms.min-stock.hit', [
                            'tenant'        => $tenant->getTenantKey(),
                            'product_id'    => $p->id,
                            'sku'           => $p->sku,
                            'on_hand'       => $p->total_qty,
                            'reorder_point' => $p->reorder_point,
                            'merchant_id'   => $p->merchant_id,
                        ]);
                    }
                    // Phase 7 will dispatch push notifications via FollowupNotificationDispatcher.
                } else {
                    $this->line(sprintf('tenant=%s low_stock=0', $tenant->getTenantKey()));
                }
            } catch (\Throwable $e) {
                $this->error(sprintf('tenant=%s failed: %s', $tenant->getTenantKey(), $e->getMessage()));
            } finally {
                tenancy()->end();
            }
        }

        $this->info(sprintf('done. %d low-stock product(s) across %d tenant(s).', $totalLow, $tenants->count()));
        return self::SUCCESS;
    }
}
