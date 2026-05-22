<?php
declare(strict_types=1);

namespace App\Support;

use App\Enums\ParcelStatus;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\App;

/**
 * Helper for working with ParcelStatus interface constants
 * - Single source of truth via reflection (no hardcoded numeric maps)
 * - i18n: builds translation keys like parcel.status.return.received.by.merchant
 * - Elegant fallback label (Title Case) if translation key is missing
 * - Badge classes for Bootstrap, consistent across 34 states
 */
class ParcelStatusHelper
{
    /** @var array<int,string> value => NAME */
    protected static array $namesByValue = [];

    /** @var array<string,int> NAME => value */
    protected static array $valuesByName = [];

    /** @var array<int,string> badge color map (Bootstrap suffix) */
    protected static array $badgeMap = [
        // Base / forward flow
        ParcelStatus::PENDING                           => 'secondary',
        ParcelStatus::PICKUP_ASSIGN                     => 'info',
        ParcelStatus::PICKUP_RE_SCHEDULE                => 'info',
        ParcelStatus::RECEIVED_BY_PICKUP_MAN            => 'primary',
        ParcelStatus::RECEIVED_WAREHOUSE                => 'primary',
        ParcelStatus::TRANSFER_TO_HUB                   => 'primary',
        ParcelStatus::DELIVERY_MAN_ASSIGN               => 'info',
        ParcelStatus::DELIVERY_RE_SCHEDULE              => 'info',
        ParcelStatus::DELIVERED                         => 'success',
        ParcelStatus::DELIVER                           => 'primary',

        // Return / reverse flow
        ParcelStatus::RETURN_WAREHOUSE                  => 'warning',
        ParcelStatus::ASSIGN_MERCHANT                   => 'info',
        ParcelStatus::RETURNED_MERCHANT                 => 'warning',
        ParcelStatus::RECEIVED_BY_HUB                   => 'primary',
        ParcelStatus::RETURN_TO_COURIER                 => 'warning',
        ParcelStatus::RETURN_ASSIGN_TO_MERCHANT         => 'warning',
        ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE       => 'warning',
        ParcelStatus::RETURN_RECEIVED_BY_MERCHANT       => 'warning',

        // Partial flows
        ParcelStatus::PARTIAL_DELIVERED                 => 'warning',

        // 3PL
        ParcelStatus::ASSIGN_TO_3PL                     => 'info',

        // Cancellations (group to dark)
        ParcelStatus::PICKUP_ASSIGN_CANCEL              => 'dark',
        ParcelStatus::RECEIVED_BY_PICKUP_MAN_CANCEL     => 'dark',
        ParcelStatus::RECEIVED_WAREHOUSE_CANCEL         => 'dark',
        ParcelStatus::DELIVERY_MAN_ASSIGN_CANCEL        => 'dark',
        ParcelStatus::DELIVERY_RE_SCHEDULE_CANCEL       => 'dark',
        ParcelStatus::TRANSFER_TO_HUB_CANCEL            => 'dark',
        ParcelStatus::RECEIVED_BY_HUB_CANCEL            => 'dark',
        ParcelStatus::DELIVERED_CANCEL                  => 'dark',
        ParcelStatus::PICKUP_RE_SCHEDULE_CANCEL         => 'dark',
        ParcelStatus::RETURN_TO_COURIER_CANCEL          => 'dark',
        ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE_CANCEL=> 'dark',
        ParcelStatus::RETURN_ASSIGN_TO_MERCHANT_CANCEL  => 'dark',
        ParcelStatus::RETURN_RECEIVED_BY_MERCHANT_CANCEL=> 'dark',
        ParcelStatus::PARTIAL_DELIVERED_CANCEL          => 'dark',
    ];

    /**
     * Initialize caches from ParcelStatus interface once.
     */
    protected static function boot(): void
    {
        if (!empty(self::$namesByValue)) {
            return;
        }

        $ref = new \ReflectionClass(ParcelStatus::class);
        $constants = $ref->getConstants();

        // NAME => value
        self::$valuesByName = $constants;

        // value => NAME
        foreach ($constants as $name => $value) {
            self::$namesByValue[(int)$value] = $name;
        }
    }

    /**
     * Get constant NAME by numeric value.
     * e.g. 9 => "DELIVERED"
     */
    public static function nameOf(int $value): ?string
    {
        self::boot();
        return self::$namesByValue[$value] ?? null;
    }

    /**
     * Build i18n translation key from value.
     * e.g. DELIVERED => "parcel.status.delivered"
     *      RETURN_RECEIVED_BY_MERCHANT => "parcel.status.return.received.by.merchant"
     */
    public static function translationKey(int $value): ?string
    {
        $name = self::nameOf($value);
        if (!$name) {
            return null;
        }
        return 'parcel.status.' . Str::of($name)->lower()->replace('_', '.');
    }

    /**
     * Get localized label for status value with graceful fallback.
     * If translation key is missing, returns a humanized Title-Case label.
     */
    public static function label(int $value, ?string $locale = null): string
    {
        self::boot();

        $key = self::translationKey($value);
        if ($key) {
            $translated = __($key, locale: $locale ?? App::getLocale());
            if ($translated !== $key) {
                return $translated;
            }
        }

        $name = self::nameOf($value) ?? 'UNKNOWN';
        return Str::of($name)->lower()->replace('_', ' ')->title()->toString();
    }

    /**
     * Get Bootstrap badge class for status value (e.g. "badge bg-success").
     */
    public static function badgeClass(int $value): string
    {
        self::boot();
        $suffix = self::$badgeMap[$value] ?? 'secondary';
        return 'badge bg-' . $suffix;
    }

    /**
     * Return simple [value => label] map (useful for selects).
     */
    public static function options(?string $locale = null): array
    {
        self::boot();
        $opts = [];
        foreach (self::$namesByValue as $value => $_name) {
            $opts[$value] = self::label($value, $locale);
        }
        return $opts;
    }

    /**
     * Whether the given status is a cancellation (NAME ends with _CANCEL).
     */
    public static function isCanceled(int $value): bool
    {
        $name = self::nameOf($value) ?? '';
        return Str::endsWith($name, '_CANCEL');
    }

    /**
     * Whether the given status is part of the return flow (NAME starts with RETURN_).
     */
    public static function isReturnFlow(int $value): bool
    {
        $name = self::nameOf($value) ?? '';
        return Str::startsWith($name, 'RETURN_');
    }

    /**
     * Get full status list for tables/APIs:
     * [
     *   { id, name, label, class },
     *   ...
     * ]
     */
    public static function getStatusList(?string $locale = null): array
    {
        self::boot();
        $list = [];
        foreach (self::$namesByValue as $value => $name) {
            $list[] = [
                'id'    => $value,
                'name'  => $name,
                'label' => self::label($value, $locale),
                'class' => self::badgeClass($value),
            ];
        }
        // Optional: sort by id ASC to be deterministic
        usort($list, static fn ($a, $b) => $a['id'] <=> $b['id']);
        return $list;
    }
}
