<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shipping_providers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();          // 'logestechs', 'oto', ...
            $table->string('name', 64);
            $table->string('logo_url', 255)->nullable();
            $table->enum('status', ['active', 'disabled'])->default('active');
            // Capability flags surfaced via SupportsX marker interfaces on the
            // provider class. Stored here so the UI can render capability chips
            // without instantiating the provider.
            $table->json('supports')->nullable();           // ['webhooks', 'awb_pdf', 'cancel']
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_providers');
    }
};
