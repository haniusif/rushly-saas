<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Declarative routing rules. FulfillmentRouter evaluates them in
 * priority order against an incoming OMS Order and picks the first
 * match. Conditions are all AND'd inside a route; if none match the
 * router returns null and the Fulfillment stays `pending` for manual
 * assignment.
 *
 * All condition columns are nullable — null means "don't filter on
 * this column". e.g. a route with only `source_provider_code='salla'`
 * set matches every Salla order regardless of merchant / city / total.
 *
 * Target: strategy is required, the *_id targets are strategy-specific.
 * shipping_connection_id is required for threepl_dropship; hub_id is
 * optional for wms (null → strategy picks default warehouse).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('fulfillment_routes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();

            $table->string('name', 191);
            $table->unsignedInteger('priority')->default(100);    // lower = higher priority (router picks first match)
            $table->boolean('is_active')->default(true);

            // Conditions (all AND'd; nullable = don't filter)
            $table->unsignedBigInteger('merchant_id')->nullable()->index();
            $table->string('source_provider_code', 32)->nullable()->index();
            $table->unsignedBigInteger('shipping_city_id')->nullable();
            $table->string('shipping_country', 100)->nullable();
            $table->decimal('min_total', 12, 2)->nullable();
            $table->decimal('max_total', 12, 2)->nullable();
            $table->boolean('is_cod')->nullable();                 // null = don't filter; true/false = require COD / non-COD

            // Target strategy + strategy-specific fields
            $table->string('strategy', 32);                        // 'wms', 'threepl_dropship', 'vendor_direct', 'merchant_self'
            $table->unsignedBigInteger('shipping_connection_id')->nullable();
            $table->unsignedBigInteger('hub_id')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'is_active', 'priority'], 'fr_company_active_priority_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fulfillment_routes');
    }
};
