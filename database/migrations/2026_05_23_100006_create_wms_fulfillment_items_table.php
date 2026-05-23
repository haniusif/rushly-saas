<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wms_fulfillment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fulfillment_id')->constrained('wms_fulfillments');
            $table->foreignId('product_id')->constrained('wms_products');
            $table->foreignId('location_id')->constrained('wms_locations');
            $table->unsignedInteger('quantity_required');
            $table->unsignedInteger('quantity_picked')->default(0);
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_fulfillment_items');
    }
};
