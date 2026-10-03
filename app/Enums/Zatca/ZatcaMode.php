<?php

namespace App\Enums\Zatca;

enum ZatcaMode: string
{
    case Sandbox = 'sandbox';
    case Production = 'production';

    public static function options(): array
    {
        return [
            self::Sandbox->value => 'Sandbox',
            self::Production->value => 'Production',
        ];
    }
}
