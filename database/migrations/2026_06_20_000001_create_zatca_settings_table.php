<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('zatca_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();

            $table->string('seller_name_en', 200);
            $table->string('seller_name_ar', 200);
            $table->string('vat_number', 15);
            $table->string('cr_number', 30)->nullable();

            $table->string('address_street_en', 255)->nullable();
            $table->string('address_street_ar', 255)->nullable();
            $table->string('building_number', 10)->nullable();
            $table->string('district_en', 100)->nullable();
            $table->string('district_ar', 100)->nullable();
            $table->string('city_en', 100)->nullable();
            $table->string('city_ar', 100)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('country_code', 2)->default('SA');

            $table->decimal('vat_rate', 5, 2)->default(15.00);
            $table->string('currency', 3)->default('SAR');
            $table->string('mode', 20)->default('sandbox');
            $table->boolean('enabled')->default(false);
            $table->boolean('auto_generate')->default(true);

            $table->string('invoice_prefix', 20)->default('ZAT-');
            $table->unsignedBigInteger('last_invoice_counter')->default(0);
            $table->string('last_invoice_hash', 64)->nullable();

            $table->timestamps();

            $table->unique('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zatca_settings');
    }
};
