<?php

namespace App\Console\Commands;

use App\Models\Backend\Wms\WmsStock;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class WmsExpiryAlert extends Command
{
    protected $signature = 'wms:expiry-alert
                            {--tenant= : Run for a specific tenant only}
                            {--days=7 : Warn when expiry is within this many days}';

    protected $description = 'Find stock batches expiring within N days and alert admins.';

    public function handle(): int
    {
        $tenants = $this->option('tenant')
            ? Tenant::whereKey($this->option('tenant'))->get()
            : Tenant::all();

        $days  = max(1, (int) $this->option('days'));
        $cutoff = Carbon::now()->addDays($days);
        $total  = 0;

        foreach ($tenants as $tenant) {
            try {
                tenancy()->initialize($tenant);

                if (!settings()) {
                    $this->line(sprintf('tenant=%s skipped (no settings)', $tenant->getTenantKey()));
                    continue;
                }

                $expiring = WmsStock::companywise()
                    ->whereNotNull('expiry_date')
                    ->whereDate('expiry_date', '<=', $cutoff->toDateString())
                    ->where('quantity', '>', 0)
                    ->with(['product', 'location'])
                    ->get();

                $total += $expiring->count();

                if ($expiring->isNotEmpty()) {
                    $this->warn(sprintf('tenant=%s expiring=%d', $tenant->getTenantKey(), $expiring->count()));
                    foreach ($expiring as $s) {
                        $daysLeft = Carbon::parse($s->expiry_date)->diffInDays(Carbon::now(), false) * -1;
                        Log::warning('wms.expiry.soon', [
                            'tenant'       => $tenant->getTenantKey(),
                            'stock_id'     => $s->id,
                            'product_sku'  => optional($s->product)->sku,
                            'location'     => optional($s->location)->code,
                            'quantity'     => $s->quantity,
                            'expiry_date'  => (string) $s->expiry_date,
                            'days_left'    => $daysLeft,
                        ]);
                    }
                } else {
                    $this->line(sprintf('tenant=%s expiring=0', $tenant->getTenantKey()));
                }
            } catch (\Throwable $e) {
                $this->error(sprintf('tenant=%s failed: %s', $tenant->getTenantKey(), $e->getMessage()));
            } finally {
                tenancy()->end();
            }
        }

        $this->info(sprintf('done. %d expiring stock row(s) across %d tenant(s).', $total, $tenants->count()));
        return self::SUCCESS;
    }
}
