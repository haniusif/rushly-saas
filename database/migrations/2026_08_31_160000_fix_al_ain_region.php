<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Al Ain sat under the Riyadh region.
 *
 * cities.emirate_id for Al Ain was 8 — Riyadh, a Saudi region — when Al Ain is
 * a city in the Abu Dhabi emirate (id 1). Every other UAE city pointed at the
 * right region; this was the only one wrong, which is what a copy-paste or an
 * off-by-one during seeding tends to look like.
 *
 * It does not affect the EcoExpress mapping, which keys on city_code rather
 * than the region, but anything grouping cities by region — coverage, rate
 * zones, reporting — placed Al Ain in Saudi Arabia.
 *
 * Guarded on the current value so a re-run does nothing and a region corrected
 * by hand in the meantime is not overwritten.
 */
return new class extends Migration {
    private const CITY   = 'Al Ain';
    private const WRONG  = 8;   // Riyadh
    private const RIGHT  = 1;   // Abu Dhabi

    public function up(): void
    {
        $this->move(self::WRONG, self::RIGHT);
    }

    public function down(): void
    {
        $this->move(self::RIGHT, self::WRONG);
    }

    private function move(int $from, int $to): void
    {
        if (! Schema::hasTable('cities') || ! Schema::hasColumn('cities', 'emirate_id')) {
            return;
        }

        // Only act when the target region actually exists and still carries
        // the name we expect — ids are not guaranteed across installs.
        if (Schema::hasTable('emirates')) {
            $expected = $to === self::RIGHT ? 'Abu Dhabi' : 'Riyadh';
            $name     = DB::table('emirates')->where('id', $to)->value('en_name');

            if ($name !== $expected) {
                return;
            }
        }

        DB::table('cities')
            ->where('en_name', self::CITY)
            ->where('emirate_id', $from)
            ->update(['emirate_id' => $to, 'updated_at' => now()]);
    }
};
