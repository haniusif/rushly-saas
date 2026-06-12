<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Merchant geographic coverage:
 *   - merchants.covers_all_cities  (bool, default true)
 *       When true, the merchant operates in every city of every country
 *       it's linked to; the per-city pivot is ignored.
 *
 *   - merchant_countries (m2m)
 *       Required: every merchant must have at least one country.
 *
 *   - merchant_cities (m2m)
 *       Optional, only meaningful when covers_all_cities = false.
 *
 * Backfill: every existing merchant is attached to country_id = 1
 * (Saudi Arabia from CountrySeeder) and left with covers_all_cities
 * = true, so no merchant silently loses coverage on deploy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->boolean('covers_all_cities')->default(true)->after('status');
        });

        Schema::create('merchant_countries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('merchants')->cascadeOnDelete();
            $table->foreignId('country_id')->constrained('countries')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['merchant_id', 'country_id']);
            $table->index('country_id');
        });

        Schema::create('merchant_cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('merchants')->cascadeOnDelete();
            $table->foreignId('city_id')->constrained('cities')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['merchant_id', 'city_id']);
            $table->index('city_id');
        });

        // Backfill — link every existing merchant to Saudi (country_id=1)
        // if the row exists. Skip silently if the seeder hasn't run.
        $saudiId = DB::table('countries')->where('code', 'KSA')->value('id') ?? 1;
        $hasSaudi = DB::table('countries')->where('id', $saudiId)->exists();
        if ($hasSaudi) {
            $now = now();
            $merchantIds = DB::table('merchants')->pluck('id');
            foreach ($merchantIds as $mid) {
                DB::table('merchant_countries')->insertOrIgnore([
                    'merchant_id' => $mid,
                    'country_id'  => $saudiId,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_cities');
        Schema::dropIfExists('merchant_countries');
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn('covers_all_cities');
        });
    }
};
