<?php

namespace App\Enums\Zatca;

enum ZatcaInvoiceType: string
{
    case Standard = 'standard';
    case Simplified = 'simplified';

    public static function options(): array
    {
        return [
            self::Standard->value => 'Standard Tax Invoice (B2B)',
            self::Simplified->value => 'Simplified Tax Invoice (B2C)',
        ];
    }
}
