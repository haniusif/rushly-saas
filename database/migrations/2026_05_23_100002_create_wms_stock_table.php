<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wms_stock', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->foreignId('product_id')->constrained('wms_products');
            $table->foreignId('location_id')->constrained('wms_locations');
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('reserved_qty')->default(0);
            $table->string('batch_number')->nullable();
            $table->string('lot_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->timestamps();
            // batch_number can be null — composite unique still works in InnoDB.
            $table->unique(['product_id', 'location_id', 'batch_number'], 'wms_stock_product_loc_batch_uq');
            $table->index(['company_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_stock');
    }
};
