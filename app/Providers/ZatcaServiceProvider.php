<?php

namespace App\Providers;

use App\Models\Backend\Merchantpanel\Invoice;
use App\Observers\Zatca\InvoiceObserver;
use App\Repositories\Zatca\ZatcaInvoiceRepository;
use App\Repositories\Zatca\ZatcaInvoiceRepositoryInterface;
use App\Repositories\Zatca\ZatcaSettingRepository;
use App\Repositories\Zatca\ZatcaSettingRepositoryInterface;
use App\Services\Zatca\Contracts\ZatcaGateway;
use App\Services\Zatca\Gateways\NullGateway;
use Illuminate\Support\ServiceProvider;

class ZatcaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ZatcaGateway::class, NullGateway::class);
        $this->app->bind(ZatcaSettingRepositoryInterface::class, ZatcaSettingRepository::class);
        $this->app->bind(ZatcaInvoiceRepositoryInterface::class, ZatcaInvoiceRepository::class);
    }

    public function boot(): void
    {
        Invoice::observe(InvoiceObserver::class);
    }
}
