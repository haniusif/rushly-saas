<?php

namespace App\Shipping\Repositories;

use App\Shipping\Models\ShippingConnection;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ShippingConnectionRepository
{
    public function find(int $id): ?ShippingConnection
    {
        return ShippingConnection::query()->with('provider')->find($id);
    }

    public function findForCompany(int $id, int $companyId): ?ShippingConnection
    {
        return ShippingConnection::query()
            ->with('provider')
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->first();
    }

    public function listForCompany(int $companyId, ?string $providerCode = null): Collection
    {
        $q = ShippingConnection::query()
            ->with('provider')
            ->where('company_id', $companyId)
            ->orderByDesc('is_default')
            ->orderBy('connection_name');

        if ($providerCode !== null) {
            $q->whereHas('provider', fn ($p) => $p->where('code', $providerCode));
        }

        return $q->get();
    }

    public function defaultForCompany(int $companyId, string $providerCode): ?ShippingConnection
    {
        return ShippingConnection::query()
            ->with('provider')
            ->where('company_id', $companyId)
            ->whereHas('provider', fn ($p) => $p->where('code', $providerCode))
            ->where('is_default', true)
            ->where('status', 'active')
            ->first()
            ?? ShippingConnection::query()
                ->with('provider')
                ->where('company_id', $companyId)
                ->whereHas('provider', fn ($p) => $p->where('code', $providerCode))
                ->where('status', 'active')
                ->orderBy('id')
                ->first();
    }

    /**
     * Activate one connection and demote any other defaults for the same
     * (company, provider) pair in a single transaction.
     */
    public function setDefault(ShippingConnection $connection): void
    {
        DB::transaction(function () use ($connection) {
            ShippingConnection::query()
                ->where('company_id', $connection->company_id)
                ->where('provider_id', $connection->provider_id)
                ->where('id', '<>', $connection->id)
                ->update(['is_default' => false]);

            $connection->is_default = true;
            $connection->save();
        });
    }

    public function activeForSync(): Collection
    {
        return ShippingConnection::query()
            ->with('provider')
            ->where('status', 'active')
            ->whereHas('provider', fn ($p) => $p->where('status', 'active'))
            ->get();
    }
}
