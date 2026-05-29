<?php

namespace Tests\Feature\External;

use App\Enums\ParcelStatus;
use App\Models\Backend\Parcel;
use App\Models\Backend\WooCommerceOrderLink;
use App\Services\WooCommerceService;
use Illuminate\Support\Facades\Http;

class WooCommerceServiceTest extends ExternalTestCase
{
    /** @test */
    public function pushes_status_to_per_site_endpoint_with_link_token(): void
    {
        Http::fake([
            '*shop.test/*' => Http::response(['ok' => true], 200),
        ]);

        $parcel = $this->makeParcel(ParcelStatus::DELIVERY_MAN_ASSIGN);
        WooCommerceOrderLink::create([
            'parcel_id'   => $parcel->id,
            'site_url'    => 'https://shop.test',
            'site_token'  => 'per-site-bearer',
            'wc_order_id' => 1042,
            'merchant_id' => 1,
        ]);

        (new WooCommerceService('', ''))->pushParcelStatus($parcel);

        Http::assertSent(function ($request) {
            $body = $request->data();
            return $request->url() === 'https://shop.test/wp-json/rushly/v1/parcel-status'
                && $request->method() === 'POST'
                && $request->hasHeader('Authorization', 'Bearer per-site-bearer')
                && $body['wc_order_id']   === 1042
                && (int) $body['rushly_status'] === ParcelStatus::DELIVERY_MAN_ASSIGN
                && $body['wc_status']     === 'rushly-out-for-delivery';
        });

        $this->assertSame('rushly-out-for-delivery', WooCommerceOrderLink::where('parcel_id', $parcel->id)->first()->last_pushed_status);
    }

    /** @test */
    public function falls_back_to_fallback_url_and_token_when_link_lacks_them(): void
    {
        Http::fake([
            '*fallback.test/*' => Http::response(['ok' => true], 200),
        ]);

        $parcel = $this->makeParcel(ParcelStatus::DELIVERED);
        WooCommerceOrderLink::create([
            'parcel_id'   => $parcel->id,
            'site_url'    => '',
            'site_token'  => null,
            'wc_order_id' => 1042,
            'merchant_id' => 1,
        ]);

        (new WooCommerceService('https://fallback.test', 'fallback-bearer'))->pushParcelStatus($parcel);

        Http::assertSent(fn ($r) => $r->url() === 'https://fallback.test/wp-json/rushly/v1/parcel-status'
            && $r->hasHeader('Authorization', 'Bearer fallback-bearer'));
    }

    /** @test */
    public function skips_when_no_link_exists(): void
    {
        Http::fake();
        $parcel = $this->makeParcel(ParcelStatus::DELIVERED);

        (new WooCommerceService('https://fallback.test', 'token'))->pushParcelStatus($parcel);

        Http::assertNothingSent();
    }

    /** @test */
    public function skips_when_status_maps_to_null(): void
    {
        Http::fake();
        $parcel = $this->makeParcel(ParcelStatus::PENDING);  // ParcelStatus::PENDING → null
        WooCommerceOrderLink::create([
            'parcel_id'  => $parcel->id,
            'site_url'   => 'https://shop.test',
            'site_token' => 't',
            'wc_order_id'=> 1,
            'merchant_id'=> 1,
        ]);

        (new WooCommerceService('', ''))->pushParcelStatus($parcel);

        Http::assertNothingSent();
    }

    /** @test */
    public function skips_when_last_pushed_status_equals_new(): void
    {
        Http::fake();
        $parcel = $this->makeParcel(ParcelStatus::DELIVERED);
        WooCommerceOrderLink::create([
            'parcel_id'          => $parcel->id,
            'site_url'           => 'https://shop.test',
            'site_token'         => 't',
            'wc_order_id'        => 1,
            'merchant_id'        => 1,
            'last_pushed_status' => 'completed',  // already pushed
        ]);

        (new WooCommerceService('', ''))->pushParcelStatus($parcel);

        Http::assertNothingSent();
    }

    /** @test */
    public function does_not_update_last_pushed_status_on_failure(): void
    {
        Http::fake([
            '*shop.test/*' => Http::response('boom', 500),
        ]);

        $parcel = $this->makeParcel(ParcelStatus::DELIVERED);
        $link = WooCommerceOrderLink::create([
            'parcel_id'  => $parcel->id,
            'site_url'   => 'https://shop.test',
            'site_token' => 't',
            'wc_order_id'=> 1,
            'merchant_id'=> 1,
        ]);

        (new WooCommerceService('', ''))->pushParcelStatus($parcel);

        $this->assertNull($link->fresh()->last_pushed_status);
    }

    private function makeParcel(int $status): Parcel
    {
        return Parcel::create([
            'tracking_id'   => 'RL-T-'.$status.'-'.uniqid(),
            'merchant_id'   => 1,
            'customer_name' => 'x',
            'status'        => $status,
        ]);
    }
}
