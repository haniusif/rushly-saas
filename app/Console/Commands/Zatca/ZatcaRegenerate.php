<?php

namespace App\Console\Commands\Zatca;

use App\Models\Backend\Zatca\ZatcaInvoice;
use App\Services\Zatca\ZatcaService;
use Illuminate\Console\Command;

class ZatcaRegenerate extends Command
{
    protected $signature = 'zatca:regenerate {id : zatca_invoices.id}';

    protected $description = 'Regenerate the QR/TLV artifact for a single ZATCA invoice';

    public function handle(ZatcaService $svc): int
    {
        $zatca = ZatcaInvoice::find((int) $this->argument('id'));
        if (! $zatca) {
            $this->error('ZATCA invoice not found.');
            return self::FAILURE;
        }
        $rebuilt = $svc->regenerate($zatca);
        $this->info("Regenerated {$rebuilt->invoice_number} (status: {$rebuilt->status})");
        return self::SUCCESS;
    }
}
