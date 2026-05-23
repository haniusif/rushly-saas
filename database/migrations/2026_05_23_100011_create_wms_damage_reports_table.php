<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wms_damage_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->foreignId('product_id')->constrained('wms_products');
            $table->foreignId('location_id')->constrained('wms_locations');
            $table->foreignId('reported_by')->constrained('users');
            $table->unsignedInteger('quantity_damaged');
            $table->string('cause');
            $table->json('photos')->nullable();
            $table->text('notes')->nullable();
            $table->string('action_taken')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_damage_reports');
    }
};
