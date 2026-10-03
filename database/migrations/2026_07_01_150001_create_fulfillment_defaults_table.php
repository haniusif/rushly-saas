<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Business-logic super-admin config. Two-tier:
 *   - `company_id IS NULL` — global platform-wide default
 *   - `company_id = <id>`  — per-tenant override
 *
 * A per-tenant row's non-null columns win over the corresponding
 * global column; nulls fall through. That way a tenant can override
 * *just* their default_strategy while inheriting the service→strategy
 * mapping from global.
 *
 * Fields:
 *   - `default_strategy` — fallback when router matches no route AND
 *     the order's merchant has no service-mapped strategy
 *   - `service_<X>_strategy` — merchants offering that service route
 *     to this strategy when no explicit fulfillment_route matches
 *
 * Not enforced by UNIQUE(company_id) — MySQL treats each NULL as
 * distinct, so a naive UNIQUE would allow multiple global rows. App
 * code uses `updateOrCreate(['company_id' => ...], [...])` for atomic
 * upserts on this pair.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('fulfillment_defaults', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();  // null = global
            $table->string('default_strategy', 32)->nullable();
            $table->string('service_last_mile_strategy', 32)->nullable();
            $table->string('service_fulfillment_strategy', 32)->nullable();
            $table->string('service_storage_strategy', 32)->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fulfillment_defaults');
    }
};
