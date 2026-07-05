<?php

namespace Tests\Feature\Companies;

use App\Http\Requests\Plan\StoreRequest as PlanStoreRequest;
use App\Repositories\Superadmin\Plan\PlanInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Covers the Phase-2 slice: the Vendor-plan data migration and
 * PlanRepository::store/update round-tripping the new user_count column.
 */
class VendorPlanTest extends CompaniesTestCase
{
    /** @test */
    public function vendor_plan_seed_migration_creates_the_expected_row_and_is_idempotent(): void
    {
        // The Vendor plan migration is loaded via require(), then its up()
        // is executed twice to verify idempotency (name-keyed short-circuit).
        $migration = require __DIR__ . '/../../../database/migrations/2026_07_05_100005_seed_vendor_plan.php';

        $migration->up();
        $migration->up(); // second run must be a no-op

        $rows = DB::table('plans')->where('name', 'Vendor')->get();
        $this->assertCount(1, $rows, 'Vendor plan should exist exactly once after two up() calls');

        $vendor = $rows->first();
        $this->assertSame(5000, (int) $vendor->parcel_count);
        $this->assertSame(100,  (int) $vendor->deliveryman_count);
        $this->assertSame(5,    (int) $vendor->user_count);
        $this->assertSame(30,   (int) $vendor->days_count);
        $this->assertSame(1,    (int) $vendor->status);

        $modules = json_decode($vendor->modules, true);
        $this->assertIsArray($modules);
        $this->assertEqualsCanonicalizing(
            ['dashboard', 'delivery_man', 'tms', 'reports'],
            $modules,
        );
    }

    /** @test */
    public function plan_repository_persists_user_count_on_create_and_update(): void
    {
        /** @var \App\Repositories\Superadmin\Plan\PlanRepository $repo */
        $repo = app(PlanInterface::class);

        $ok = $repo->store(new \Illuminate\Http\Request([
            'name'              => 'Trial Test',
            'parcel_count'      => 100,
            'deliveryman_count' => 5,
            'user_count'        => 3,
            'days_count'        => 7,
            'price'             => 10,
            'description'       => null,
            'position'          => 10,
            'modules'           => ['dashboard'],
            'status'            => 1,
        ]));
        $this->assertTrue($ok);

        $row = DB::table('plans')->where('name', 'Trial Test')->first();
        $this->assertNotNull($row);
        $this->assertSame(3, (int) $row->user_count);

        $repo->update($row->id, new \Illuminate\Http\Request([
            'name'              => 'Trial Test',
            'parcel_count'      => 100,
            'deliveryman_count' => 5,
            'user_count'        => 8,
            'days_count'        => 7,
            'price'             => 10,
            'description'       => null,
            'position'          => 10,
            'modules'           => ['dashboard'],
            'status'            => 1,
        ]));

        $updated = DB::table('plans')->where('id', $row->id)->first();
        $this->assertSame(8, (int) $updated->user_count);
    }

    /** @test */
    public function plan_store_request_accepts_null_user_count(): void
    {
        // Backwards-compat: existing plans have no user_count. The rule
        // must be nullable so pre-Phase-2 plan edits keep working.
        $rules = (new PlanStoreRequest())->rules();

        $validator = Validator::make([
            'name'         => 'Legacy',
            'parcel_count' => 100,
            'days_count'   => 30,
            'price'        => 1,
            'position'     => 1,
            'status'       => 1,
            // no user_count key
        ], $rules);

        $this->assertFalse($validator->fails(), print_r($validator->errors()->all(), true));
    }

    /** @test */
    public function plan_store_request_rejects_user_count_below_one(): void
    {
        $rules = (new PlanStoreRequest())->rules();

        $validator = Validator::make([
            'name'         => 'Bad',
            'parcel_count' => 100,
            'days_count'   => 30,
            'price'        => 1,
            'position'     => 1,
            'status'       => 1,
            'user_count'   => 0,
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('user_count', $validator->errors()->toArray());
    }
}
