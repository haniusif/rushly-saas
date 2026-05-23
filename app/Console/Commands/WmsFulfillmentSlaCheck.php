<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Repositories\Wms\WmsFulfillmentRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class WmsFulfillmentSlaCheck extends Command
{
    protected $signature = 'wms:sla-check {--tenant= : Run for a specific tenant only}';
    protected $description = 'Detect WMS fulfillments whose SLA deadline has passed and surface them for hub managers.';

    public function handle(WmsFulfillmentRepositoryInterface $repo): int
    {
        $tenants = $this->option('tenant')
            ? Tenant::whereKey($this->option('tenant'))->get()
            : Tenant::all();

        $totalBreached = 0;

        foreach ($tenants as $tenant) {
            try {
                tenancy()->initialize($tenant);

                $breached = $repo->breachedSla();
                $totalBreached += $breached->count();

                if ($breached->isNotEmpty()) {
                    $this->warn(sprintf(
                        'tenant=%s breached=%d',
                        $tenant->getTenantKey(),
                        $breached->count()
                    ));

                    // Phase 6 will dispatch push notifications via FollowupNotificationDispatcher.
                    // For now, log so the admin can see breaches in storage/logs.
                    foreach ($breached as $f) {
                        Log::warning('wms.sla.breached', [
                            'tenant'          => $tenant->getTenantKey(),
                            'fulfillment_id'  => $f->id,
                            'fulfillment_no'  => $f->fulfillment_number,
                            'status'          => $f->status,
                            'sla_deadline'    => (string) $f->sla_deadline,
                            'hub_id'          => $f->hub_id,
                            'minutes_overdue' => max(0, $f->sla_deadline?->diffInMinutes(now()) ?? 0),
                        ]);
                    }
                } else {
                    $this->line(sprintf('tenant=%s breached=0', $tenant->getTenantKey()));
                }
            } catch (\Throwable $e) {
                $this->error(sprintf('tenant=%s failed: %s', $tenant->getTenantKey(), $e->getMessage()));
            } finally {
                tenancy()->end();
            }
        }

        $this->info(sprintf('done. %d breached fulfillment(s) across %d tenant(s).', $totalBreached, $tenants->count()));
        return self::SUCCESS;
    }
}
