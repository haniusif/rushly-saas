<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wms_grn_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grn_id')->constrained('wms_grn');
            $table->foreignId('product_id')->constrained('wms_products');
            $table->foreignId('location_id')->constrained('wms_locations');
            $table->unsignedInteger('expected_qty');
            $table->unsignedInteger('received_qty');
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('condition')->default('good');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_grn_items');
    }
};
