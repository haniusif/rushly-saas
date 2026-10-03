<?php

namespace App\Services\Zatca;

use App\Models\Backend\Merchantpanel\Invoice;
use App\Models\Backend\Zatca\ZatcaInvoice;
use App\Models\Backend\Zatca\ZatcaSetting;
use App\Services\Zatca\Contracts\ZatcaGateway;
use RuntimeException;

/**
 * Public facade for the ZATCA module — what controllers, jobs and commands call.
 */
final class ZatcaService
{
    public function __construct(
        private readonly InvoiceBuilder $builder,
        private readonly ZatcaGateway $gateway,
    ) {}

    public function settingsFor(int $companyId): ZatcaSetting
    {
        return ZatcaSetting::query()->firstOrCreate(
            ['company_id' => $companyId],
            [
                'seller_name_en' => '',
                'seller_name_ar' => '',
                'vat_number'     => '',
                'enabled'        => false,
            ],
        );
    }

    public function generate(Invoice $source, array $opts = []): ZatcaInvoice
    {
        $companyId = $source->company_id ?? settings('company_id');
        $setting = $this->settingsFor((int) $companyId);

        if (! $setting->enabled) {
            throw new RuntimeException('ZATCA is not enabled for this tenant.');
        }

        $zatca = $this->builder->build($source, $setting, $opts);

        if ($this->gateway->isAvailable($setting)) {
            // Phase 2 hook — Phase 1 NullGateway short-circuits.
            $this->gateway->report($zatca);
        }

        return $zatca;
    }

    public function regenerate(ZatcaInvoice $zatca, array $opts = []): ZatcaInvoice
    {
        $source = $zatca->invoice()->first();
        if (! $source) {
            throw new RuntimeException('Source invoice not found.');
        }
        return $this->generate($source, $opts);
    }

    public function markFailed(ZatcaInvoice $zatca, string $error): void
    {
        $this->builder->markFailed($zatca, $error);
    }
}
