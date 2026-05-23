<?php

namespace App\Repositories\Wms;

use App\Models\Backend\Wms\WmsCycleCount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WmsCycleCountRepository implements WmsCycleCountRepositoryInterface
{
    public function all(?Request $request = null)
    {
        $q = WmsCycleCount::companywise()->with(['hub', 'assignedTo']);
        if ($request) {
            if ($request->filled('status')) $q->where('status', $request->input('status'));
            if ($request->filled('hub_id')) $q->where('hub_id', $request->input('hub_id'));
        }
        return $q->latest('id')->paginate(25);
    }

    public function find(int $id): ?WmsCycleCount
    {
        return WmsCycleCount::companywise()->with(['hub', 'assignedTo'])->find($id);
    }

    public function create(array $data): WmsCycleCount
    {
        $data['company_id']   = settings()->id;
        $data['assigned_to']  = $data['assigned_to'] ?? Auth::id();
        $data['count_number'] = $data['count_number'] ?? $this->nextCountNumber();
        return WmsCycleCount::create($data);
    }

    public function start(WmsCycleCount $c): bool
    {
        $c->status     = 'in_progress';
        $c->started_at = now();
        return (bool) $c->save();
    }

    public function complete(WmsCycleCount $c): bool
    {
        $c->status       = 'completed';
        $c->completed_at = now();
        return (bool) $c->save();
    }

    public function nextCountNumber(): string
    {
        $year = date('Y');
        $next = WmsCycleCount::companywise()->whereYear('created_at', $year)->count() + 1;
        return sprintf('CC-%s-%05d', $year, $next);
    }
}
