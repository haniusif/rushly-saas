<?php

namespace Tests\Unit\Shipping;

use App\Enums\ParcelStatus;
use App\Shipping\Providers\Logestechs\Mappers\StatusMapper;
use PHPUnit\Framework\TestCase;

class StatusMapperTest extends TestCase
{
    /** @dataProvider statusCases */
    public function test_it_maps_known_statuses(string $raw, ?int $expected): void
    {
        $this->assertSame($expected, StatusMapper::map($raw));
    }

    public static function statusCases(): array
    {
        return [
            'DELIVERED'               => ['DELIVERED', ParcelStatus::DELIVERED],
            'COMPLETED maps DELIVERED'=> ['COMPLETED', ParcelStatus::DELIVERED],
            'PARTIAL_DELIVERED'       => ['PARTIAL_DELIVERED', ParcelStatus::PARTIAL_DELIVERED],
            'CANCELLED'               => ['CANCELLED', ParcelStatus::CANCELLED],
            'CANCELED (US spelling)'  => ['CANCELED', ParcelStatus::CANCELLED],
            'OUT_FOR_DELIVERY'        => ['OUT_FOR_DELIVERY', ParcelStatus::DELIVERY_MAN_ASSIGN],
            'WITH_DRIVER'             => ['WITH_DRIVER', ParcelStatus::DELIVERY_MAN_ASSIGN],
            'PICKED_UP'               => ['PICKED_UP', ParcelStatus::RECEIVED_BY_PICKUP_MAN],
            'COLLECTED'               => ['COLLECTED', ParcelStatus::RECEIVED_BY_PICKUP_MAN],
            'AT_HUB'                  => ['AT_HUB', ParcelStatus::RECEIVED_BY_HUB],
            'AT_BRANCH'               => ['AT_BRANCH', ParcelStatus::RECEIVED_BY_HUB],
            'IN_TRANSIT'              => ['IN_TRANSIT', ParcelStatus::TRANSFER_TO_HUB],
            'EN_ROUTE'                => ['EN_ROUTE', ParcelStatus::TRANSFER_TO_HUB],
            'RETURN_TO_SENDER'        => ['RETURN_TO_SENDER', ParcelStatus::RETURN_RECEIVED_BY_MERCHANT],
            'RETURN_IN_TRANSIT'       => ['RETURN_IN_TRANSIT', ParcelStatus::RETURN_TO_COURIER],
            'FAILED_DELIVERY'         => ['FAILED_DELIVERY', ParcelStatus::DELIVERY_RE_SCHEDULE],
            'PENDING'                 => ['PENDING', ParcelStatus::PENDING],
            'NEW'                     => ['NEW', ParcelStatus::PENDING],
            'UNKNOWN_GIBBERISH'       => ['UNKNOWN_GIBBERISH', null],
        ];
    }

    public function test_it_handles_lowercase_and_whitespace(): void
    {
        $this->assertSame(ParcelStatus::DELIVERED, StatusMapper::map('  delivered  '));
    }
}
