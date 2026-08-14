<?php

namespace App\Console\Commands;

use App\Shipping\Jobs\SyncTrackingJob;
use App\Shipping\Repositories\ShippingConnectionRepository;
use Illuminate\Console\Command;

/**
 * Cron entry point. Iterates active shipping connections and dispatches one
 * SyncTrackingJob per connection. Each job runs independently (per-connection
 * isolation) so a slow provider can't block others.
 *
 * Schedule (registered in app/Console/Kernel.php):
 *   $schedule->command('shipping:sync-tracking')->everyFiveMinutes()->withoutOverlapping();
 *
 * Replaces the old logestechs:sync-tracking (which was Logestechs-specific
 * and polled directly without queueing).
 */
class ShippingSyncTracking extends Command
{
    protected $signature   = 'shipping:sync-tracking {--provider= : Limit to a single provider code}';
    protected $description = 'Dispatch per-connection tracking sync jobs for all active shipping connections';

    public function handle(ShippingConnectionRepository $repo): int
    {
        $connections = $repo->activeForSync();
        if ($filter = $this->option('provider')) {
            $connections = $connections->filter(fn ($c) => $c->provider->code === $filter);
        }

        if ($connections->isEmpty()) {
            $this->info('No active shipping connections to sync.');
            return self::SUCCESS;
        }

        $queueName = (string) config('shipping.queue.name', 'shipping');
        $queueConn = (string) config('shipping.queue.connection');

        foreach ($connections as $c) {
            SyncTrackingJob::dispatch($c->id)
                ->onConnection($queueConn ?: null)
                ->onQueue($queueName);
        }

        $this->info("✅ Dispatched {$connections->count()} per-connection sync job(s).");
        return self::SUCCESS;
    }
}
