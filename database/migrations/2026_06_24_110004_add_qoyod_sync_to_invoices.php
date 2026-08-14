<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('qoyod_invoice_id')->nullable()->after('updated_at');
            $table->string('qoyod_invoice_reference')->nullable()->after('qoyod_invoice_id');
            $table->unsignedBigInteger('qoyod_payment_id')->nullable()->after('qoyod_invoice_reference');
            $table->string('qoyod_sync_status', 16)->nullable()->after('qoyod_payment_id');
            $table->timestamp('qoyod_synced_at')->nullable()->after('qoyod_sync_status');
            $table->text('qoyod_sync_error')->nullable()->after('qoyod_synced_at');
            $table->index('qoyod_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['qoyod_invoice_id']);
            $table->dropColumn([
                'qoyod_invoice_id', 'qoyod_invoice_reference', 'qoyod_payment_id',
                'qoyod_sync_status', 'qoyod_synced_at', 'qoyod_sync_error',
            ]);
        });
    }
};
