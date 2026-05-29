<?php

namespace Tests\Feature\External;

use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\Backend\WooCommerceOrderLink;

class WooCommerceParcelControllerTest extends ExternalTestCase
{
    private const API_KEY = '123456rx-ecourier123456';

    /** @test */
    public function rejects_request_without_apikey(): void
    {
        $this->postJson('/api/v10/external/woocommerce/parcel', $this->validBody())
            ->assertStatus(400)
            ->assertJson(['success' => false, 'message' => 'Invalid Api Key']);
    }

    /** @test */
    public function rejects_request_with_wrong_apikey(): void
    {
        $this->postJson('/api/v10/external/woocommerce/parcel', $this->validBody(), [
            'apiKey' => 'wrong',
        ])->assertStatus(400);
    }

    /** @test */
    public function rejects_request_missing_required_fields(): void
    {
        $this->postJson('/api/v10/external/woocommerce/parcel', [
            'site_url' => 'https://shop.test',
            // missing everything else
        ], [
            'apiKey' => self::API_KEY,
        ])->assertStatus(422);
    }

    /** @test */
    public function returns_404_when_merchant_unknown(): void
    {
        $this->postJson('/api/v10/external/woocommerce/parcel', $this->validBody(['merchant_id' => 999_999]), [
            'apiKey' => self::API_KEY,
        ])->assertStatus(404)
          ->assertJson(['message' => 'Unknown Rushly merchant']);
    }

    /** @test */
    public function creates_parcel_and_link_with_company_id_and_category_id(): void
    {
        $merchant = tap(new Merchant(['business_name' => 'Test']), fn($m) => $m->forceFill(['company_id' => 7])->save());

        $body = $this->validBody([
            'merchant_id' => $merchant->id,
            'meta'        => ['wc_order_number' => '#1042'],
        ]);

        $response = $this->postJson('/api/v10/external/woocommerce/parcel', $body, [
            'apiKey' => self::API_KEY,
        ])->assertStatus(201)
          ->assertJsonStructure(['data' => ['parcel_id', 'tracking_id']]);

        $parcelId = $response->json('data.parcel_id');
        $parcel = Parcel::find($parcelId);

        $this->assertNotNull($parcel);
        $this->assertSame(7, $parcel->company_id);            // ← was missing pre-fix
        $this->assertSame(2, $parcel->category_id);           // ← was being silently dropped as deliverycategory_id
        $this->assertSame($merchant->id, $parcel->merchant_id);
        $this->assertSame('Acme', $parcel->customer_name);
        $this->assertNotEmpty($parcel->tracking_id);
        $this->assertSame('#1042', $parcel->reference_number);
        $this->assertSame('WooCommerce order #1042', $parcel->note);

        $link = WooCommerceOrderLink::where('parcel_id', $parcelId)->first();
        $this->assertNotNull($link);
        $this->assertSame(7, $link->company_id);
        $this->assertSame('https://shop.test', $link->site_url);  // trailing slash trimmed
        $this->assertSame(1042, (int) $link->wc_order_id);
        $this->assertSame(['wc_order_number' => '#1042'], $link->meta);
    }

    /** @test */
    public function is_idempotent_for_repeat_calls(): void
    {
        $merchant = tap(new Merchant(), fn($m) => $m->forceFill(['company_id' => 7])->save());

        $body = $this->validBody(['merchant_id' => $merchant->id]);

        $first = $this->postJson('/api/v10/external/woocommerce/parcel', $body, [
            'apiKey' => self::API_KEY,
        ])->assertStatus(201);

        $firstParcelId = $first->json('data.parcel_id');

        $second = $this->postJson('/api/v10/external/woocommerce/parcel', $body, [
            'apiKey' => self::API_KEY,
        ])->assertStatus(200)
          ->assertJson(['message' => 'Parcel already created']);

        $this->assertSame($firstParcelId, $second->json('data.parcel_id'));
        $this->assertSame(1, Parcel::count());
        $this->assertSame(1, WooCommerceOrderLink::count());
    }

    /** @test */
    public function trims_trailing_slash_on_site_url(): void
    {
        $merchant = tap(new Merchant(), fn($m) => $m->forceFill(['company_id' => 7])->save());

        $this->postJson('/api/v10/external/woocommerce/parcel', $this->validBody([
            'merchant_id' => $merchant->id,
            'site_url'    => 'https://shop.test/',
        ]), ['apiKey' => self::API_KEY])->assertStatus(201);

        $this->assertSame('https://shop.test', WooCommerceOrderLink::first()->site_url);
    }

    private function validBody(array $overrides = []): array
    {
        return array_merge([
            'site_url'         => 'https://shop.test',
            'wc_order_id'      => 1042,
            'site_token'       => 'wp-bearer',
            'merchant_id'      => 1,
            'shop_id'          => 3,
            'city_id'          => 1,
            'category_id'      => 2,
            'delivery_type_id' => 3,
            'customer_name'    => 'Acme',
            'customer_address' => '1 Test St',
            'customer_phone'   => '+1-555-0100',
            'cash_collection'  => 25.50,
        ], $overrides);
    }
}
