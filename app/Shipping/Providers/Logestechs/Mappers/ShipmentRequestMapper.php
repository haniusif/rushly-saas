<?php

namespace App\Shipping\Providers\Logestechs\Mappers;

use App\Shipping\DTOs\ConnectionDTO;
use App\Shipping\DTOs\ShipmentDTO;

/**
 * Convert a canonical ShipmentDTO + ConnectionDTO into the Logestechs
 * /ship/request/by-email body. All Logestechs-specific quirks live here:
 *
 *   - email/password embedded inside the body (per-call cred check)
 *   - cod as integer (not float)
 *   - destinationAddress wrapping village/cityId/regionId from the extra blob
 *   - integrationSource defaults to 'API' but tenants override via settings
 *
 * Caller is responsible for resolving the village (via searchVillages) and
 * threading the resolved {cityId, regionId} into ShipmentDTO->extra.
 */
class ShipmentRequestMapper
{
    public static function toBody(ConnectionDTO $c, ShipmentDTO $s): array
    {
        $cod    = (int) round($s->codAmount);
        $itemName = mb_substr($s->description, 0, 200);

        $village = $s->extra['village'] ?? null; // {englishName, cityId, regionId} from resolveVillage
        $villageStr = $village['englishName']
            ?? $village['arabicName']
            ?? $s->recipient->area
            ?? $s->recipient->city
            ?? '';

        return [
            'email'    => (string) $c->email,
            'password' => (string) $c->password,
            'pkg' => [
                'receiverName'              => $s->recipient->name ?? '',
                'cod'                       => $cod,
                'notes'                     => '',
                'supplierInvoice'           => $s->referenceNumber,
                'packageItemsToDeliverList' => [[
                    'name' => $itemName,
                    'cod'  => $cod,
                ]],
                'senderName'         => $s->sender->name ?? '',
                'businessSenderName' => $s->sender->name ?? '',
                'senderPhone'        => $s->sender->phone ?? '',
                'receiverPhone'      => $s->recipient->phone ?? '',
                'receiverPhone2'     => '',
                'serviceType'        => (string) $c->setting('service_type', 'STANDARD'),
                // Logestechs rejects any other value ('NORMAL' was returning
                // HTTP 400 'model.shipmentType null'). 'COD' is the only
                // accepted enum; non-COD shipments still validate as long as
                // pkg.cod = 0.
                'shipmentType'       => 'COD',
                'quantity'           => max(1, $s->quantity),
                'description'        => 'Reference: ' . $s->referenceNumber,
                'integrationSource'  => (string) $c->setting(
                    'integration_source',
                    config('shipping.providers.logestechs.config.integration_source', 'API')
                ),
            ],
            'destinationAddress' => array_filter([
                'village'      => $villageStr,
                'cityId'       => $village['cityId']   ?? null,
                'regionId'     => $village['regionId'] ?? null,
                'addressLine1' => mb_substr((string) ($s->recipient->line1 ?? ''), 0, 250),
                'addressLine2' => '',
            ], fn ($v) => $v !== null && $v !== ''),
            'pkgUnitType' => 'METRIC',
        ];
    }
}
