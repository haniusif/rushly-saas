<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Optional per-tenant background image for the login screen. References
     * `uploads.id`; NULL means "no override" and the layout falls back to
     * its built-in gradient / plain background.
     */
    public function up(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('general_settings', 'login_bg')) {
                $table->unsignedBigInteger('login_bg')->nullable()->after('favicon');
            }
        });
    }

    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            if (Schema::hasColumn('general_settings', 'login_bg')) {
                $table->dropColumn('login_bg');
            }
        });
    }
};
