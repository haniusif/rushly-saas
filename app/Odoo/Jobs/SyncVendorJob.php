<?php

namespace App\Odoo\Jobs;

use App\Odoo\Models\CourierPartner;
use App\Odoo\Services\VendorSync;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncVendorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 60, 300];

    public function __construct(public int $courierPartnerId) {}

    public function handle(VendorSync $sync): void
    {
        $vendor = CourierPartner::find($this->courierPartnerId);
        if (! $vendor) return;
        $sync->sync($vendor);
    }

    public function failed(Throwable $e): void
    {
        Log::error('odoo.sync.vendor.failed', ['courier_partner_id' => $this->courierPartnerId, 'error' => $e->getMessage()]);
        CourierPartner::where('id', $this->courierPartnerId)->update([
            'odoo_sync_status' => 'failed',
            'odoo_sync_error'  => substr($e->getMessage(), 0, 1000),
            'odoo_synced_at'   => Carbon::now(),
        ]);
    }
}
