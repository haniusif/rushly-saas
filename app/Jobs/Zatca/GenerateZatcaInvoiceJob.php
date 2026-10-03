<?php

namespace App\Jobs\Zatca;

use App\Models\Backend\Merchantpanel\Invoice;
use App\Services\Zatca\ZatcaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateZatcaInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public int $invoiceId, public array $opts = []) {}

    public function handle(ZatcaService $svc): void
    {
        $invoice = Invoice::find($this->invoiceId);
        if (! $invoice) {
            Log::warning('ZATCA: source invoice missing', ['id' => $this->invoiceId]);
            return;
        }
        $svc->generate($invoice, $this->opts);
    }

    public function failed(Throwable $e): void
    {
        Log::error('ZATCA generation job failed permanently', [
            'invoice_id' => $this->invoiceId,
            'error'      => $e->getMessage(),
        ]);
    }
}
