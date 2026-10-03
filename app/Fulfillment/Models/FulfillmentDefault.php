<?php

namespace App\Fulfillment\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Two-tier super-admin fulfillment config. See migration for shape.
 *
 * Key entry point: `FulfillmentDefault::resolvedFor($companyId)`
 * returns the effective config for a tenant by merging their override
 * (if any) on top of the global row. Non-null tenant columns win.
 */
class FulfillmentDefault extends Model
{
    protected $table = 'fulfillment_defaults';

    protected $fillable = [
        'company_id',
        'default_strategy',
        'service_last_mile_strategy',
        'service_fulfillment_strategy',
        'service_storage_strategy',
        'updated_by',
    ];

    public const SERVICE_STRATEGY_MAP = [
        'last_mile'   => 'service_last_mile_strategy',
        'fulfillment' => 'service_fulfillment_strategy',
        'storage'     => 'service_storage_strategy',
    ];

    public static function global(): self
    {
        return static::firstOrCreate(['company_id' => null]);
    }

    public static function forTenant(int $companyId): ?self
    {
        return static::query()->where('company_id', $companyId)->first();
    }

    /**
     * Merge tenant override on top of global. Non-null tenant fields
     * win; nulls fall through to global (which may itself be null).
     *
     * Returns an assoc array — deliberately not an Eloquent model
     * because it's a virtual merged view.
     *
     * @return array{
     *   default_strategy: ?string,
     *   service_last_mile_strategy: ?string,
     *   service_fulfillment_strategy: ?string,
     *   service_storage_strategy: ?string
     * }
     */
    public static function resolvedFor(?int $companyId): array
    {
        $global = static::query()->whereNull('company_id')->first();
        $tenant = $companyId ? static::query()->where('company_id', $companyId)->first() : null;

        $fields = [
            'default_strategy',
            'service_last_mile_strategy',
            'service_fulfillment_strategy',
            'service_storage_strategy',
        ];

        $out = [];
        foreach ($fields as $f) {
            $out[$f] = $tenant->{$f} ?? $global->{$f} ?? null;
        }
        return $out;
    }

    /**
     * Given a merchant's `services` JSON array + a resolved defaults
     * bundle, pick the strategy for the FIRST matching service (in
     * the order the merchant listed them). Returns null when nothing
     * maps.
     *
     * @param array<int, string> $merchantServices
     */
    public static function strategyForMerchantServices(array $merchantServices, array $resolved): ?string
    {
        foreach ($merchantServices as $svc) {
            $col = self::SERVICE_STRATEGY_MAP[$svc] ?? null;
            if ($col && ! empty($resolved[$col])) {
                return $resolved[$col];
            }
        }
        return null;
    }
}
