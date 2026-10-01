<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Normalize general_settings.currency to ISO currency codes.
 *
 * The column historically held a mix of codes ("AED", "SAR") and symbols
 * ("﷼", "$"). Symbols are ambiguous — in particular "﷼" is shared by SAR, IRR,
 * OMR, QAR and YER, so a symbol lookup resolved the Saudi tenants to Iranian
 * Rial. Going forward the UI stores codes and the Currency relation keys on
 * `code`; this backfills existing rows.
 *
 * Mapping reflects the actual tenants in this deployment (all "﷼" rows are KSA
 * companies, all "$" rows are trial/test tenants).
 */
return new class extends Migration
{
    public function up()
    {
        DB::table('general_settings')->where('currency', '﷼')->update(['currency' => 'SAR']);
        DB::table('general_settings')->where('currency', '$')->update(['currency' => 'USD']);

        // NAVIX (navix.com.sa, Saudi) had no currency set.
        DB::table('general_settings')
            ->where('id', 12)
            ->where(function ($q) {
                $q->whereNull('currency')->orWhere('currency', '');
            })
            ->update(['currency' => 'SAR']);
    }

    public function down()
    {
        // Best-effort reverse (symbols are ambiguous, so this is not exact).
        DB::table('general_settings')->where('currency', 'SAR')->update(['currency' => '﷼']);
        DB::table('general_settings')->where('currency', 'USD')->update(['currency' => '$']);
    }
};
