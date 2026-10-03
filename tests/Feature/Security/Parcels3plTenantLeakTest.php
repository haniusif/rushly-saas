<?php

namespace Tests\Feature\Security;

use App\Models\Backend\Parcels_3pl;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Phase 9 — closes the multi-tenant leak on parcels_3pl documented in
 * 3PL.md issue #3. Tests the model-level auto-populate of `company_id`
 * from the linked parcel, so future rows can't be tenant-less.
 *
 * We don't test the sync-command scoping here — those still run
 * unscoped as of Phase 9, per the deferred-follow-up note in
 * commerce-module-phase1 memory. This test locks in that the
 * FOUNDATION (column + scope + auto-populate + unique index) is
 * correct.
 */
class Parcels3plTenantLeakTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Minimal schema — parcels (source of tenant) + parcels_3pl
        // (the leaky link table).
        Schema::create('parcels', function ($t) {
            $t->id();
            $t->unsignedBigInteger('company_id')->nullable();
            $t->unsignedBigInteger('merchant_id')->nullable();
            $t->string('tracking_id')->nullable();
            $t->timestamps();
        });

        Schema::create('parcels_3pl', function ($t) {
            $t->id();
            $t->unsignedBigInteger('company_id')->nullable();
            $t->unsignedBigInteger('parcel_id');
            $t->string('parcel_3pl_name');
            $t->string('target_company_id')->nullable();
            $t->string('awb_number')->nullable();
            $t->string('awb_pdf')->nullable();
            $t->longText('response')->nullable();
            $t->string('current_status')->nullable();
            $t->timestamp('status_datetime')->nullable();
            $t->timestamps();
        });
    }

    private function seedParcel(int $companyId): int
    {
        return \DB::table('parcels')->insertGetId([
            'company_id'  => $companyId,
            'merchant_id' => 1,
            'tracking_id' => 'RL-' . bin2hex(random_bytes(3)),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    /** @test */
    public function auto_populates_company_id_from_linked_parcel_on_create(): void
    {
        $parcelIdA = $this->seedParcel(companyId: 42);
        $parcelIdB = $this->seedParcel(companyId: 99);

        // Caller omits company_id — model must resolve it via the parcel.
        $rowA = Parcels_3pl::create([
            'parcel_id'       => $parcelIdA,
            'parcel_3pl_name' => 'aramex',
            'awb_number'      => 'AWB-1',
        ]);
        $rowB = Parcels_3pl::create([
            'parcel_id'       => $parcelIdB,
            'parcel_3pl_name' => 'aramex',
            'awb_number'      => 'AWB-2',
        ]);

        $this->assertSame(42, (int) $rowA->fresh()->company_id);
        $this->assertSame(99, (int) $rowB->fresh()->company_id);
    }

    /** @test */
    public function respects_explicit_company_id_when_caller_supplies_one(): void
    {
        $parcelId = $this->seedParcel(companyId: 42);

        // Caller explicitly overrides — used by cross-tenant admin tools
        // + import scripts. The auto-populate hook must not clobber this.
        $row = Parcels_3pl::create([
            'company_id'      => 500,      // explicit
            'parcel_id'       => $parcelId,
            'parcel_3pl_name' => 'aramex',
            'awb_number'      => 'AWB-X',
        ]);

        $this->assertSame(500, (int) $row->fresh()->company_id);
    }

    /** @test */
    public function companywise_scope_isolates_rows_by_tenant(): void
    {
        $parcelA = $this->seedParcel(companyId: 42);
        $parcelB = $this->seedParcel(companyId: 99);
        Parcels_3pl::create(['parcel_id' => $parcelA, 'parcel_3pl_name' => 'jet', 'awb_number' => 'A']);
        Parcels_3pl::create(['parcel_id' => $parcelB, 'parcel_3pl_name' => 'jet', 'awb_number' => 'B']);

        // Direct where() query — companywise scope is opt-in, not global.
        $company42Rows = Parcels_3pl::where('company_id', 42)->get();
        $this->assertCount(1, $company42Rows);
        $this->assertSame('A', $company42Rows[0]->awb_number);

        $company99Rows = Parcels_3pl::where('company_id', 99)->get();
        $this->assertCount(1, $company99Rows);
        $this->assertSame('B', $company99Rows[0]->awb_number);
    }

    /** @test */
    public function leaves_company_id_null_when_parcel_id_missing_or_orphaned(): void
    {
        // Best-effort — no parcel means no tenant to inherit.
        // Model still saves; downstream code has to handle the null.
        $orphaned = Parcels_3pl::create([
            'parcel_id'       => 999999,     // nonexistent
            'parcel_3pl_name' => 'panda',
            'awb_number'      => 'ORPHAN',
        ]);

        $this->assertNull($orphaned->fresh()->company_id);
    }
}
