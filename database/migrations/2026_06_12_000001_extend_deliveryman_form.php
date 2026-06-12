<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extends the admin > deliveryman > create form to capture:
 *   - rich identity + address fields on the user
 *   - driver-type-specific employment + license + bank fields on delivery_man
 *   - new uploads for ID, iqama, contract, promissory note
 *   - two lookup tables (supplier_companies, operational_areas) for the
 *     outsourced + operational-area dropdowns
 *
 * All new columns are nullable so existing rows survive the migration
 * without backfill.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('general_settings')->onUpdate('cascade')->onDelete('cascade');
            $table->string('name', 191);
            $table->string('contact_phone', 50)->nullable();
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();
            $table->index('company_id');
        });

        Schema::create('operational_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('general_settings')->onUpdate('cascade')->onDelete('cascade');
            $table->string('name', 191);
            $table->string('code', 50)->nullable();
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();
            $table->index('company_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('name_en', 191)->nullable()->after('name');
            $table->string('alt_mobile', 50)->nullable()->after('mobile');
            $table->string('gender', 16)->nullable();                  // male / female
            $table->date('dob')->nullable();
            $table->string('nationality', 100)->nullable();
            $table->string('id_type', 20)->nullable();                 // national_id / iqama
            $table->string('id_number', 50)->nullable();
            $table->date('id_expiry')->nullable();
            $table->foreignId('id_image_id')->nullable()->constrained('uploads')->onDelete('set null');
            $table->string('district', 191)->nullable();
            $table->string('short_national_address', 50)->nullable();
        });

        Schema::table('delivery_man', function (Blueprint $table) {
            $table->string('driver_type', 30)->nullable()->after('user_id'); // freelancer / outsourced / company_courier
            $table->string('employee_number', 50)->nullable();
            $table->date('joining_date')->nullable();
            $table->date('contract_end_date')->nullable();
            $table->foreignId('direct_manager_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('license_number', 50)->nullable();
            $table->date('license_expiry')->nullable();
            $table->date('iqama_expiry')->nullable();
            $table->string('bank_account_no', 50)->nullable();
            $table->string('iban', 50)->nullable();
            $table->foreignId('supplier_company_id')->nullable()->constrained('supplier_companies')->onDelete('set null');
            $table->foreignId('operational_area_id')->nullable()->constrained('operational_areas')->onDelete('set null');
            $table->foreignId('iqama_image_id')->nullable()->constrained('uploads')->onDelete('set null');
            $table->foreignId('contract_image_id')->nullable()->constrained('uploads')->onDelete('set null');
            $table->foreignId('promissory_note_image_id')->nullable()->constrained('uploads')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_man', function (Blueprint $table) {
            $table->dropForeign(['direct_manager_id']);
            $table->dropForeign(['supplier_company_id']);
            $table->dropForeign(['operational_area_id']);
            $table->dropForeign(['iqama_image_id']);
            $table->dropForeign(['contract_image_id']);
            $table->dropForeign(['promissory_note_image_id']);
            $table->dropColumn([
                'driver_type', 'employee_number', 'joining_date', 'contract_end_date',
                'direct_manager_id', 'license_number', 'license_expiry', 'iqama_expiry',
                'bank_account_no', 'iban', 'supplier_company_id', 'operational_area_id',
                'iqama_image_id', 'contract_image_id', 'promissory_note_image_id',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['id_image_id']);
            $table->dropColumn([
                'name_en', 'alt_mobile', 'gender', 'dob', 'nationality',
                'id_type', 'id_number', 'id_expiry', 'id_image_id',
                'district', 'short_national_address',
            ]);
        });

        Schema::dropIfExists('operational_areas');
        Schema::dropIfExists('supplier_companies');
    }
};
