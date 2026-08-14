<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('zatca_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('invoice_id')->nullable()->index();
            $table->unsignedBigInteger('merchant_id')->nullable()->index();

            $table->uuid('uuid');
            $table->string('invoice_number', 50);
            $table->string('invoice_type', 20)->default('simplified');
            $table->timestamp('issued_at');

            $table->string('buyer_name', 200)->nullable();
            $table->string('buyer_vat_number', 15)->nullable();
            $table->string('buyer_address', 255)->nullable();

            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('vat_rate', 5, 2)->default(15.00);
            $table->decimal('vat_amount', 14, 2)->default(0);
            $table->decimal('total_inclusive', 14, 2)->default(0);
            $table->string('currency', 3)->default('SAR');

            $table->longText('qr_payload')->nullable();
            $table->string('qr_image_path', 255)->nullable();
            $table->string('pdf_path', 255)->nullable();
            $table->longText('xml_payload')->nullable();

            $table->string('hash', 64)->nullable();
            $table->string('previous_hash', 64)->nullable();
            $table->unsignedBigInteger('sequence')->nullable();

            $table->string('status', 20)->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('generated_at')->nullable();

            $table->timestamps();

            $table->unique(['company_id', 'invoice_number']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'issued_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zatca_invoices');
    }
};
