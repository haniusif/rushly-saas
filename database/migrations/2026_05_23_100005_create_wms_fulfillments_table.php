<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wms_fulfillments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('fulfillment_number')->unique();
            $table->foreignId('parcel_id')->constrained('parcels');
            $table->foreignId('hub_id')->constrained('hubs');
            $table->foreignId('merchant_id')->constrained('merchants');
            $table->string('status')->default('pending');
            $table->foreignId('picker_id')->nullable()->constrained('users');
            $table->foreignId('packer_id')->nullable()->constrained('users');
            $table->timestamp('picked_at')->nullable();
            $table->timestamp('packed_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('sla_deadline')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_fulfillments');
    }
};
