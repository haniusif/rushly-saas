<?php

namespace Tests\Feature\External;

use App\Enums\ParcelStatus;
use App\Models\Backend\Parcel;
use App\Repositories\Parcel\ParcelInterface;
use App\Services\SallaService;
use App\Services\WooCommerceService;
use App\Services\ZidService;
use Illuminate\Support\Facades\Http;

class CancelShipmentTest extends ExternalTestCase
{
    /** @test */
    public function cancel_marks_status_and_locks_further_updates(): void
    {
        $parcel = Parcel::create([
            'tracking_id'   => 'RL-CANCEL-1',
            'merchant_id'   => 1,
            'customer_name' => 'x',
            'status'        => ParcelStatus::PENDING,
        ]);

        $this->assertFalse($parcel->isCancelled());
        $this->assertTrue($parcel->isCancellable());
        $this->assertTrue($parcel->cancelShipment('Customer changed mind'));
        $this->assertTrue($parcel->fresh()->isCancelled());
        $this->assertSame(ParcelStatus::CANCELLED, (int) $parcel->fresh()->status);
    }

    /** @test */
    public function cancel_is_rejected_once_pickup_has_been_assigned(): void
    {
        $parcel = Parcel::create([
            'tracking_id'   => 'RL-CANCEL-LATE',
            'merchant_id'   => 1,
            'customer_name' => 'x',
            'status'        => ParcelStatus::PICKUP_ASSIGN,
        ]);

        $this->assertFalse($parcel->isCancellable());
        $this->assertFalse($parcel->cancelShipment('too late'));
        $this->assertSame(ParcelStatus::PICKUP_ASSIGN, (int) $parcel->fresh()->status);
    }

    /** @test */
    public function cancel_is_rejected_for_delivered_parcels(): void
    {
        $parcel = Parcel::create([
            'tracking_id'   => 'RL-CANCEL-DEL',
            'merchant_id'   => 1,
            'customer_name' => 'x',
            'status'        => ParcelStatus::DELIVERED,
        ]);

        $this->assertFalse($parcel->isCancellable());
        $this->assertFalse($parcel->cancelShipment());
        $this->assertSame(ParcelStatus::DELIVERED, (int) $parcel->fresh()->status);
    }

    /** @test */
    public function further_status_updates_silently_no_op(): void
    {
        $parcel = Parcel::create([
            'tracking_id'   => 'RL-CANCEL-2',
            'merchant_id'   => 1,
            'customer_name' => 'x',
            'status'        => ParcelStatus::CANCELLED,
        ]);

        // Direct model update
        $parcel->status = ParcelStatus::DELIVERED;
        $saved = $parcel->save();

        // The updating event hook returns false → save() returns false → DB unchanged.
        $this->assertFalse($saved);
        $this->assertSame(ParcelStatus::CANCELLED, (int) $parcel->fresh()->status);
    }

    /** @test */
    public function repository_status_update_rejects_cancelled_parcel(): void
    {
        $parcel = Parcel::create([
            'tracking_id'   => 'RL-CANCEL-3',
            'merchant_id'   => 1,
            'customer_name' => 'x',
            'status'        => ParcelStatus::CANCELLED,
        ]);

        $repo = $this->app->make(ParcelInterface::class);
        $this->assertFalse($repo->statusUpdate($parcel->id, ParcelStatus::DELIVERED));
        $this->assertSame(ParcelStatus::CANCELLED, (int) $parcel->fresh()->status);
    }

    /** @test */
    public function cancel_twice_is_a_no_op(): void
    {
        $parcel = Parcel::create([
            'tracking_id'   => 'RL-CANCEL-4',
            'merchant_id'   => 1,
            'customer_name' => 'x',
            'status'        => ParcelStatus::CANCELLED,
        ]);

        $this->assertFalse($parcel->cancelShipment('again'));
    }

    /** @test */
    public function salla_writeback_emits_cancelled_for_cancelled_status(): void
    {
        $service = new SallaService('https://salla.test', 'token');
        $method  = (new \ReflectionClass($service))->getMethod('mapStatus');
        $method->setAccessible(true);

        $this->assertSame('cancelled', $method->invoke($service, ParcelStatus::CANCELLED));
        $this->assertSame('delivered', $method->invoke($service, ParcelStatus::DELIVERED));
        $this->assertSame('in_progress', $method->invoke($service, ParcelStatus::PICKUP_ASSIGN));
        $this->assertNull($method->invoke($service, ParcelStatus::PENDING));
    }

    /** @test */
    public function zid_writeback_emits_cancelled_for_cancelled_status(): void
    {
        $service = new ZidService('https://zid.test', 'token');
        $method  = (new \ReflectionClass($service))->getMethod('mapStatus');
        $method->setAccessible(true);

        $this->assertSame('cancelled', $method->invoke($service, ParcelStatus::CANCELLED));
        $this->assertSame('indelivery', $method->invoke($service, ParcelStatus::DELIVERY_MAN_ASSIGN));
        $this->assertSame('preparing', $method->invoke($service, ParcelStatus::PICKUP_ASSIGN));
    }

    /** @test */
    public function woocommerce_writeback_emits_cancelled_for_cancelled_status(): void
    {
        $service = new WooCommerceService('', '');
        $method  = (new \ReflectionClass($service))->getMethod('mapStatus');
        $method->setAccessible(true);

        $this->assertSame('cancelled', $method->invoke($service, ParcelStatus::CANCELLED));
        $this->assertSame('completed', $method->invoke($service, ParcelStatus::DELIVERED));
        $this->assertSame('rushly-out-for-delivery', $method->invoke($service, ParcelStatus::DELIVERY_MAN_ASSIGN));
    }
}
