<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->unsignedBigInteger('qoyod_customer_id')->nullable()->after('updated_at');
            $table->string('qoyod_sync_status', 16)->nullable()->after('qoyod_customer_id');
            $table->timestamp('qoyod_synced_at')->nullable()->after('qoyod_sync_status');
            $table->text('qoyod_sync_error')->nullable()->after('qoyod_synced_at');
            $table->index('qoyod_customer_id');
        });
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropIndex(['qoyod_customer_id']);
            $table->dropColumn(['qoyod_customer_id', 'qoyod_sync_status', 'qoyod_synced_at', 'qoyod_sync_error']);
        });
    }
};
