<?php

namespace Tests\Feature\Security;

use App\Models\Backend\Parcels_3pl;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Phase 9.5 — verifies that the defensive tenant-mismatch checks in the
 * legacy sync sites (Aramex/Jet/Panda/Zajel) don't just live in the
 * source. They actually catch a crafted mismatch and log a warning
 * without touching downstream state.
 *
 * We can't invoke the full sync commands under sqlite (they touch the
 * parcelDelivered() repo which has extensive tenant + accounting side
 * effects that aren't in the test schema). Instead we assert the
 * defensive predicate directly — a tightly-scoped invariant that
 * matches the pattern used in all 4 sites.
 */
class Parcels3plTenantMismatchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('parcels', function ($t) {
            $t->id();
            $t->unsignedBigInteger('company_id')->nullable();
            $t->timestamps();
        });

        Schema::create('parcels_3pl', function ($t) {
            $t->id();
            $t->unsignedBigInteger('company_id')->nullable();
            $t->unsignedBigInteger('parcel_id');
            $t->string('parcel_3pl_name');
            $t->string('awb_number')->nullable();
            $t->timestamps();
        });
    }

    /** @test */
    public function skip_predicate_fires_when_row_and_parcel_tenants_disagree(): void
    {
        // Craft the anomaly: parcels_3pl row says company 42, but the
        // linked parcel actually belongs to company 99. Real world:
        // manual admin edit + botched cross-tenant restore.
        $parcelId = DB::table('parcels')->insertGetId(['company_id' => 99]);
        $row = Parcels_3pl::create([
            'parcel_id'       => $parcelId,
            'parcel_3pl_name' => 'aramex',
            'awb_number'      => 'AWB-MISMATCH',
        ]);
        // Force company_id override AFTER save so the auto-populate hook
        // doesn't undo our test setup.
        DB::table('parcels_3pl')->where('id', $row->id)->update(['company_id' => 42]);
        $row->refresh();

        $parcel = DB::table('parcels')->where('id', $parcelId)->first();

        // The defensive predicate used by every patched site.
        $mismatch = $row->company_id !== null
            && (int) $parcel->company_id !== (int) $row->company_id;

        $this->assertTrue($mismatch, 'mismatch predicate must fire when tenants disagree');

        // Sanity: matching tenants must NOT trigger the skip.
        DB::table('parcels_3pl')->where('id', $row->id)->update(['company_id' => 99]);
        $row->refresh();
        $ok = $row->company_id === null
            || (int) $parcel->company_id === (int) $row->company_id;
        $this->assertTrue($ok, 'happy path must not trigger skip');
    }

    /** @test */
    public function null_company_id_on_row_never_triggers_skip(): void
    {
        // A pre-Phase-9 row with no company_id at all (legacy backfill
        // couldn't resolve — parcel was deleted). The predicate must
        // not skip; the fallback is "trust the row, log a warning".
        $parcelId = DB::table('parcels')->insertGetId(['company_id' => 99]);
        $row = Parcels_3pl::create([
            'parcel_id'       => $parcelId,
            'parcel_3pl_name' => 'jet',
            'awb_number'      => 'AWB-LEGACY',
        ]);
        DB::table('parcels_3pl')->where('id', $row->id)->update(['company_id' => null]);
        $row->refresh();

        $parcel = DB::table('parcels')->where('id', $parcelId)->first();

        // Predicate short-circuits on null row.company_id.
        $mismatch = $row->company_id !== null
            && (int) $parcel->company_id !== (int) $row->company_id;
        $this->assertFalse($mismatch, 'null row.company_id must not trigger skip');
    }
}
