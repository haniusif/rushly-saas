<?php

namespace App\Enums\Zatca;

enum ZatcaInvoiceStatus: string
{
    case Pending = 'pending';
    case Generated = 'generated';
    case Failed = 'failed';
    case Regenerated = 'regenerated';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Generated => 'Generated',
            self::Failed => 'Failed',
            self::Regenerated => 'Regenerated',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Generated => 'success',
            self::Failed => 'danger',
            self::Regenerated => 'info',
        };
    }
}
