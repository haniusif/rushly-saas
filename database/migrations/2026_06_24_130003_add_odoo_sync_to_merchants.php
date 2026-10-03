<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->unsignedBigInteger('odoo_partner_id')->nullable()->after('daftra_sync_error');
            $table->string('odoo_sync_status', 16)->nullable()->after('odoo_partner_id');
            $table->timestamp('odoo_synced_at')->nullable()->after('odoo_sync_status');
            $table->text('odoo_sync_error')->nullable()->after('odoo_synced_at');
            $table->index('odoo_partner_id');
        });
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropIndex(['odoo_partner_id']);
            $table->dropColumn(['odoo_partner_id', 'odoo_sync_status', 'odoo_synced_at', 'odoo_sync_error']);
        });
    }
};
