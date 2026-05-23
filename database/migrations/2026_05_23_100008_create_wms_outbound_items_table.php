<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wms_outbound_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outbound_id')->constrained('wms_outbound');
            $table->foreignId('product_id')->constrained('wms_products');
            $table->foreignId('location_id')->constrained('wms_locations');
            $table->unsignedInteger('quantity');
            $table->string('batch_number')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_outbound_items');
    }
};
