<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A record of every bulk shipment import, keyed by the CONTENT of the file.
 *
 * On 2026-09-07 the same 547-row sheet was imported twice, twenty minutes
 * apart, producing 1,094 parcels. Nothing was wrong with the import: nginx
 * timed out at 60s and returned 504 while PHP carried on and finished, so the
 * merchant saw a failure, retried, and got a second full batch.
 *
 * The nginx timeout is fixed, but a slow import can still outlive a browser,
 * a proxy or someone's patience. Recording the file hash makes the confirm
 * step idempotent: the same sheet from the same merchant is refused with a
 * message naming when it ran and how many shipments it created, instead of
 * silently doubling them.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('parcel_import_runs')) {
            return;
        }

        Schema::create('parcel_import_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('merchant_id');
            // sha256 of the uploaded file's bytes.
            $table->string('file_hash', 64);
            $table->string('file_name')->nullable();
            $table->unsignedInteger('row_count')->default(0);
            $table->unsignedInteger('imported_count')->default(0);
            // running -> completed | failed. A row left at 'running' is an
            // import that died mid-flight; it still blocks a blind retry.
            $table->string('status', 20)->default('running');
            $table->text('error')->nullable();
            $table->timestamps();

            // One import per file per merchant. The uniqueness is what makes
            // the retry safe - not the application check, which can race.
            $table->unique(['merchant_id', 'file_hash']);
            $table->index(['company_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parcel_import_runs');
    }
};
