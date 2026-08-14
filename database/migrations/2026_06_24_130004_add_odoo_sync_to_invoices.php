<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('odoo_invoice_id')->nullable()->after('daftra_sync_error');
            $table->string('odoo_invoice_reference')->nullable()->after('odoo_invoice_id');
            $table->unsignedBigInteger('odoo_payment_id')->nullable()->after('odoo_invoice_reference');
            $table->string('odoo_sync_status', 16)->nullable()->after('odoo_payment_id');
            $table->timestamp('odoo_synced_at')->nullable()->after('odoo_sync_status');
            $table->text('odoo_sync_error')->nullable()->after('odoo_synced_at');
            $table->index('odoo_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['odoo_invoice_id']);
            $table->dropColumn([
                'odoo_invoice_id', 'odoo_invoice_reference', 'odoo_payment_id',
                'odoo_sync_status', 'odoo_synced_at', 'odoo_sync_error',
            ]);
        });
    }
};
