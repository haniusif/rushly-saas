<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('woocommerce_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('merchant_id')->nullable()->index();
            $table->unsignedBigInteger('parcel_id')->nullable()->index();

            // WP doesn't have a central "store id" — each merchant owns their
            // own WordPress install. We identify a store by its site URL.
            // WooCommerce order IDs are bigint per-site, so the unique key is
            // (site_url, wc_order_id). Keep site_url <=191 chars so the
            // composite unique stays under MySQL's 1000-byte key limit.
            $table->string('site_url', 191)->index();
            $table->unsignedBigInteger('wc_order_id')->index();

            // Per-store bearer used when rushly-saas POSTs back to the WP REST
            // endpoint. Stored at link level (not just in integration_settings)
            // because each WP install has its own token.
            $table->string('site_token')->nullable();

            $table->string('wc_awb_number')->nullable();
            $table->string('last_pushed_status')->nullable();
            $table->timestamp('last_pushed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['site_url', 'wc_order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('woocommerce_orders');
    }
};
