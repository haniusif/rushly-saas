<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Phase 2 — flip the salla row in commerce_providers from disabled to
 * active so it shows up in the admin "Add integration" picker. Idempotent.
 *
 * The seed migration (2026_06_30_140004) intentionally seeds every
 * provider as disabled so the catalog is present but no UI surface
 * appears until a concrete impl lands. Salla is the first to land —
 * see app/Commerce/Providers/Salla/SallaProvider.
 */
return new class extends Migration {
    public function up(): void
    {
        DB::table('commerce_providers')
            ->where('code', 'salla')
            ->update([
                'status'     => 'active',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('commerce_providers')
            ->where('code', 'salla')
            ->update([
                'status'     => 'disabled',
                'updated_at' => now(),
            ]);
    }
};
