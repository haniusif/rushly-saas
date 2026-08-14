<?php

namespace App\Shipping\Repositories;

use App\Shipping\Models\Shipment;
use Illuminate\Database\Eloquent\Collection;

class ShipmentRepository
{
    public function find(int $id): ?Shipment
    {
        return Shipment::query()->with('connection.provider')->find($id);
    }

    public function findByConnectionAndRemote(int $connectionId, string $remoteShipmentId): ?Shipment
    {
        return Shipment::query()
            ->where('connection_id', $connectionId)
            ->where('remote_shipment_id', $remoteShipmentId)
            ->first();
    }

    public function findByParcelAndConnection(int $parcelId, int $connectionId): ?Shipment
    {
        return Shipment::query()
            ->where('parcel_id', $parcelId)
            ->where('connection_id', $connectionId)
            ->orderByDesc('id')
            ->first();
    }

    public function pendingForConnection(int $connectionId, int $limit): Collection
    {
        return Shipment::query()
            ->where('connection_id', $connectionId)
            ->whereNotNull('remote_shipment_id')
            ->nonTerminal()
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function lastForParcel(int $parcelId): ?Shipment
    {
        return Shipment::query()
            ->where('parcel_id', $parcelId)
            ->whereNotNull('remote_shipment_id')
            ->orderByDesc('id')
            ->first();
    }
}
