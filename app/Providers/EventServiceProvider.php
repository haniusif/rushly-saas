<?php

namespace App\Providers;

use App\Commerce\Listeners\PushStockToConnectedChannelsListener;
use App\Models\Backend\Parcel;
use App\Models\Backend\Wms\WmsStock;
use App\Observers\ParcelInstrumentationObserver;
use App\Observers\ParcelSallaObserver;
use App\Observers\ParcelWooCommerceObserver;
use App\Observers\ParcelZidObserver;
use App\Fulfillment\Listeners\RouteToFulfillmentListener;
use App\Oms\Events\OrderReceived;
use App\Oms\Listeners\LogOrderReceivedListener;
use App\Shipping\Events\ShipmentDelivered;
use App\Shipping\Events\ShipmentStatusChanged;
use App\Shipping\Listeners\SendShipmentNotifications;
use App\Shipping\Listeners\StoreTrackingHistory;
use App\Shipping\Listeners\UpdateParcelStatus;
use App\Wms\Events\StockChanged;
use App\Wms\Observers\WmsStockObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        ShipmentStatusChanged::class => [
            UpdateParcelStatus::class,
            StoreTrackingHistory::class,
        ],
        ShipmentDelivered::class => [
            SendShipmentNotifications::class,
        ],
        // Phase 5 OMS + Phase 6 Fulfillment — log stays for ops
        // visibility; router listener drives the actual fulfillment
        // dispatch. Listener order matters: log first, then route.
        OrderReceived::class => [
            LogOrderReceivedListener::class,
            RouteToFulfillmentListener::class,
        ],
        // Phase 7 Inventory sync — WMS stock changes fan out to every
        // active CommerceConnection whose provider implements
        // SupportsInventorySync. Listener is sync (dispatches jobs);
        // the per-connection provider HTTP calls run in the job.
        StockChanged::class => [
            PushStockToConnectedChannelsListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        Parcel::observe(ParcelSallaObserver::class);
        Parcel::observe(ParcelZidObserver::class);
        Parcel::observe(ParcelWooCommerceObserver::class);

        // Performance Dashboard Phase 4 instrumentation:
        // auto-stamp expected_delivery_at + distance_m on parcel create.
        Parcel::observe(ParcelInstrumentationObserver::class);

        // Phase 7 — fire StockChanged whenever WMS stock rows change.
        WmsStock::observe(WmsStockObserver::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents()
    {
        return false;
    }
}
