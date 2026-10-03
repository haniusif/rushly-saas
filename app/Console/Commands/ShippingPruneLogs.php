<?php

namespace App\Console\Commands;

use App\Shipping\Models\ShippingApiLog;
use Illuminate\Console\Command;

/**
 * Prune shipping_api_logs — outbound HTTP call log for the Shipping
 * module. Retention config lives at
 * `config('shipping.logging.retention_days')` (default 30).
 *
 * Mirror of `commerce:prune-logs` shape. Runs daily from
 * Console\Kernel@3am with withoutOverlapping.
 */
class ShippingPruneLogs extends Command
{
    protected $signature   = 'shipping:prune-logs {--dry-run : Report counts without deleting}';
    protected $description = 'Prune shipping_api_logs older than shipping.logging.retention_days';

    public function handle(): int
    {
        $days   = (int) config('shipping.logging.retention_days', 30);
        $cutoff = now()->subDays($days);
        $dryRun = (bool) $this->option('dry-run');

        $count = ShippingApiLog::query()
            ->where('created_at', '<', $cutoff)
            ->count();

        $this->info("Retention: {$days} days (cutoff {$cutoff->toDateTimeString()})");
        $this->info("shipping_api_logs   : {$count} row(s) to prune");

        if ($dryRun) {
            $this->warn('--dry-run set — no rows deleted.');
            return self::SUCCESS;
        }

        $pruned = 0;
        do {
            $batch = ShippingApiLog::query()
                ->where('created_at', '<', $cutoff)
                ->orderBy('id')
                ->limit(5000)
                ->delete();
            $pruned += $batch;
        } while ($batch > 0);

        $this->info("Pruned shipping_api_logs : {$pruned}");
        return self::SUCCESS;
    }
}
