<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4b instrumentation.
 *
 * 1) parcel_ratings — captures real customer satisfaction per delivered parcel.
 *    UNIQUE(parcel_id) so a customer can only rate once; the rating capture
 *    endpoint uses upsert semantics to allow editing.
 *
 * 2) supports.driver_id — nullable FK so support tickets can be linked to a
 *    specific driver. Replaces the "complaints = all tickets" proxy used by
 *    DriverPerformanceService.
 *
 * Both are nullable / additive; no existing query breaks.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('parcel_ratings')) {
            Schema::create('parcel_ratings', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('company_id')->index();
                $t->unsignedBigInteger('parcel_id');
                $t->unsignedBigInteger('deliveryman_id')->nullable()->index();
                $t->unsignedBigInteger('merchant_id')->nullable()->index();
                $t->string('customer_phone', 32)->nullable();
                $t->unsignedTinyInteger('rating'); // 1..5
                $t->text('comment')->nullable();
                $t->string('source', 24)->default('public'); // public | admin | merchant | api
                $t->timestamps();

                $t->unique('parcel_id', 'parcel_ratings_parcel_id_unique');
                $t->index(['company_id', 'created_at'], 'parcel_ratings_company_created_idx');
                $t->index(['deliveryman_id', 'created_at'], 'parcel_ratings_driver_created_idx');
            });
        }

        if (Schema::hasTable('supports') && ! Schema::hasColumn('supports', 'driver_id')) {
            Schema::table('supports', function (Blueprint $t) {
                $t->unsignedBigInteger('driver_id')->nullable()->after('user_id');
                $t->index('driver_id', 'supports_driver_id_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('supports') && Schema::hasColumn('supports', 'driver_id')) {
            Schema::table('supports', function (Blueprint $t) {
                $t->dropIndex('supports_driver_id_idx');
                $t->dropColumn('driver_id');
            });
        }
        Schema::dropIfExists('parcel_ratings');
    }
};
