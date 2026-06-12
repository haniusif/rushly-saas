<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two changes in one migration because they're conceptually tied:
 *
 *   1) merchants gets three service flags. A merchant with `has_fulfillment`
 *      is one whose orders go through the WMS pick/pack workflow (their
 *      WMS catalog is already linked via wms_products.merchant_id). The
 *      other two are informational classifications for now.
 *
 *   2) parcel_items captures the SKU lines attached at parcel-create time.
 *      Lightweight: it just stores the merchant's product picks alongside
 *      a snapshot of name+sku at the time of capture, so renaming a SKU
 *      later doesn't rewrite history.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->boolean('has_last_mile')->default(true)->after('status');
            $table->boolean('has_fulfillment')->default(false)->after('has_last_mile');
            $table->boolean('has_storage')->default(false)->after('has_fulfillment');
        });

        Schema::create('parcel_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parcel_id')->constrained('parcels')->cascadeOnDelete();
            $table->foreignId('wms_product_id')->nullable()->constrained('wms_products')->nullOnDelete();
            $table->string('sku', 120)->nullable();
            $table->string('name', 255);
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 13, 2)->nullable();
            $table->decimal('line_total', 13, 2)->nullable();
            $table->string('note', 500)->nullable();
            $table->timestamps();
            $table->index('parcel_id');
            $table->index('wms_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parcel_items');
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn(['has_last_mile', 'has_fulfillment', 'has_storage']);
        });
    }
};
