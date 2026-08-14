<?php

namespace Tests\Unit\Shipping;

use App\Shipping\DTOs\AddressDTO;
use App\Shipping\DTOs\ConnectionDTO;
use App\Shipping\DTOs\ShipmentDTO;
use App\Shipping\Providers\Logestechs\Mappers\ShipmentRequestMapper;
use Tests\TestCase;

class ShipmentRequestMapperTest extends TestCase
{
    public function test_it_builds_a_cod_body(): void
    {
        $body = ShipmentRequestMapper::toBody(
            $this->connectionDto(),
            $this->shipmentDto(codAmount: 150.0),
        );

        $this->assertSame('test@example.com', $body['email']);
        $this->assertSame('s3cret', $body['password']);
        $this->assertSame('COD', $body['pkg']['shipmentType']);
        $this->assertSame(150, $body['pkg']['cod']);
        $this->assertSame(150, $body['pkg']['packageItemsToDeliverList'][0]['cod']);
        $this->assertSame('STANDARD', $body['pkg']['serviceType']);
        $this->assertSame('API', $body['pkg']['integrationSource']);
        $this->assertSame('METRIC', $body['pkgUnitType']);
    }

    public function test_it_uses_normal_shipment_type_when_no_cod(): void
    {
        $body = ShipmentRequestMapper::toBody(
            $this->connectionDto(),
            $this->shipmentDto(codAmount: 0),
        );
        $this->assertSame('NORMAL', $body['pkg']['shipmentType']);
        $this->assertSame(0, $body['pkg']['cod']);
    }

    public function test_it_threads_resolved_village_into_destination_address(): void
    {
        $dto = $this->shipmentDto()->withExtra(['village' => [
            'englishName' => 'Riyadh Dist.',
            'cityId'      => 36134,
            'regionId'    => 1199,
        ]]);

        $body = ShipmentRequestMapper::toBody($this->connectionDto(), $dto);

        $this->assertSame('Riyadh Dist.', $body['destinationAddress']['village']);
        $this->assertSame(36134, $body['destinationAddress']['cityId']);
        $this->assertSame(1199, $body['destinationAddress']['regionId']);
    }

    public function test_it_lets_connection_override_integration_source(): void
    {
        $c = $this->connectionDto(['integration_source' => 'WOOCOMMERCE']);
        $body = ShipmentRequestMapper::toBody($c, $this->shipmentDto());
        $this->assertSame('WOOCOMMERCE', $body['pkg']['integrationSource']);
    }

    // -------------------------------------------------------------

    private function connectionDto(array $settings = []): ConnectionDTO
    {
        return new ConnectionDTO(
            id:              42,
            companyId:       1,
            providerCode:    'logestechs',
            connectionName:  'Default',
            remoteCompanyId: '496',
            domain:          'salesksa.logestechs.com',
            email:           'test@example.com',
            password:        's3cret',
            settings:        $settings,
        );
    }

    private function shipmentDto(float $codAmount = 100.0): ShipmentDTO
    {
        return new ShipmentDTO(
            parcelId:        7,
            referenceNumber: 'RL-7',
            description:    'Box of widgets',
            quantity:       1,
            weight:         1.0,
            codAmount:      $codAmount,
            currency:       'SAR',
            sender:         new AddressDTO(name: 'Merchant', phone: '+966500000000'),
            recipient:      new AddressDTO(name: 'Customer', phone: '+966555555555', line1: '123 St', city: 'Riyadh'),
        );
    }
}
