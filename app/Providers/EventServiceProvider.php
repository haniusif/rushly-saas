<?php

namespace App\Providers;

use App\Models\Backend\Parcel;
use App\Observers\ParcelSallaObserver;
use App\Observers\ParcelWooCommerceObserver;
use App\Observers\ParcelZidObserver;
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
