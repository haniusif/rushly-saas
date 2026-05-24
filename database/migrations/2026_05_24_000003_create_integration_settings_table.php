<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('integration_settings', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 32)->unique();          // 'salla' | 'zid' | 'shopify'
            $table->boolean('is_enabled')->default(false);
            $table->string('app_url')->nullable();             // bridge app URL, e.g. https://zid.rushly.test
            $table->text('writeback_token')->nullable();       // shared bearer for /internal/parcel-status
            $table->string('api_base')->nullable();            // platform API base URL
            $table->unsignedBigInteger('default_city_id')->nullable();
            $table->unsignedBigInteger('default_category_id')->nullable();
            $table->unsignedBigInteger('default_delivery_type_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        // Seed the three known platforms so the index page can render rows
        // without an empty-state fallback. Existing .env values remain the
        // source of truth until an admin edits the row in the UI.
        $now = now();
        DB::table('integration_settings')->insert([
            [
                'platform'    => 'salla',
                'is_enabled'  => true,
                'app_url'     => env('RUSHLY_SALLA_APP_URL'),
                'writeback_token' => env('RUSHLY_SALLA_WRITEBACK_TOKEN'),
                'api_base'    => env('SALLA_API_BASE', 'https://api.salla.dev/admin/v2'),
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'platform'    => 'zid',
                'is_enabled'  => true,
                'app_url'     => env('RUSHLY_ZID_APP_URL'),
                'writeback_token' => env('RUSHLY_ZID_WRITEBACK_TOKEN'),
                'api_base'    => env('ZID_API_BASE', 'https://api.zid.sa/v1'),
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'platform'    => 'shopify',
                'is_enabled'  => false,
                'app_url'     => null,
                'writeback_token' => null,
                'api_base'    => null,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_settings');
    }
};
