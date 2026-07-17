<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fleet-driver mobile app tables.
 *
 * fleet_vehicles: master list of company vehicles, each optionally
 * assigned to a driver (users.id where user_type = deliveryman).
 *
 * fleet_trips: shift/session log. Starts with odometer + inspection
 * snapshot, ends with final odometer + optional end GPS.
 *
 * fleet_fuel_logs: fuel fill-ups, one row per receipt.
 *
 * fleet_maintenance_reports: driver-raised issues + scheduled work.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('fleet_vehicles', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('company_id')->index();
            $t->string('plate_number');
            $t->string('make')->nullable();
            $t->string('model')->nullable();
            $t->unsignedSmallInteger('year')->nullable();
            $t->string('vehicle_type', 32)->default('van'); // van/truck/motorbike/car
            $t->string('status', 32)->default('active');    // active/inactive/in_maintenance
            $t->unsignedBigInteger('current_odometer')->default(0);
            $t->unsignedBigInteger('assigned_driver_id')->nullable()->index();
            $t->unsignedBigInteger('hub_id')->nullable()->index();
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->index(['company_id', 'status']);
            $t->unique(['company_id', 'plate_number']);
        });

        Schema::create('fleet_trips', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('company_id')->index();
            $t->foreignId('vehicle_id')->constrained('fleet_vehicles');
            $t->unsignedBigInteger('driver_id')->index();
            $t->unsignedBigInteger('start_odometer');
            $t->unsignedBigInteger('end_odometer')->nullable();
            $t->timestamp('started_at');
            $t->timestamp('ended_at')->nullable();
            $t->decimal('start_lat', 10, 7)->nullable();
            $t->decimal('start_lng', 10, 7)->nullable();
            $t->decimal('end_lat', 10, 7)->nullable();
            $t->decimal('end_lng', 10, 7)->nullable();
            $t->json('start_inspection')->nullable(); // {tires_ok, brakes_ok, lights_ok, fluids_ok, body_ok, notes}
            $t->text('notes')->nullable();
            $t->string('status', 16)->default('in_progress'); // in_progress/completed
            $t->timestamps();
            $t->index(['company_id', 'driver_id', 'status']);
        });

        Schema::create('fleet_fuel_logs', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('company_id')->index();
            $t->foreignId('vehicle_id')->constrained('fleet_vehicles');
            $t->unsignedBigInteger('driver_id')->index();
            $t->decimal('liters', 8, 2);
            $t->decimal('cost', 10, 2);
            $t->unsignedBigInteger('odometer_reading');
            $t->string('receipt_url')->nullable();
            $t->timestamp('filled_at');
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->index(['company_id', 'vehicle_id', 'filled_at']);
        });

        Schema::create('fleet_maintenance_reports', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('company_id')->index();
            $t->foreignId('vehicle_id')->constrained('fleet_vehicles');
            $t->unsignedBigInteger('driver_id')->index();
            $t->string('issue_type', 32);   // mechanical/electrical/body/tires/other
            $t->string('severity', 16);     // low/medium/high/critical
            $t->text('description');
            $t->string('status', 16)->default('reported'); // reported/in_progress/resolved
            $t->timestamp('reported_at');
            $t->timestamp('resolved_at')->nullable();
            $t->text('resolution_notes')->nullable();
            $t->timestamps();
            $t->index(['company_id', 'vehicle_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_maintenance_reports');
        Schema::dropIfExists('fleet_fuel_logs');
        Schema::dropIfExists('fleet_trips');
        Schema::dropIfExists('fleet_vehicles');
    }
};
