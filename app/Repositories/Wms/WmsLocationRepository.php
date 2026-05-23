<?php

namespace App\Repositories\Wms;

use App\Models\Backend\Wms\WmsLocation;
use Illuminate\Http\Request;

class WmsLocationRepository implements WmsLocationRepositoryInterface
{
    public function all(?Request $request = null)
    {
        $q = WmsLocation::companywise()->with('hub');
        if ($request) {
            if ($request->filled('hub_id')) $q->where('hub_id', $request->input('hub_id'));
            if ($request->filled('zone'))   $q->where('zone', $request->input('zone'));
            if ($request->filled('aisle'))  $q->where('aisle', $request->input('aisle'));
            if ($request->filled('type'))   $q->where('type', $request->input('type'));
        }
        return $q->orderBy('code')->paginate(50);
    }

    public function find(int $id): ?WmsLocation
    {
        return WmsLocation::companywise()->with(['hub', 'stocks.product'])->find($id);
    }

    public function findByCode(string $code): ?WmsLocation
    {
        return WmsLocation::companywise()->where('code', $code)->first();
    }

    public function create(array $data): WmsLocation
    {
        $data['company_id'] = $data['company_id'] ?? settings()->id;
        // Auto-generate code if not provided.
        if (empty($data['code'])) {
            $data['code'] = WmsLocation::buildCode([
                $data['zone'] ?? null,
                $data['aisle'] ?? null,
                $data['rack'] ?? null,
                $data['shelf'] ?? null,
                $data['bin'] ?? null,
            ]);
        }
        return WmsLocation::create($data);
    }

    public function update(WmsLocation $l, array $data): bool
    {
        return (bool) $l->update($data);
    }

    public function delete(WmsLocation $l): bool
    {
        return (bool) $l->delete();
    }

    public function tree(?int $hubId = null): array
    {
        $q = WmsLocation::companywise();
        if ($hubId) $q->where('hub_id', $hubId);
        $rows = $q->get();

        $tree = [];
        foreach ($rows as $row) {
            $z = $row->zone ?: '—';
            $a = $row->aisle ?: '—';
            $tree[$z][$a][] = $row;
        }
        return $tree;
    }
}
