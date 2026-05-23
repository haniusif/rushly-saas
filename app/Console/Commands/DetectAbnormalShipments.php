<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Repositories\AbnormalShipmentRepositoryInterface;
use Illuminate\Console\Command;
use Stancl\Tenancy\Database\Models\Domain;

class DetectAbnormalShipments extends Command
{
    /**
     * Iterates every tenant, reads its per-tenant threshold (Config), and
     * upserts abnormal_shipments rows for stalled parcels.
     */
    protected $signature = 'shipments:detect-abnormal
                            {--tenant= : Run only for a single tenant id (skip iteration)}
                            {--threshold= : Override the threshold in days for all tenants}';

    protected $description = 'Detect parcels with no activity for N days and upsert abnormal_shipments records.';

    public function handle(AbnormalShipmentRepositoryInterface $repo): int
    {
        $tenants = $this->option('tenant')
            ? Tenant::whereKey($this->option('tenant'))->get()
            : Tenant::all();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants found.');
            return self::SUCCESS;
        }

        $override     = $this->option('threshold') ? (int) $this->option('threshold') : null;
        $totalCreated = 0;
        $totalUpdated = 0;
        $totalScanned = 0;

        foreach ($tenants as $tenant) {
            try {
                tenancy()->initialize($tenant);

                $threshold = $override ?? $repo->getThresholdDays();
                $result    = $repo->detect($threshold);

                $this->line(sprintf(
                    'tenant=%s threshold=%dd scanned=%d created=%d updated=%d',
                    $tenant->getTenantKey(),
                    $threshold,
                    $result['scanned'],
                    $result['created'],
                    $result['updated']
                ));

                $totalCreated += $result['created'];
                $totalUpdated += $result['updated'];
                $totalScanned += $result['scanned'];
            } catch (\Throwable $e) {
                $this->error(sprintf(
                    'tenant=%s failed: %s',
                    $tenant->getTenantKey(),
                    $e->getMessage()
                ));
            } finally {
                tenancy()->end();
            }
        }

        $this->info(sprintf(
            'done. scanned=%d created=%d updated=%d across %d tenant(s).',
            $totalScanned,
            $totalCreated,
            $totalUpdated,
            $tenants->count()
        ));

        return self::SUCCESS;
    }
}
