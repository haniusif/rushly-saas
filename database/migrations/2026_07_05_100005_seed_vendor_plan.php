<?php

use App\Enums\Status;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotent seed of the "Vendor" plan (drivers + TMS + reports, 5 users,
 * 100 drivers, 5000 parcels, 30 days). Safe to re-run — keyed on name.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('plans')) {
            return;
        }
        if (DB::table('plans')->where('name', 'Vendor')->exists()) {
            return;
        }

        $position = (int) DB::table('plans')->max('position') + 1;

        DB::table('plans')->insert([
            'name'              => 'Vendor',
            'parcel_count'      => 5000,
            'deliveryman_count' => 100,
            'user_count'        => 5,
            'days_count'        => 30,
            'price'             => 0,
            'description'       => 'Vendor plan — drivers, TMS, reports. 5 user seats.',
            'position'          => $position,
            'modules'           => json_encode(['dashboard', 'delivery_man', 'tms', 'reports']),
            'status'            => Status::ACTIVE,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
    }

    public function down(): void
    {
        if (Schema::hasTable('plans')) {
            DB::table('plans')->where('name', 'Vendor')->delete();
        }
    }
};
