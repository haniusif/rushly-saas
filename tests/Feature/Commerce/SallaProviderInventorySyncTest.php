<?php

namespace Tests\Feature\Commerce;

use App\Commerce\DTOs\CommerceConnectionDTO;
use App\Commerce\Providers\Salla\SallaProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SallaProviderInventorySyncTest extends TestCase
{
    private function connection(): CommerceConnectionDTO
    {
        return new CommerceConnectionDTO(
            id:              1,
            companyId:       1,
            providerCode:    'salla',
            connectionName:  'test',
            remoteStoreId:   '99999999',
            domain:          null,
            merchantId:      42,
            accessToken:     'test-token',
            refreshToken:    null,
            tokenExpiresAt:  null,
            apiKey:          null,
            apiSecret:       null,
            webhookSecret:   null,
        );
    }

    /** @test */
    public function pushes_absolute_quantity_after_resolving_sku_to_product_id(): void
    {
        Http::fake([
            // GET /products?sku=SKU-A → resolve to product id 12345
            'api.salla.dev/admin/v2/products*' => Http::sequence()
                ->push(['data' => [['id' => 12345, 'sku' => 'SKU-A', 'name' => 'Widget']]], 200)
                ->push(['ok' => true], 200),   // PUT /products/12345/quantity
        ]);

        $provider = $this->app->make(SallaProvider::class);
        $provider->pushInventoryUpdate($this->connection(), [
            ['sku' => 'SKU-A', 'quantity' => 42],
        ]);

        // Two calls total: one GET (resolve) + one PUT (update).
        Http::assertSentCount(2);

        // Assert the PUT hit the resolved product id with the exact absolute quantity.
        Http::assertSent(function ($request) {
            return $request->method() === 'PUT'
                && str_contains($request->url(), '/products/12345/quantity')
                && $request->data() === ['quantity' => 42];
        });
    }

    /** @test */
    public function skips_sku_when_not_found_at_salla(): void
    {
        Http::fake([
            // Salla returns 200 with empty data — SKU doesn't exist on this storefront
            'api.salla.dev/admin/v2/products*' => Http::response(['data' => []], 200),
        ]);

        $provider = $this->app->make(SallaProvider::class);
        $provider->pushInventoryUpdate($this->connection(), [
            ['sku' => 'MISSING-SKU', 'quantity' => 10],
        ]);

        // Only the GET was made — no PUT because we couldn't resolve.
        Http::assertSentCount(1);
        Http::assertNotSent(function ($request) {
            return $request->method() === 'PUT';
        });
    }

    /** @test */
    public function continues_batch_after_one_sku_fails(): void
    {
        Http::fake([
            'api.salla.dev/admin/v2/products*' => Http::sequence()
                // First SKU: 500 error
                ->push(['error' => 'boom'], 500)
                // Second SKU: found → resolve to 999
                ->push(['data' => [['id' => 999, 'sku' => 'SKU-B']]], 200)
                // PUT for second SKU: success
                ->push(['ok' => true], 200),
        ]);

        $provider = $this->app->make(SallaProvider::class);
        $provider->pushInventoryUpdate($this->connection(), [
            ['sku' => 'SKU-A', 'quantity' => 5],   // will 500 on GET → logged + skipped
            ['sku' => 'SKU-B', 'quantity' => 8],   // succeeds
        ]);

        // 3 calls: GET for A (500), GET for B (200), PUT for B (200)
        Http::assertSentCount(3);
        Http::assertSent(function ($request) {
            return $request->method() === 'PUT'
                && str_contains($request->url(), '/products/999/quantity');
        });
    }
}
