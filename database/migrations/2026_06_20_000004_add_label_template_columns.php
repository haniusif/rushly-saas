<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('general_settings') && ! Schema::hasColumn('general_settings', 'default_label_template')) {
            Schema::table('general_settings', function (Blueprint $table) {
                $table->string('default_label_template', 20)->default('generic')->after('invoice_prefix');
            });
        }

        if (Schema::hasTable('merchants') && ! Schema::hasColumn('merchants', 'label_template')) {
            Schema::table('merchants', function (Blueprint $table) {
                $table->string('label_template', 20)->nullable()->after('id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('general_settings', 'default_label_template')) {
            Schema::table('general_settings', function (Blueprint $table) {
                $table->dropColumn('default_label_template');
            });
        }
        if (Schema::hasColumn('merchants', 'label_template')) {
            Schema::table('merchants', function (Blueprint $table) {
                $table->dropColumn('label_template');
            });
        }
    }
};
