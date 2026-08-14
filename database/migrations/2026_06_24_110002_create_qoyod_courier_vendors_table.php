<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('qoyod_courier_vendors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('courier_key', 32);
            $table->string('display_name')->nullable();
            $table->unsignedBigInteger('qoyod_vendor_id')->nullable();
            $table->string('qoyod_sync_status', 16)->default('pending');
            $table->timestamp('qoyod_synced_at')->nullable();
            $table->text('qoyod_sync_error')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'courier_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qoyod_courier_vendors');
    }
};
