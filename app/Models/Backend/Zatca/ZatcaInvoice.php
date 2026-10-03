<?php

namespace App\Models\Backend\Zatca;

use App\Enums\Zatca\ZatcaInvoiceStatus;
use App\Enums\Zatca\ZatcaInvoiceType;
use App\Models\Backend\Merchantpanel\Invoice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZatcaInvoice extends Model
{
    protected $table = 'zatca_invoices';

    protected $guarded = ['id'];

    protected $casts = [
        'issued_at' => 'datetime',
        'generated_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total_inclusive' => 'decimal:2',
        'sequence' => 'integer',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function scopeCompanywise(Builder $q): Builder
    {
        return $q->where('company_id', settings('company_id'));
    }

    public function scopeForMerchant(Builder $q, int $merchantId): Builder
    {
        return $q->where('merchant_id', $merchantId);
    }

    public function statusEnum(): ZatcaInvoiceStatus
    {
        return ZatcaInvoiceStatus::tryFrom((string) $this->status) ?? ZatcaInvoiceStatus::Pending;
    }

    public function typeEnum(): ZatcaInvoiceType
    {
        return ZatcaInvoiceType::tryFrom((string) $this->invoice_type) ?? ZatcaInvoiceType::Simplified;
    }

    public function qrDataUri(): ?string
    {
        if (! $this->qr_payload) {
            return null;
        }
        return 'data:image/svg+xml;base64,' . base64_encode($this->qrSvg());
    }

    public function qrSvg(): string
    {
        $generator = new \Milon\Barcode\DNS2D();
        $generator->setStorPath(storage_path('framework/barcodes/'));
        return $generator->getBarcodeSVG((string) $this->qr_payload, 'QRCODE', 4, 4, 'black');
    }
}
