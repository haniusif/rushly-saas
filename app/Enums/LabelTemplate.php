<?php

namespace App\Enums;

/**
 * Available shipping-label templates. Each case knows its display name,
 * the Blade view that renders it, and the mPDF paper format in millimetres.
 */
enum LabelTemplate: string
{
    // String values also become the URL slug and the blade view name — kept
    // as layout descriptors instead of the courier brand each layout used
    // to imitate, so operators don't see "…/preview/aramex" in the browser.
    case Aramex     = 'high-density';
    case Jet        = 'two-zone';
    case Smsa       = 'bold-barcode';
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
        // Names describe the LAYOUT (density, structure) rather than which
        // external courier the visual originally imitated — the picker sits
        // in a tenant-facing settings page and mixing third-party brands in
        // there confused operators about ownership.
        return match ($this) {
            self::Aramex     => 'High-density',
            self::Jet        => 'Two-zone',
            self::Smsa       => 'Bold barcode',
            self::Generic    => 'Generic',
            self::Internal   => 'Internal',
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
            self::Aramex     => 'High-density AWB layout with side-by-side sender/receiver blocks. Good when the label has to carry a lot of fine print.',
            self::Jet        => 'Clean two-zone layout with a prominent barcode strip. Works well for most local-courier hand-offs.',
            self::Smsa       => 'Compact layout with a bold barcode and a large COD-amount highlight — hard to miss on the loading dock.',
            self::Generic    => 'Neutral, brand-agnostic layout. Safe default for any carrier.',
            self::Internal   => 'Tenant-branded layout for in-house delivery teams.',
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
