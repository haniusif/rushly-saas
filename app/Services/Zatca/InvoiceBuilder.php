<?php

namespace App\Services\Zatca;

use App\Enums\Zatca\ZatcaInvoiceStatus;
use App\Enums\Zatca\ZatcaInvoiceType;
use App\Models\Backend\Merchantpanel\Invoice;
use App\Models\Backend\Zatca\ZatcaAuditLog;
use App\Models\Backend\Zatca\ZatcaInvoice;
use App\Models\Backend\Zatca\ZatcaSetting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Orchestrator: derives ZATCA fields from a source Invoice + tenant settings,
 * builds the Phase 1 TLV/QR, persists the ZatcaInvoice row, and writes assets.
 *
 * VAT math assumes the source `invoices.total_charge` is VAT-INCLUSIVE
 * at the tenant's configured rate (default 15%). This was an explicit
 * design choice to keep the existing billing pipeline untouched.
 */
final class InvoiceBuilder
{
    public function __construct(
        private readonly TlvEncoder $tlv,
        private readonly QrGenerator $qr,
    ) {}

    /**
     * Build (or rebuild) the ZATCA artifact for a source Invoice.
     *
     * @param Invoice            $source  the existing merchant billing invoice
     * @param ZatcaSetting       $setting tenant configuration
     * @param array<string,mixed> $opts    optional overrides (buyer info, type)
     */
    public function build(Invoice $source, ZatcaSetting $setting, array $opts = []): ZatcaInvoice
    {
        if (! $setting->isReady()) {
            throw new RuntimeException('ZATCA settings are incomplete or disabled.');
        }

        return DB::transaction(function () use ($source, $setting, $opts) {
            $issuedAt = CarbonImmutable::parse(
                $opts['issued_at'] ?? $source->invoice_date ?? now()
            )->utc();

            $totalInclusive = round((float) ($source->total_charge ?? 0), 2);
            $vatRate = (float) ($setting->vat_rate ?? 15.0);
            [$subtotal, $vatAmount] = $this->splitVatInclusive($totalInclusive, $vatRate);

            $type = isset($opts['type'])
                ? ZatcaInvoiceType::from($opts['type'])
                : ZatcaInvoiceType::Simplified;

            $zatca = ZatcaInvoice::firstOrNew([
                'company_id' => $setting->company_id,
                'invoice_id' => $source->id,
            ]);

            $isRegeneration = $zatca->exists;

            $invoiceNumber = $zatca->invoice_number ?: $this->reserveInvoiceNumber($setting);
            $sequence = $zatca->sequence ?: ((int) $setting->last_invoice_counter);

            $tlvMap = $this->buildTlv($setting, $issuedAt, $totalInclusive, $vatAmount, $type, $opts);
            $qrPayload = $this->tlv->encodeMap($tlvMap);

            $qrRelPath = sprintf('zatca/%d/qr/%s.png', $setting->company_id, $invoiceNumber);
            $this->qr->writePng($qrPayload, $qrRelPath);

            $previousHash = $setting->last_invoice_hash;
            $hash = hash('sha256', ($previousHash ?? '') . $qrPayload);

            $zatca->fill([
                'company_id'      => $setting->company_id,
                'invoice_id'      => $source->id,
                'merchant_id'     => $source->merchant_id,
                'uuid'            => $zatca->uuid ?: (string) Str::uuid(),
                'invoice_number'  => $invoiceNumber,
                'invoice_type'    => $type->value,
                'issued_at'       => $issuedAt,
                'buyer_name'      => $opts['buyer_name'] ?? optional($source->merchant)->business_name,
                'buyer_vat_number'=> $opts['buyer_vat_number'] ?? null,
                'buyer_address'   => $opts['buyer_address'] ?? null,
                'subtotal'        => $subtotal,
                'vat_rate'        => $vatRate,
                'vat_amount'      => $vatAmount,
                'total_inclusive' => $totalInclusive,
                'currency'        => $setting->currency ?: 'SAR',
                'qr_payload'      => $qrPayload,
                'qr_image_path'   => $qrRelPath,
                'previous_hash'   => $previousHash,
                'hash'            => $hash,
                'sequence'        => $sequence,
                'status'          => $isRegeneration
                    ? ZatcaInvoiceStatus::Regenerated->value
                    : ZatcaInvoiceStatus::Generated->value,
                'error_message'   => null,
                'generated_at'    => now(),
            ])->save();

            $setting->forceFill([
                'last_invoice_hash' => $hash,
            ])->save();

            ZatcaAuditLog::create([
                'company_id'       => $setting->company_id,
                'zatca_invoice_id' => $zatca->id,
                'actor_id'         => optional(auth()->user())->id,
                'action'           => $isRegeneration ? 'regenerated' : 'generated',
                'payload'          => [
                    'invoice_number' => $invoiceNumber,
                    'total_inclusive'=> $totalInclusive,
                    'vat_amount'     => $vatAmount,
                    'type'           => $type->value,
                ],
                'ip'               => request()?->ip(),
            ]);

            return $zatca;
        });
    }

    /**
     * Mark an existing ZatcaInvoice as failed (used by job retry exhaust).
     */
    public function markFailed(ZatcaInvoice $zatca, string $error): void
    {
        $zatca->forceFill([
            'status'        => ZatcaInvoiceStatus::Failed->value,
            'error_message' => $error,
        ])->save();

        Log::warning('ZATCA invoice generation failed', [
            'company_id' => $zatca->company_id,
            'invoice_id' => $zatca->invoice_id,
            'error'      => $error,
        ]);

        ZatcaAuditLog::create([
            'company_id'       => $zatca->company_id,
            'zatca_invoice_id' => $zatca->id,
            'action'           => 'failed',
            'payload'          => ['error' => $error],
        ]);
    }

    /**
     * Returns [subtotal, vat] derived from a VAT-inclusive total.
     */
    public function splitVatInclusive(float $totalInclusive, float $vatRatePercent): array
    {
        $rate = $vatRatePercent / 100;
        $subtotal = round($totalInclusive / (1 + $rate), 2);
        $vat = round($totalInclusive - $subtotal, 2);
        return [$subtotal, $vat];
    }

    /**
     * @return array<int,string>
     */
    private function buildTlv(
        ZatcaSetting $setting,
        CarbonImmutable $issuedAt,
        float $totalInclusive,
        float $vatAmount,
        ZatcaInvoiceType $type,
        array $opts,
    ): array {
        $sellerName = $opts['seller_name_override']
            ?? ($setting->seller_name_ar ?: $setting->seller_name_en);

        $map = [
            1 => (string) $sellerName,
            2 => (string) $setting->vat_number,
            3 => $issuedAt->format('Y-m-d\TH:i:s\Z'),
            4 => number_format($totalInclusive, 2, '.', ''),
            5 => number_format($vatAmount, 2, '.', ''),
        ];

        return $map;
    }

    private function reserveInvoiceNumber(ZatcaSetting $setting): string
    {
        $locked = ZatcaSetting::query()->whereKey($setting->id)->lockForUpdate()->first();
        $next = ((int) $locked->last_invoice_counter) + 1;
        $invoiceNumber = $locked->invoice_prefix . str_pad((string) $next, 8, '0', STR_PAD_LEFT);
        $locked->forceFill(['last_invoice_counter' => $next])->save();
        $setting->setRawAttributes(array_merge($setting->getAttributes(), [
            'last_invoice_counter' => $next,
        ]), true);
        return $invoiceNumber;
    }
}
