<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wms_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->foreignId('merchant_id')->constrained('merchants');
            $table->foreignId('hub_id')->constrained('hubs');
            $table->string('name');
            $table->string('sku')->unique();
            $table->string('barcode')->nullable();
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->decimal('weight', 8, 3)->nullable();
            $table->json('dimensions')->nullable();
            $table->string('unit')->default('piece');
            $table->unsignedInteger('reorder_point')->default(0);
            $table->boolean('track_expiry')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['company_id', 'merchant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_products');
    }
};
