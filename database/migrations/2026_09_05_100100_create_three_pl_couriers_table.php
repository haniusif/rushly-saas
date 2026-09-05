<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-tenant map of 3PL carrier -> the courier record that represents it.
 *
 * The Shipping module's carriers (Logestechs, EcoExpress) each have a
 * shipping_connections row to hang this on. The legacy four - Panda, Zajel,
 * Aramex, J&T - have no per-tenant row at all: their credentials come from
 * config('services.*') via env, globally. So they need a home of their own,
 * and this is it.
 *
 * Keyed by carrier CODE rather than by connection because that is the only
 * identifier the legacy paths carry: parcels_3pl.parcel_3pl_name. The
 * Shipping module reads its connection column first and falls back here, so a
 * tenant that wants one courier for all of EcoExpress can set it once.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('three_pl_couriers')) {
            return;
        }

        Schema::create('three_pl_couriers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            // 'panda', 'zajel', 'aramex', 'jet', 'logestechs', 'ecoexpress'.
            $table->string('carrier_code', 40);
            $table->unsignedBigInteger('delivery_man_id');
            $table->timestamps();

            // One courier per carrier per tenant - the lookup is a point read.
            $table->unique(['company_id', 'carrier_code']);
            $table->index('delivery_man_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('three_pl_couriers');
    }
};
