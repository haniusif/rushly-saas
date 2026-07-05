<?php

namespace Tests\Feature\Companies;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Shared base for tests around the Vendor-plan / child-company work.
 *
 * Rationale identical to OmsTestCase / ExternalTestCase: the main app's
 * migrations aren't sqlite-clean, so each suite hand-builds only the
 * tables it touches. Here we cover the writes CompanyRepository::store()
 * and company_create() do, plus enough of settings()'s eager-load path
 * that it doesn't blow up on the first call.
 */
abstract class CompaniesTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['activitylog.enabled' => false]);

        $this->buildSchema();
        $this->seedPlatformSettingsRow();
    }

    private function buildSchema(): void
    {
        Schema::create('general_settings', function ($t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('phone')->nullable();
            $t->string('email')->nullable();
            $t->string('address')->nullable();
            $t->string('currency', 16)->nullable();
            $t->string('copyright')->nullable();
            $t->string('current_version')->nullable();
            $t->string('par_track_prefix')->nullable();
            $t->string('invoice_prefix')->nullable();
            $t->unsignedBigInteger('logo')->nullable();
            $t->unsignedBigInteger('favicon')->nullable();
            $t->unsignedBigInteger('plan_id')->nullable();
            $t->unsignedBigInteger('subscription_id')->nullable();
            $t->unsignedBigInteger('parent_company_id')->nullable()->index();
            $t->unsignedTinyInteger('status')->default(1);
            $t->timestamps();
        });

        Schema::create('uploads', function ($t) {
            $t->id();
            $t->string('original')->nullable();
            $t->timestamps();
        });

        Schema::create('sms_send_settings', function ($t) {
            $t->id();
            $t->unsignedBigInteger('company_id');
            $t->string('sms_send_status')->nullable();
            $t->unsignedTinyInteger('status')->default(0);
            $t->timestamps();
        });

        Schema::create('sms_settings', function ($t) {
            $t->id();
            $t->unsignedBigInteger('company_id');
            $t->string('key');
            $t->text('value')->nullable();
            $t->timestamps();
        });

        Schema::create('config', function ($t) {
            $t->id();
            $t->unsignedBigInteger('company_id');
            $t->string('key');
            $t->text('value')->nullable();
            $t->timestamps();
        });

        Schema::create('notification_settings', function ($t) {
            $t->id();
            $t->unsignedBigInteger('company_id');
            $t->string('fcm_secret_key')->nullable();
            $t->string('fcm_topic')->nullable();
            $t->timestamps();
        });

        Schema::create('plans', function ($t) {
            $t->id();
            $t->string('name')->nullable();
            $t->bigInteger('parcel_count')->default(0);
            $t->bigInteger('deliveryman_count')->default(0);
            $t->unsignedBigInteger('user_count')->nullable();
            $t->bigInteger('days_count')->default(0);
            $t->decimal('price', 22, 2)->default(0);
            $t->text('description')->nullable();
            $t->bigInteger('position')->default(0);
            $t->text('modules')->nullable();
            $t->unsignedTinyInteger('status')->default(1);
            $t->timestamps();
        });

        Schema::create('subscriptions', function ($t) {
            $t->id();
            $t->unsignedBigInteger('company_id');
            $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('plan_id');
            $t->decimal('price', 16, 2)->nullable();
            $t->bigInteger('parcel_count')->nullable();
            $t->bigInteger('deliveryman_count')->nullable();
            $t->unsignedBigInteger('user_count')->nullable();
            $t->bigInteger('days_count')->nullable();
            $t->timestamp('start_date')->nullable();
            $t->timestamp('expired_date')->nullable();
            $t->timestamps();
        });
    }

    private function seedPlatformSettingsRow(): void
    {
        DB::table('general_settings')->insert([
            'id'              => 1,
            'name'            => 'Platform',
            'copyright'       => '© Test',
            'current_version' => '1.0.0-test',
            'currency'        => 'USD',
            'favicon'         => 9,
            'status'          => 1,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
    }
}
