<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('salla_shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salla_order_id')->constrained('salla_orders')->cascadeOnDelete();
            $table->string('rushly_tracking_number')->unique();
            $table->string('salla_shipment_id')->nullable()->index();
            $table->string('awb_number')->nullable();
            $table->string('label_url')->nullable();
            $table->string('status')->default('pending');
            $table->string('last_rushly_status')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salla_shipments');
    }
};
