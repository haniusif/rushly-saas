<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('odoo_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->unique();
            $table->boolean('enabled')->default(false);
            $table->string('host_url')->nullable();             // e.g. https://mycompany.odoo.com
            $table->string('database', 64)->nullable();
            $table->string('username')->nullable();
            $table->text('api_key')->nullable();                // password or API key
            $table->unsignedInteger('cached_uid')->nullable();  // authenticated UID, cached
            $table->unsignedBigInteger('default_invoice_journal_id')->nullable();
            $table->unsignedBigInteger('default_bill_journal_id')->nullable();
            $table->unsignedBigInteger('default_payment_journal_id')->nullable();
            $table->unsignedBigInteger('default_product_id')->nullable();
            $table->unsignedBigInteger('default_tax_id')->nullable();
            $table->decimal('vat_percent', 5, 2)->default(15.00);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odoo_settings');
    }
};
