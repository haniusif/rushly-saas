<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Line items for an OMS order. One row per OrderItemDTO the mapper
 * produced. Rewritten in full on each order update — order_id + a
 * per-order sort_order together let us re-emit the exact line list the
 * storefront sent without needing to reconcile individual item ids.
 *
 * remote_product_id / remote_variant_id let Phase 7 inventory-sync push
 * stock updates back to the right variant on the source storefront.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);

            $table->string('sku', 191)->nullable()->index();
            $table->string('name', 255);
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price',  12, 2)->default(0);
            $table->decimal('total_price', 12, 2)->default(0);
            $table->string('currency', 8)->default('SAR');

            $table->string('remote_product_id', 191)->nullable()->index();
            $table->string('remote_variant_id', 191)->nullable();

            $table->json('extra')->nullable();

            $table->timestamps();

            $table->index(['order_id', 'sort_order'], 'oi_order_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
