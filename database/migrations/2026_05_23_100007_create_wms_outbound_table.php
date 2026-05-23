<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wms_outbound', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('outbound_number')->unique();
            $table->foreignId('hub_id')->constrained('hubs');
            $table->foreignId('merchant_id')->constrained('merchants');
            $table->string('type');
            // Spec: `foreignId('fulfillment_id')->nullable()` — no constraint, just a soft pointer.
            $table->unsignedBigInteger('fulfillment_id')->nullable();
            $table->foreignId('processed_by')->constrained('users');
            $table->string('status')->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['company_id', 'status']);
            $table->index('fulfillment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_outbound');
    }
};
