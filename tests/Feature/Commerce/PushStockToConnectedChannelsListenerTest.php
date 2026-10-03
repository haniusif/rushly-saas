<?php

namespace Tests\Feature\Commerce;

use App\Commerce\Jobs\PushStockJob;
use App\Commerce\Listeners\PushStockToConnectedChannelsListener;
use App\Commerce\Models\CommerceConnection;
use App\Commerce\Models\CommerceProvider;
use App\Wms\Events\StockChanged;
use Illuminate\Support\Facades\Bus;
use Tests\Feature\Oms\OmsTestCase;

class PushStockToConnectedChannelsListenerTest extends OmsTestCase
{
    /** @test */
    public function dispatches_one_job_per_active_connection_supporting_inventory_sync(): void
    {
        Bus::fake();

        $salla = CommerceProvider::create([
            'code' => 'salla', 'name' => 'Salla', 'status' => 'active',
            'supports' => ['inventory_sync', 'webhooks'],
        ]);
        $noInv = CommerceProvider::create([
            'code' => 'noinv', 'name' => 'NoInv', 'status' => 'active',
            'supports' => ['webhooks'],
        ]);

        // Active salla connection — should get a job
        $active = CommerceConnection::create([
            'company_id' => 1, 'provider_id' => $salla->id,
            'connection_name' => 'active salla', 'status' => 'active',
        ]);
        // Inactive salla — should NOT get a job
        CommerceConnection::create([
            'company_id' => 1, 'provider_id' => $salla->id,
            'connection_name' => 'paused', 'status' => 'paused',
        ]);
        // Provider without inventory_sync capability — should NOT get a job
        CommerceConnection::create([
            'company_id' => 1, 'provider_id' => $noInv->id,
            'connection_name' => 'noinv-1', 'status' => 'active',
        ]);
        // Different tenant — should NOT get a job
        CommerceConnection::create([
            'company_id' => 999, 'provider_id' => $salla->id,
            'connection_name' => 'other-tenant', 'status' => 'active',
        ]);

        (new PushStockToConnectedChannelsListener())->handle(new StockChanged(
            companyId: 1,
            productId: 42,
            sku: 'SKU-A',
            merchantId: null,
            previousQuantity: 5,
            newQuantity: 12,
            reason: 'adjustment',
        ));

        Bus::assertDispatchedTimes(PushStockJob::class, 1);
        Bus::assertDispatched(PushStockJob::class, function (PushStockJob $j) use ($active) {
            return $j->connectionId === $active->id
                && $j->updates[0]['sku']      === 'SKU-A'
                && $j->updates[0]['quantity'] === 12;
        });
    }

    /** @test */
    public function skips_when_event_has_no_sku(): void
    {
        Bus::fake();

        CommerceProvider::create(['code' => 'salla', 'name' => 'Salla', 'status' => 'active', 'supports' => ['inventory_sync']]);
        // Even with a matching connection, missing SKU short-circuits.
        (new PushStockToConnectedChannelsListener())->handle(new StockChanged(
            companyId: 1, productId: 42, sku: null, merchantId: null,
            previousQuantity: 0, newQuantity: 5,
        ));

        Bus::assertNotDispatched(PushStockJob::class);
    }

    /** @test */
    public function merchant_scope_narrows_fan_out(): void
    {
        Bus::fake();

        $salla = CommerceProvider::create([
            'code' => 'salla', 'name' => 'Salla', 'status' => 'active',
            'supports' => ['inventory_sync'],
        ]);
        $wired = CommerceConnection::create([
            'company_id' => 1, 'provider_id' => $salla->id,
            'connection_name' => 'wired-to-7', 'status' => 'active', 'merchant_id' => 7,
        ]);
        // Different merchant on same tenant — should NOT receive
        CommerceConnection::create([
            'company_id' => 1, 'provider_id' => $salla->id,
            'connection_name' => 'wired-to-99', 'status' => 'active', 'merchant_id' => 99,
        ]);
        // Unbound connection (no merchant_id) — SHOULD receive (fan out to all merchants)
        $unbound = CommerceConnection::create([
            'company_id' => 1, 'provider_id' => $salla->id,
            'connection_name' => 'unbound', 'status' => 'active',
        ]);

        (new PushStockToConnectedChannelsListener())->handle(new StockChanged(
            companyId: 1, productId: 42, sku: 'SKU-B', merchantId: 7,
            previousQuantity: 0, newQuantity: 3,
        ));

        Bus::assertDispatchedTimes(PushStockJob::class, 2);
        $dispatched = collect();
        Bus::assertDispatched(PushStockJob::class, function (PushStockJob $j) use ($dispatched) {
            $dispatched->push($j->connectionId);
            return true;
        });
        $this->assertTrue($dispatched->contains($wired->id));
        $this->assertTrue($dispatched->contains($unbound->id));
    }
}
