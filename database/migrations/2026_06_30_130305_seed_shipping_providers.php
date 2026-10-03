<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the shipping_providers catalog. Idempotent — safe to re-run. Currently
 * registers Logestechs only; future providers (OTO, generic Aramex/SMSA wired
 * through the new module) get added the same way.
 */
return new class extends Migration {
    public function up(): void
    {
        $rows = [
            [
                'code'     => 'logestechs',
                'name'     => 'Logestechs',
                'logo_url' => '/images/partners/logestechs.svg',
                'status'   => 'active',
                'supports' => json_encode(['cancel', 'awb_pdf', 'tracking', 'villages']),
            ],
        ];

        foreach ($rows as $row) {
            DB::table('shipping_providers')->updateOrInsert(
                ['code' => $row['code']],
                array_merge($row, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }

    public function down(): void
    {
        DB::table('shipping_providers')->whereIn('code', ['logestechs'])->delete();
    }
};
