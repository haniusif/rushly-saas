<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Per-tenant timezone. Middleware SetTenantTimezone reads this at request
     * time and overrides config('app.timezone'). NULL falls back to the app
     * default (Asia/Riyadh).
     */
    public function up(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('general_settings', 'timezone')) {
                $table->string('timezone', 64)->nullable()->after('currency');
            }
        });
    }

    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            if (Schema::hasColumn('general_settings', 'timezone')) {
                $table->dropColumn('timezone');
            }
        });
    }
};
