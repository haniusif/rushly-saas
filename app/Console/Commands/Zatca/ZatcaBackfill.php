<?php

namespace App\Console\Commands\Zatca;

use App\Jobs\Zatca\GenerateZatcaInvoiceJob;
use App\Models\Backend\Merchantpanel\Invoice;
use App\Models\Backend\Zatca\ZatcaInvoice;
use App\Models\Backend\Zatca\ZatcaSetting;
use Illuminate\Console\Command;

class ZatcaBackfill extends Command
{
    protected $signature = 'zatca:backfill
        {--company= : Limit to a single company_id}
        {--since= : Only invoices on/after YYYY-MM-DD}
        {--dry : Show counts but do not dispatch}';

    protected $description = 'Queue ZATCA generation for existing invoices that have no ZATCA record';

    public function handle(): int
    {
        $companies = ZatcaSetting::query()->where('enabled', true)->get();
        if ($this->option('company')) {
            $companies = $companies->where('company_id', (int) $this->option('company'));
        }

        $total = 0;
        foreach ($companies as $setting) {
            $query = Invoice::query()->where('company_id', $setting->company_id);
            if ($since = $this->option('since')) {
                $query->whereDate('invoice_date', '>=', $since);
            }

            $existingIds = ZatcaInvoice::query()
                ->where('company_id', $setting->company_id)
                ->pluck('invoice_id')
                ->all();

            $query->whereNotIn('id', $existingIds);

            $count = $query->count();
            $this->info("Company {$setting->company_id}: {$count} invoice(s) pending backfill");
            $total += $count;

            if (! $this->option('dry')) {
                $query->select('id')->chunkById(200, function ($chunk) {
                    foreach ($chunk as $row) {
                        GenerateZatcaInvoiceJob::dispatch($row->id)->onQueue('zatca');
                    }
                });
            }
        }

        $this->info("Total queued: {$total}");
        return self::SUCCESS;
    }
}
