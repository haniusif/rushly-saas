<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('general_settings', 'parent_company_id')) {
                $table->unsignedBigInteger('parent_company_id')
                    ->nullable()
                    ->index()
                    ->comment('Company (general_settings.id) that created this tenant. NULL = created by super-admin.');
            }
        });
    }

    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            if (Schema::hasColumn('general_settings', 'parent_company_id')) {
                $table->dropIndex(['parent_company_id']);
                $table->dropColumn('parent_company_id');
            }
        });
    }
};
