<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('daftra_invoice_id')->nullable()->after('qoyod_sync_error');
            $table->string('daftra_invoice_reference')->nullable()->after('daftra_invoice_id');
            $table->unsignedBigInteger('daftra_payment_id')->nullable()->after('daftra_invoice_reference');
            $table->string('daftra_sync_status', 16)->nullable()->after('daftra_payment_id');
            $table->timestamp('daftra_synced_at')->nullable()->after('daftra_sync_status');
            $table->text('daftra_sync_error')->nullable()->after('daftra_synced_at');
            $table->index('daftra_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['daftra_invoice_id']);
            $table->dropColumn([
                'daftra_invoice_id', 'daftra_invoice_reference', 'daftra_payment_id',
                'daftra_sync_status', 'daftra_synced_at', 'daftra_sync_error',
            ]);
        });
    }
};
