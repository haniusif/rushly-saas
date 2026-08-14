<?php

namespace App\Oms\Normalization;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Best-effort match of a provider-reported city/area string into the
 * tenant's local cities/areas tables. Case-insensitive, English-name-first
 * with Arabic-name fallback, cached for 24h per (tenant, city string).
 *
 * Returns [city_id?, area_id?] — either or both may be null when no
 * confident match exists. Downstream code (Phase 5 OMS OrderService)
 * decides whether the order is still acceptable with a freeform city
 * string only.
 *
 * Not exhaustive: this is a Phase 4 baseline. Phase 4.5 candidates —
 * fuzzy match (Levenshtein), synonym table ("Ar Riyadh" ↔ "Riyadh"),
 * per-provider city-code column on cities (already precedent set by
 * Logestechs's villageId caching in `logestechs_villages` / similar).
 */
class AddressResolver
{
    private const CACHE_TTL = 86400;    // 24h

    /**
     * @return array{city_id: ?int, area_id: ?int}
     */
    public function resolve(?string $cityName, ?string $areaName, ?int $companyId): array
    {
        $cityId = $this->resolveCity($cityName, $companyId);
        $areaId = $cityId ? $this->resolveArea($areaName, $cityId) : null;

        return ['city_id' => $cityId, 'area_id' => $areaId];
    }

    private function resolveCity(?string $name, ?int $companyId): ?int
    {
        $name = trim((string) $name);
        if ($name === '') return null;

        $key = 'commerce.address.city:' . ($companyId ?? 0) . ':' . Str::lower($name);
        return Cache::remember($key, self::CACHE_TTL, function () use ($name) {
            // Best-effort: swallow lookup faults (missing table in a test
            // env, transient connection loss) and return null so the DTO
            // still carries the freeform city string. The mapper's job is
            // to shape the payload, not to guard the DB.
            try {
                $lookup = Str::lower($name);
                $row = DB::table('cities')
                    ->where('is_active', 1)
                    ->where(function ($q) use ($lookup) {
                        $q->whereRaw('LOWER(en_name) = ?', [$lookup])
                          ->orWhereRaw('LOWER(name) = ?', [$lookup]);
                    })
                    ->orderBy('sorting')
                    ->first(['id']);
                return $row?->id;
            } catch (\Throwable $e) {
                Log::debug('commerce.address.city_lookup_failed', [
                    'name'  => $name,
                    'error' => $e->getMessage(),
                ]);
                return null;
            }
        });
    }

    private function resolveArea(?string $name, int $cityId): ?int
    {
        $name = trim((string) $name);
        if ($name === '') return null;

        $key = 'commerce.address.area:' . $cityId . ':' . Str::lower($name);
        return Cache::remember($key, self::CACHE_TTL, function () use ($name, $cityId) {
            try {
                $lookup = Str::lower($name);
                $row = DB::table('areas')
                    ->where('city_id', $cityId)
                    ->where('is_active', 1)
                    ->where(function ($q) use ($lookup) {
                        $q->whereRaw('LOWER(en_name) = ?', [$lookup])
                          ->orWhereRaw('LOWER(name) = ?', [$lookup]);
                    })
                    ->orderBy('sorting')
                    ->first(['id']);
                return $row?->id;
            } catch (\Throwable $e) {
                Log::debug('commerce.address.area_lookup_failed', [
                    'name'    => $name,
                    'city_id' => $cityId,
                    'error'   => $e->getMessage(),
                ]);
                return null;
            }
        });
    }

    /** Test / admin utility — invalidate a cached lookup pair. */
    public function forget(?string $cityName, ?int $companyId): void
    {
        if ($cityName) {
            Cache::forget('commerce.address.city:' . ($companyId ?? 0) . ':' . Str::lower(trim($cityName)));
        }
    }
}
