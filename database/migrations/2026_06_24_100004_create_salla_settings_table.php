<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('salla_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salla_merchant_id')->unique()->constrained('salla_merchants')->cascadeOnDelete();
            $table->boolean('auto_create_parcel')->default(true);
            $table->string('trigger_status')->default('payment_pending');
            $table->unsignedBigInteger('default_rushly_shop_id')->nullable();
            $table->unsignedBigInteger('default_city_id')->nullable();
            $table->unsignedBigInteger('default_category_id')->nullable();
            $table->unsignedBigInteger('default_delivery_type_id')->nullable();
            $table->string('support_email')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salla_settings');
    }
};
