<?php

namespace App\Odoo\Jobs;

use App\Odoo\Services\BillSync;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PushCourierBillJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 60, 300];

    public function __construct(public int $courierStatementId) {}

    public function handle(BillSync $sync): void
    {
        $sync->sync($this->courierStatementId);
    }

    public function failed(Throwable $e): void
    {
        Log::error('odoo.sync.bill.failed', ['courier_statement_id' => $this->courierStatementId, 'error' => $e->getMessage()]);
        DB::table('courier_statements')->where('id', $this->courierStatementId)->update([
            'odoo_sync_status' => 'failed',
            'odoo_sync_error'  => substr($e->getMessage(), 0, 1000),
            'odoo_synced_at'   => Carbon::now(),
            'updated_at'       => Carbon::now(),
        ]);
    }
}
