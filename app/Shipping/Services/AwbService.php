<?php

namespace App\Shipping\Services;

use App\Shipping\DTOs\ConnectionDTO;
use App\Shipping\Factory\ShippingProviderFactory;
use App\Shipping\Models\Shipment;
use App\Shipping\Models\ShippingConnection;

class AwbService
{
    public function __construct(
        private readonly ShippingProviderFactory $factory,
    ) {}

    /**
     * Fetch AWB PDF bytes for one or more shipments. All shipments must share
     * the same connection (and therefore provider).
     *
     * @param Shipment[] $shipments
     */
    public function printForShipments(array $shipments): string
    {
        if (empty($shipments)) {
            return '';
        }

        $connection = $shipments[0]->connection;
        if (! $connection instanceof ShippingConnection) {
            throw new \InvalidArgumentException('Shipment has no connection.');
        }
        $connectionId = $connection->id;
        foreach ($shipments as $s) {
            if ($s->connection_id !== $connectionId) {
                throw new \InvalidArgumentException('AWB batch spans multiple connections.');
            }
        }

        $provider = $this->factory->forConnection($connection);
        $remoteIds = array_filter(array_map(fn (Shipment $s) => $s->remote_shipment_id, $shipments));

        return $provider->printAwb(ConnectionDTO::fromModel($connection), $remoteIds);
    }
}
