<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('general_settings', 'login_layout')) {
                $table->string('login_layout', 16)->nullable()->after('text_color');
            }
        });
        Schema::table('merchants', function (Blueprint $table) {
            if (!Schema::hasColumn('merchants', 'login_layout')) {
                $table->string('login_layout', 16)->nullable()->after('density');
            }
        });
    }

    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            if (Schema::hasColumn('general_settings', 'login_layout')) {
                $table->dropColumn('login_layout');
            }
        });
        Schema::table('merchants', function (Blueprint $table) {
            if (Schema::hasColumn('merchants', 'login_layout')) {
                $table->dropColumn('login_layout');
            }
        });
    }
};
