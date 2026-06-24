<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('salla_orders') && ! Schema::hasTable('salla_order_links')) {
            Schema::rename('salla_orders', 'salla_order_links');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('salla_order_links') && ! Schema::hasTable('salla_orders')) {
            Schema::rename('salla_order_links', 'salla_orders');
        }
    }
};
