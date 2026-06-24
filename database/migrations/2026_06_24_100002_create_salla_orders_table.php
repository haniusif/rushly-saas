<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('salla_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salla_merchant_id')->constrained('salla_merchants')->cascadeOnDelete();
            $table->unsignedBigInteger('salla_order_id')->index();
            $table->string('reference_id')->nullable();
            $table->string('status')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->text('shipping_address')->nullable();
            $table->string('shipping_city')->nullable();
            $table->decimal('total', 12, 2)->nullable();
            $table->string('currency', 8)->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->unique(['salla_merchant_id', 'salla_order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salla_orders');
    }
};
