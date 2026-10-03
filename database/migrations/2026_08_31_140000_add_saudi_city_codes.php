<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fill in the missing city codes.
 *
 * Twenty of the twenty-eight cities — every Saudi one — had city_code NULL,
 * while the eight UAE cities were coded. That matters because city_code is not
 * decorative: ParcelController reads it as `originCode` / `destinationCode`
 * when building a carrier label, so an uncoded city ships with "-" in the
 * station fields, and the EcoExpress location mapper keys on it too.
 *
 * Codes are IATA city/airport codes wherever one exists, since that is what
 * carriers in the region actually key on:
 *
 *   RUH Riyadh    JED Jeddah    MED Madinah   DMM Dammam    AHB Abha
 *   TIF Taif      YNB Yanbu     DHA Dhahran   QJB Jubail    HOF Al-Ahsa
 *   KMX Khamis Mushait          ELQ Buraidah  TUU Tabuk     HAS Hail
 *   EAM Najran    GIZ Jazan
 *
 * Four places have no IATA code of their own — Al Kharj, Makkah, Khobar and
 * Qatif are served by a neighbouring airport — so they get a derived
 * three-letter code (AKH, MAK, KHB, QTF). These are marked below because they
 * are OUR convention, not a standard: if a carrier rejects them, that is where
 * to look first.
 *
 * Only NULL/empty codes are written. The eight existing UAE codes are left
 * exactly as they are: DXD, AAN and FJR disagree with EcoExpress's spelling of
 * the same emirate (DXB, AIN, FUJ), but they are already consumed by another
 * carrier's label builder, so re-coding them is a separate decision with its
 * own blast radius. EcoExpress absorbs the difference in its LocationMapper.
 */
return new class extends Migration {
    /**
     * en_name => [code, is_iata]
     */
    private const CODES = [
        'Riyadh'         => ['RUH', true],
        'Al Kharj'       => ['AKH', false],   // no IATA — served by RUH
        'Jeddah'         => ['JED', true],
        'Makkah'         => ['MAK', false],   // no airport — served by JED
        'Taif'           => ['TIF', true],
        'Madinah'        => ['MED', true],
        'Yanbu'          => ['YNB', true],
        'Dammam'         => ['DMM', true],
        'Khobar'         => ['KHB', false],   // no IATA — served by DMM
        'Dhahran'        => ['DHA', true],
        'Jubail'         => ['QJB', true],
        'Al-Ahsa'        => ['HOF', true],
        'Qatif'          => ['QTF', false],   // no IATA — served by DMM
        'Abha'           => ['AHB', true],
        'Khamis Mushait' => ['KMX', true],
        'Buraidah'       => ['ELQ', true],
        'Tabuk'          => ['TUU', true],
        'Hail'           => ['HAS', true],
        'Najran'         => ['EAM', true],
        'Jazan'          => ['GIZ', true],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('cities') || ! Schema::hasColumn('cities', 'city_code')) {
            return;
        }

        // Codes already in use, so a new one can never collide with an
        // existing city's.
        $taken = DB::table('cities')
            ->whereNotNull('city_code')
            ->where('city_code', '!=', '')
            ->pluck('city_code')
            ->map(fn ($c) => strtoupper((string) $c))
            ->all();

        foreach (self::CODES as $name => [$code, $isIata]) {
            if (in_array($code, $taken, true)) {
                continue;
            }

            // Only fill blanks — never overwrite a code someone has set.
            $updated = DB::table('cities')
                ->where('en_name', $name)
                ->where(fn ($q) => $q->whereNull('city_code')->orWhere('city_code', ''))
                ->update(['city_code' => $code, 'updated_at' => now()]);

            if ($updated > 0) {
                $taken[] = $code;
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('cities') || ! Schema::hasColumn('cities', 'city_code')) {
            return;
        }

        // Clear only the codes this migration could have written, and only
        // where the value still matches — so a code edited by hand afterwards
        // survives a rollback.
        foreach (self::CODES as $name => [$code, $isIata]) {
            DB::table('cities')
                ->where('en_name', $name)
                ->where('city_code', $code)
                ->update(['city_code' => null, 'updated_at' => now()]);
        }
    }
};
