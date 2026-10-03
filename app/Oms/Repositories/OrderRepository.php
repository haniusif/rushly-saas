<?php

namespace App\Oms\Repositories;

use App\Oms\Models\Order;
use Illuminate\Database\Eloquent\Collection;

class OrderRepository
{
    public function find(int $id): ?Order
    {
        return Order::query()->with(['items', 'connection.provider'])->find($id);
    }

    public function findForCompany(int $id, int $companyId): ?Order
    {
        return Order::query()
            ->with(['items', 'connection.provider'])
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->first();
    }

    public function findByConnectionAndRemote(int $connectionId, string $remoteOrderId): ?Order
    {
        return Order::query()
            ->with('items')
            ->where('connection_id', $connectionId)
            ->where('remote_order_id', $remoteOrderId)
            ->first();
    }

    public function listForCompany(
        int $companyId,
        ?string $status = null,
        ?int $connectionId = null,
        int $limit = 100,
    ): Collection {
        $q = Order::query()
            ->with(['connection.provider'])
            ->where('company_id', $companyId)
            ->orderByDesc('id');

        if ($status)       $q->where('status', $status);
        if ($connectionId) $q->where('connection_id', $connectionId);

        return $q->limit($limit)->get();
    }
}
