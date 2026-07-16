<?php

namespace App\Enums;

/**
 * Available shipping-label templates. Each case knows its display name,
 * the Blade view that renders it, and the mPDF paper format in millimetres.
 */
enum LabelTemplate: string
{
    case Aramex     = 'aramex';
    case Jet        = 'jet';
    case Smsa       = 'smsa';
    case Generic    = 'generic';
    case Internal   = 'internal';
    // ---- 2026-07 additions: five extra visual styles ----
    case Modern     = 'modern';
    case Compact    = 'compact';
    case Colorful   = 'colorful';
    case Minimal    = 'minimal';
    case Enterprise = 'enterprise';

    public static function default(): self
    {
        return self::Generic;
    }

    public function label(): string
    {
        return match ($this) {
            self::Aramex     => 'Aramex-style',
            self::Jet        => 'JET-style',
            self::Smsa       => 'SMSA-style',
            self::Generic    => 'Generic',
            self::Internal   => 'Internal (Rushly)',
            self::Modern     => 'Modern',
            self::Compact    => 'Compact',
            self::Colorful   => 'Colorful',
            self::Minimal    => 'Minimal',
            self::Enterprise => 'Enterprise',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Aramex     => 'High-density layout mimicking Aramex AWB labels. Good for direct hand-offs to Aramex pickup.',
            self::Jet        => 'Clean two-zone layout used by JET / Saudi local couriers.',
            self::Smsa       => 'SMSA-flavoured layout with bold barcode and large COD highlight.',
            self::Generic    => 'Neutral, brand-agnostic layout. Safe default for any carrier.',
            self::Internal   => 'Rushly-branded internal label for in-house delivery teams.',
            self::Modern     => 'High-contrast bold header with a jumbo barcode. Best for warehouse scanners.',
            self::Compact    => 'Everything squeezed into a small 4×6 zone for thermal label printers.',
            self::Colorful   => 'Uses the tenant primary/secondary colors for the header, COD block, and dividers.',
            self::Minimal    => 'Ultra-clean typography, no borders — just barcode, addresses, and COD.',
            self::Enterprise => 'Corporate multi-panel layout with sender / receiver / package / declared value.',
        };
    }

    /** mPDF format dimensions in mm (width × height). */
    public function format(): array
    {
        return match ($this) {
            self::Aramex     => [100, 150],
            self::Jet        => [105, 150],
            self::Smsa       => [100, 150],
            self::Generic    => [105, 150],
            self::Internal   => [105, 150],
            self::Modern     => [105, 150],
            self::Compact    => [100, 100],
            self::Colorful   => [105, 150],
            self::Minimal    => [105, 150],
            self::Enterprise => [148, 210], // A5 — the "big picture" corporate one
        };
    }

    public function view(): string
    {
        return 'labels.' . $this->value;
    }

    /** @return array<string,string> value => label */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(
            fn (self $c) => [$c->value => $c->label()]
        )->all();
    }
}
