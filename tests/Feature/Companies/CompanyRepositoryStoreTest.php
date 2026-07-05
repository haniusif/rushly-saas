<?php

namespace Tests\Feature\Companies;

use App\Repositories\Superadmin\Company\CompanyInterface;
use App\Repositories\Superadmin\Company\CompanyRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Covers CompanyRepository::company_create — the sole write site for
 * general_settings.parent_company_id. store() calls this with the parent
 * id when invoked from the tenant-context ChildCompanyController.
 */
class CompanyRepositoryStoreTest extends CompaniesTestCase
{
    private function makeRequest(array $overrides = []): Request
    {
        return new Request(array_merge([
            'company_name'     => 'Acme Sub',
            'mobile'           => '555-1000',
            'email'            => 'owner@acme.sub',
            'address'          => '1 Main St',
            'currency'         => 'USD',
            'par_track_prefix' => 'ac',
            'invoice_prefix'   => 'inv',
            'status'           => 1,
        ], $overrides));
    }

    /** @test */
    public function company_create_writes_parent_company_id_when_provided(): void
    {
        /** @var CompanyRepository $repo */
        $repo = app(CompanyInterface::class);
        $parentId = 42;

        $newId = $repo->company_create($this->makeRequest(), null, $parentId);

        $this->assertNotFalse($newId);
        $this->assertDatabaseHas('general_settings', [
            'id'                => $newId,
            'name'              => 'Acme Sub',
            'parent_company_id' => $parentId,
        ]);
    }

    /** @test */
    public function company_create_without_parent_leaves_parent_company_id_null(): void
    {
        /** @var CompanyRepository $repo */
        $repo = app(CompanyInterface::class);

        $newId = $repo->company_create($this->makeRequest([
            'company_name' => 'Direct Signup',
            'email'        => 'direct@example.com',
        ]));

        $this->assertNotFalse($newId);

        $row = DB::table('general_settings')->where('id', $newId)->first();
        $this->assertNotNull($row);
        $this->assertNull($row->parent_company_id);
    }

    /** @test */
    public function company_create_does_not_touch_parent_company_id_on_update(): void
    {
        /** @var CompanyRepository $repo */
        $repo = app(CompanyInterface::class);

        // First create with a parent, then update (id path) with a different
        // parent value — parent_company_id should be immutable on updates so
        // an accidental resave of the child never re-parents it.
        $newId = $repo->company_create($this->makeRequest(), null, 100);
        $this->assertDatabaseHas('general_settings', ['id' => $newId, 'parent_company_id' => 100]);

        $repo->company_create($this->makeRequest([
            'company_name' => 'Acme Sub — renamed',
        ]), $newId, 999); // parent arg should be ignored when $id is set

        $this->assertDatabaseHas('general_settings', [
            'id'                => $newId,
            'name'              => 'Acme Sub — renamed',
            'parent_company_id' => 100,
        ]);
    }

}
