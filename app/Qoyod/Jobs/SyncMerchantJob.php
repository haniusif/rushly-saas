<?php

namespace App\Qoyod\Jobs;

use App\Models\Backend\Merchant;
use App\Qoyod\Services\CustomerSync;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncMerchantJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 60, 300];

    public function __construct(public int $merchantId) {}

    public function handle(CustomerSync $sync): void
    {
        $merchant = Merchant::find($this->merchantId);
        if (! $merchant) return;
        $sync->sync($merchant);
    }

    public function failed(Throwable $e): void
    {
        Log::error('qoyod.sync.merchant.failed', [
            'merchant_id' => $this->merchantId,
            'error'       => $e->getMessage(),
        ]);
        Merchant::where('id', $this->merchantId)->update([
            'qoyod_sync_status' => 'failed',
            'qoyod_sync_error'  => substr($e->getMessage(), 0, 1000),
            'qoyod_synced_at'   => Carbon::now(),
        ]);
    }
}
