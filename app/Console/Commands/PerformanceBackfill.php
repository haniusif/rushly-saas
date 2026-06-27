<?php

namespace App\Console\Commands;

use App\Models\Backend\Parcel;
use App\Services\Performance\HaversineDistance;
use App\Services\Performance\SlaProxy;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Backfills the new Phase 4 instrumentation columns for HISTORICAL parcels.
 *
 *   parcels.expected_delivery_at  = created_at + SLA hours for delivery_type
 *   parcels.distance_m            = haversine(pickup → customer)
 *
 * The Phase 4 migration deliberately doesn't backfill in-band because the
 * parcels table is hot (and large) in tenant deployments — an unbounded
 * UPDATE would lock writers. This command processes rows in chunks and
 * only touches NULL columns, so it's safe to re-run.
 *
 * Usage:
 *   php artisan performance:backfill
 *   php artisan performance:backfill --chunk=2000
 *   php artisan performance:backfill --tenant=acme       (limit to a tenant)
 *   php artisan performance:backfill --dry-run           (count, don't write)
 */
class PerformanceBackfill extends Command
{
    protected $signature = 'performance:backfill
        {--chunk=1000 : Number of rows per chunk}
        {--tenant= : Restrict to a single tenant company_id}
        {--dry-run : Report what would change without writing}';

    protected $description = 'Backfill expected_delivery_at + distance_m on historical parcels';

    public function handle(): int
    {
        $chunk    = max(50, (int) $this->option('chunk'));
        $tenant   = $this->option('tenant');
        $dry      = (bool) $this->option('dry-run');

        $base = Parcel::withoutGlobalScope('tenant')
            ->where(function ($q) {
                $q->whereNull('expected_delivery_at')->orWhereNull('distance_m');
            });
        if ($tenant !== null) {
            $base->where('company_id', $tenant);
        }

        $total = (clone $base)->count();
        if ($total === 0) {
            $this->info('Nothing to backfill — all parcels already have instrumentation set.');
            return Command::SUCCESS;
        }
        $this->info(($dry ? '[dry-run] ' : '') . "Backfilling {$total} parcels in chunks of {$chunk}…");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $updated = 0;
        $base->orderBy('id')->chunkById($chunk, function ($parcels) use (&$updated, $bar, $dry) {
            foreach ($parcels as $p) {
                $patch = [];

                if ($p->expected_delivery_at === null) {
                    $hours = SlaProxy::hoursFor($p->delivery_type_id);
                    $start = $p->created_at ?? Carbon::now();
                    $patch['expected_delivery_at'] = Carbon::parse($start)->addHours($hours);
                }
                if ($p->distance_m === null) {
                    $dist = HaversineDistance::metres(
                        (float) ($p->pickup_lat ?? 0),
                        (float) ($p->pickup_long ?? 0),
                        (float) ($p->customer_lat ?? 0),
                        (float) ($p->customer_long ?? 0),
                    );
                    if ($dist !== null) $patch['distance_m'] = $dist;
                }

                if (! empty($patch)) {
                    if (! $dry) {
                        // Avoid triggering observers — we want a tight UPDATE,
                        // not a full Eloquent save() round-trip.
                        Parcel::withoutGlobalScope('tenant')->whereKey($p->id)->update($patch);
                    }
                    $updated++;
                }
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info(($dry ? '[dry-run] ' : '') . "Updated {$updated} of {$total} parcels.");
        return Command::SUCCESS;
    }
}
