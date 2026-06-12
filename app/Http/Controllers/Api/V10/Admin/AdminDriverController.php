<?php

namespace App\Http\Controllers\Api\V10\Admin;

use App\Enums\ParcelStatus;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Parcel;
use App\Models\Backend\ParcelEvent;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;

class AdminDriverController extends Controller
{
    use ApiReturnFormatTrait;

    public function index(Request $request)
    {
        $query = DeliveryMan::query()->with(['user', 'hub'])->latest();

        if ($q = $request->query('q')) {
            $query->whereHas('user', fn ($u) =>
                $u->where('name', 'like', "%$q%")
                  ->orWhere('email', 'like', "%$q%")
                  ->orWhere('mobile', 'like', "%$q%")
                  ->orWhere('unique_id', 'like', "%$q%")
            );
        }
        if ($hubId = $request->query('hub_id')) {
            $query->where('hub_id', (int) $hubId);
        }

        $this->clampToHub($query, $request->user());

        $per = max(10, min(100, (int) $request->query('per_page', 25)));
        $drivers = $query->paginate($per);

        return $this->responseWithSuccess('admin.drivers', [
            'drivers' => $drivers->through(fn ($d) => $this->transform($d)),
        ], 200);
    }

    public function show($id, Request $request)
    {
        $driver = DeliveryMan::with(['user', 'hub'])->findOrFail($id);
        $this->ensureHubMatch($driver, $request->user());

        $today = today();
        $assignedToday = Parcel::where('delivery_man_id', $driver->id)
            ->whereDate('updated_at', $today)
            ->count();
        $deliveredToday = Parcel::where('delivery_man_id', $driver->id)
            ->where('status', ParcelStatus::DELIVERED)
            ->whereDate('updated_at', $today)
            ->count();

        $lastEvent = ParcelEvent::where('delivery_man_id', $driver->id)
            ->latest('id')
            ->first();

        return $this->responseWithSuccess('admin.driver', [
            'driver' => $this->transform($driver),
            'today'  => [
                'assigned'  => $assignedToday,
                'delivered' => $deliveredToday,
            ],
            'last_location' => $lastEvent ? [
                'lat'        => $lastEvent->delivery_lat,
                'lng'        => $lastEvent->delivery_long,
                'updated_at' => optional($lastEvent->updated_at)->toIso8601String(),
            ] : null,
        ], 200);
    }

    private function transform(DeliveryMan $d): array
    {
        return [
            'id'            => $d->id,
            'name'          => optional($d->user)->name,
            'unique_id'     => optional($d->user)->unique_id,
            'phone'         => (string) optional($d->user)->mobile,
            'email'         => optional($d->user)->email,
            'hub_id'        => $d->hub_id,
            'hub_name'      => optional($d->hub)->name,
            'status'        => (int) optional($d->user)->status,
            'current_balance' => (float) ($d->current_balance ?? 0),
        ];
    }

    private function clampToHub($query, $user): void
    {
        $type = (int) $user->user_type;
        if (($type === UserType::HUB || $type === UserType::INCHARGE) && $user->hub_id) {
            $query->where('hub_id', (int) $user->hub_id);
        }
    }

    private function ensureHubMatch(DeliveryMan $driver, $user): void
    {
        $type = (int) $user->user_type;
        if (($type === UserType::HUB || $type === UserType::INCHARGE)
            && $user->hub_id
            && (int) $driver->hub_id !== (int) $user->hub_id) {
            abort(403, 'Hub mismatch');
        }
    }
}
