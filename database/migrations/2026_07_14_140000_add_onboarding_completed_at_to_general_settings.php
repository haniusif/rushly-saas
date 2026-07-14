<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Marks a tenant as having finished the first-run setup wizard. NULL means
     * the wizard is still due; a timestamp means "done" (either the last step
     * saved or the owner clicked Skip all).
     *
     * Backfills existing tenants with `now()` so we don't force setup on
     * long-standing customers when the feature ships — only new signups are
     * meant to see the wizard.
     */
    public function up(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('general_settings', 'onboarding_completed_at')) {
                $table->timestamp('onboarding_completed_at')->nullable()->after('parent_company_id');
            }
        });

        DB::table('general_settings')
            ->whereNull('onboarding_completed_at')
            ->update(['onboarding_completed_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            if (Schema::hasColumn('general_settings', 'onboarding_completed_at')) {
                $table->dropColumn('onboarding_completed_at');
            }
        });
    }
};
