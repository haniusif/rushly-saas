<?php

namespace App\Qoyod\Jobs;

use App\Qoyod\Models\CourierVendor;
use App\Qoyod\Services\VendorSync;
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

    public function __construct(public int $courierVendorId) {}

    public function handle(VendorSync $sync): void
    {
        $vendor = CourierVendor::find($this->courierVendorId);
        if (! $vendor) return;
        $sync->sync($vendor);
    }

    public function failed(Throwable $e): void
    {
        Log::error('qoyod.sync.vendor.failed', [
            'courier_vendor_id' => $this->courierVendorId,
            'error'             => $e->getMessage(),
        ]);
        CourierVendor::where('id', $this->courierVendorId)->update([
            'qoyod_sync_status' => 'failed',
            'qoyod_sync_error'  => substr($e->getMessage(), 0, 1000),
            'qoyod_synced_at'   => Carbon::now(),
        ]);
    }
}
