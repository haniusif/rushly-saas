<?php

namespace App\Shipping\Providers\Logestechs\Mappers;

use App\Shipping\DTOs\ShipmentDTO;

class ShipmentResponseMapper
{
    /**
     * Logestechs create-shipment response shape (real, captured against
     * company 496):
     *   { id, companyId, customerId, barcode, barcodeImage, expectedDeliveryDate, ... }
     */
    public static function apply(ShipmentDTO $dto, array $response): ShipmentDTO
    {
        $remoteId = (string) ($response['id'] ?? '');
        $awb      = (string) ($response['barcode'] ?? '');
        $awbPdf   = (string) ($response['barcodeImage'] ?? '');

        return $dto->withRemote(
            remoteId:    $remoteId !== '' ? $remoteId : ($awb !== '' ? $awb : ''),
            awb:         $awb !== '' ? $awb : null,
            awbPdf:      $awbPdf !== '' ? $awbPdf : null,
            rawResponse: $response,
        );
    }
}
