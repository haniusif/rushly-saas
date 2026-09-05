<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Give hubs a real city.
 *
 * A hub is the physical origin of every shipment picked up from it, but the
 * table carried only a free-text name and address. Carrier integrations that
 * need an origin locality had nothing structured to read: the EcoExpress
 * mapper resolves the hub NAME ("Dubai", "AbuDhabi") against a lookup table,
 * which works for hubs named after their emirate and fails for anything named
 * "Warehouse 3" or "DXB North".
 *
 * Nullable on purpose — existing hubs have no city and operators should not be
 * blocked from saving one until they fill it in. The backfill below sets it
 * where the hub name matches a known city, which covers the current rows.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('hubs') || Schema::hasColumn('hubs', 'city_id')) {
            return;
        }

        Schema::table('hubs', function (Blueprint $table) {
            $table->unsignedBigInteger('city_id')->nullable()->after('company_id');
            $table->index('city_id');
        });

        $this->backfillFromName();
    }

    public function down(): void
    {
        if (! Schema::hasTable('hubs') || ! Schema::hasColumn('hubs', 'city_id')) {
            return;
        }

        Schema::table('hubs', function (Blueprint $table) {
            $table->dropIndex(['city_id']);
            $table->dropColumn('city_id');
        });
    }

    /**
     * Best-effort backfill: match the hub's name to a city by en_name or name,
     * ignoring case and spacing so "AbuDhabi" finds "Abu Dhabi".
     *
     * Deliberately conservative — only an unambiguous single match is written.
     * A hub that matches nothing, or more than one city, is left null for a
     * human to set rather than guessed at.
     */
    private function backfillFromName(): void
    {
        if (! Schema::hasTable('cities')) {
            return;
        }

        $cities = DB::table('cities')->get(['id', 'name', 'en_name']);
        $norm   = fn ($v) => strtolower(preg_replace('/\s+/', '', (string) $v) ?? '');

        $byName = [];
        foreach ($cities as $c) {
            foreach ([$c->en_name, $c->name] as $label) {
                $key = $norm($label);
                if ($key === '') {
                    continue;
                }
                // Collect ids per key so an ambiguous name can be detected
                // rather than silently taking the first match.
                $byName[$key][] = $c->id;
            }
        }

        foreach (DB::table('hubs')->get(['id', 'name']) as $hub) {
            $key = $norm($hub->name);
            $ids = array_unique($byName[$key] ?? []);

            if (count($ids) !== 1) {
                continue;
            }

            DB::table('hubs')->where('id', $hub->id)->update(['city_id' => reset($ids)]);
        }
    }
};
