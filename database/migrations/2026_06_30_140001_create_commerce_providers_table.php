<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('commerce_providers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();          // 'salla', 'zid', 'shopify', 'woocommerce', ...
            $table->string('name', 64);
            $table->string('logo_url', 255)->nullable();
            $table->enum('status', ['active', 'disabled'])->default('active');
            // Capability flags. Mirrors the SupportsX marker interfaces on the
            // provider class so the admin UI can render capability chips
            // without instantiating the provider. Examples:
            //   ['oauth', 'webhooks', 'bulk_fetch', 'order_writeback', 'inventory_sync']
            $table->json('supports')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_providers');
    }
};
