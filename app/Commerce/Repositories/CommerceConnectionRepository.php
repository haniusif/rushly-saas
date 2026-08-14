<?php

namespace App\Commerce\Repositories;

use App\Commerce\Models\CommerceConnection;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CommerceConnectionRepository
{
    public function find(int $id): ?CommerceConnection
    {
        return CommerceConnection::query()->with('provider')->find($id);
    }

    public function findForCompany(int $id, int $companyId): ?CommerceConnection
    {
        return CommerceConnection::query()
            ->with('provider')
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->first();
    }

    public function findByRemoteStore(string $providerCode, string $remoteStoreId): ?CommerceConnection
    {
        return CommerceConnection::query()
            ->with('provider')
            ->whereHas('provider', fn ($p) => $p->where('code', $providerCode))
            ->where('remote_store_id', $remoteStoreId)
            ->first();
    }

    public function listForCompany(int $companyId, ?string $providerCode = null): Collection
    {
        $q = CommerceConnection::query()
            ->with('provider')
            ->where('company_id', $companyId)
            ->orderByDesc('is_default')
            ->orderBy('connection_name');

        if ($providerCode !== null) {
            $q->whereHas('provider', fn ($p) => $p->where('code', $providerCode));
        }

        return $q->get();
    }

    public function defaultForCompany(int $companyId, string $providerCode): ?CommerceConnection
    {
        return CommerceConnection::query()
            ->with('provider')
            ->where('company_id', $companyId)
            ->whereHas('provider', fn ($p) => $p->where('code', $providerCode))
            ->where('is_default', true)
            ->where('status', 'active')
            ->first()
            ?? CommerceConnection::query()
                ->with('provider')
                ->where('company_id', $companyId)
                ->whereHas('provider', fn ($p) => $p->where('code', $providerCode))
                ->where('status', 'active')
                ->orderBy('id')
                ->first();
    }

    /**
     * Activate one connection and demote any other defaults for the same
     * (company, provider) pair in a single transaction. Same shape as
     * ShippingConnectionRepository::setDefault().
     */
    public function setDefault(CommerceConnection $connection): void
    {
        DB::transaction(function () use ($connection) {
            CommerceConnection::query()
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
        return CommerceConnection::query()
            ->with('provider')
            ->where('status', 'active')
            ->whereHas('provider', fn ($p) => $p->where('status', 'active'))
            ->get();
    }
}
