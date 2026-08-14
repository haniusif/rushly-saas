<?php

namespace Tests\Unit\Shipping;

use App\Shipping\Contracts\ShippingProviderInterface;
use App\Shipping\Factory\ShippingProviderFactory;
use App\Shipping\Providers\Logestechs\LogestechsProvider;
use InvalidArgumentException;
use Tests\TestCase;

class ShippingProviderFactoryTest extends TestCase
{
    public function test_it_resolves_a_registered_provider(): void
    {
        $factory = app(ShippingProviderFactory::class);
        $provider = $factory->make('logestechs');

        $this->assertInstanceOf(LogestechsProvider::class, $provider);
        $this->assertInstanceOf(ShippingProviderInterface::class, $provider);
        $this->assertSame('logestechs', $provider->code());
    }

    public function test_it_caches_provider_instances(): void
    {
        $factory = app(ShippingProviderFactory::class);
        $a = $factory->make('logestechs');
        $b = $factory->make('logestechs');
        $this->assertSame($a, $b);
    }

    public function test_it_throws_for_unknown_provider(): void
    {
        $this->expectException(InvalidArgumentException::class);
        app(ShippingProviderFactory::class)->make('nope-not-a-real-provider');
    }

    public function test_codes_returns_the_registry(): void
    {
        $codes = app(ShippingProviderFactory::class)->codes();
        $this->assertContains('logestechs', $codes);
    }
}
