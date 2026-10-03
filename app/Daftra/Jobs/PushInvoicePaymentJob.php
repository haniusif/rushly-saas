<?php

namespace App\Daftra\Jobs;

use App\Daftra\Services\InvoicePaymentSync;
use App\Models\Backend\Merchantpanel\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class PushInvoicePaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 60, 300];

    public function __construct(public int $invoiceId) {}

    public function handle(InvoicePaymentSync $sync): void
    {
        $invoice = Invoice::find($this->invoiceId);
        if (! $invoice) return;
        $sync->sync($invoice);
    }

    public function failed(Throwable $e): void
    {
        Log::error('daftra.sync.invoice_payment.failed', [
            'invoice_id' => $this->invoiceId,
            'error'      => $e->getMessage(),
        ]);
        Invoice::where('id', $this->invoiceId)->update([
            'daftra_sync_status' => 'payment_failed',
            'daftra_sync_error'  => substr($e->getMessage(), 0, 1000),
            'daftra_synced_at'   => Carbon::now(),
        ]);
    }
}
