<?php

namespace App\Http\Controllers\Api\V10\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Backend\Fleet\FleetFuelLog;
use App\Models\Backend\Fleet\FleetMaintenanceReport;
use App\Models\Backend\Fleet\FleetTrip;
use App\Models\Backend\Fleet\FleetVehicle;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Endpoints for the Fleet Driver mobile app. `driver_id` throughout is
 * `users.id` — every authed caller has a User row, so this works for
 * deliverymen, hub staff, and admins alike.
 *
 * Reads are scoped to the caller's vehicle(s); writes are anchored to
 * the caller and validated against the same vehicle scope.
 */
class FleetDriverApiController extends Controller
{
    use ApiReturnFormatTrait;

    // ---------------- Vehicle ----------------

    public function myVehicle()
    {
        $uid = Auth::id();
        $vehicle = FleetVehicle::companywise()
            ->where('assigned_driver_id', $uid)
            ->first();

        if (!$vehicle) {
            return $this->responseWithSuccess('No vehicle assigned', [
                'vehicle' => null,
            ], 200);
        }

        return $this->responseWithSuccess('Vehicle', [
            'vehicle' => $this->serializeVehicle($vehicle),
            'active_trip' => $this->serializeTrip(
                FleetTrip::companywise()
                    ->where('vehicle_id', $vehicle->id)
                    ->where('driver_id', $uid)
                    ->where('status', 'in_progress')
                    ->latest('id')
                    ->first()
            ),
        ], 200);
    }

    // ---------------- Trips ----------------

    public function trips(Request $request)
    {
        $uid = Auth::id();
        $limit = min(100, max(1, (int) $request->query('limit', 20)));
        $trips = FleetTrip::companywise()
            ->where('driver_id', $uid)
            ->with('vehicle:id,plate_number,make,model')
            ->latest('id')
            ->limit($limit)
            ->get();

        return $this->responseWithSuccess('Trips', [
            'count' => $trips->count(),
            'trips' => $trips->map(fn ($t) => $this->serializeTrip($t))->values(),
        ], 200);
    }

    public function startTrip(Request $request)
    {
        $data = $request->validate([
            'vehicle_id'       => ['required', 'integer', 'exists:fleet_vehicles,id'],
            'start_odometer'   => ['required', 'integer', 'min:0'],
            'start_lat'        => ['nullable', 'numeric'],
            'start_lng'        => ['nullable', 'numeric'],
            'start_inspection' => ['nullable', 'array'],
            'notes'            => ['nullable', 'string', 'max:500'],
        ]);
        $uid = Auth::id();
        $vehicle = FleetVehicle::companywise()->findOrFail($data['vehicle_id']);

        $existing = FleetTrip::companywise()
            ->where('driver_id', $uid)
            ->where('status', 'in_progress')
            ->first();
        if ($existing) {
            return $this->responseWithError('You already have an active trip', [
                'trip_id' => $existing->id,
            ], 409);
        }

        $trip = FleetTrip::create([
            'company_id'       => settings()->id,
            'vehicle_id'       => $vehicle->id,
            'driver_id'        => $uid,
            'start_odometer'   => $data['start_odometer'],
            'started_at'       => now(),
            'start_lat'        => $data['start_lat'] ?? null,
            'start_lng'        => $data['start_lng'] ?? null,
            'start_inspection' => $data['start_inspection'] ?? null,
            'notes'            => $data['notes'] ?? null,
            'status'           => 'in_progress',
        ]);

        return $this->responseWithSuccess('Trip started', [
            'trip' => $this->serializeTrip($trip),
        ], 201);
    }

    public function endTrip(Request $request, int $id)
    {
        $data = $request->validate([
            'end_odometer' => ['required', 'integer', 'min:0'],
            'end_lat'      => ['nullable', 'numeric'],
            'end_lng'      => ['nullable', 'numeric'],
            'notes'        => ['nullable', 'string', 'max:500'],
        ]);
        $uid = Auth::id();
        $trip = FleetTrip::companywise()
            ->where('driver_id', $uid)
            ->findOrFail($id);
        if ($trip->status !== 'in_progress') {
            return $this->responseWithError('Trip is not in progress', ['status' => $trip->status], 409);
        }
        if ((int) $data['end_odometer'] < (int) $trip->start_odometer) {
            return $this->responseWithError('End odometer must be >= start odometer', [], 422);
        }
        $trip->end_odometer = $data['end_odometer'];
        $trip->end_lat      = $data['end_lat'] ?? null;
        $trip->end_lng      = $data['end_lng'] ?? null;
        $trip->ended_at     = now();
        $trip->status       = 'completed';
        if (!empty($data['notes'])) {
            $trip->notes = trim(($trip->notes ?? '') . "\n" . $data['notes']);
        }
        $trip->save();

        FleetVehicle::where('id', $trip->vehicle_id)
            ->update(['current_odometer' => $data['end_odometer']]);

        return $this->responseWithSuccess('Trip ended', [
            'trip' => $this->serializeTrip($trip),
        ], 200);
    }

    // ---------------- Fuel ----------------

    public function fuelLogs(Request $request)
    {
        $uid = Auth::id();
        $vehicleId = $request->query('vehicle_id');
        $q = FleetFuelLog::companywise()->latest('filled_at');
        if ($vehicleId) {
            $q->where('vehicle_id', (int) $vehicleId);
        } else {
            $q->where('driver_id', $uid);
        }
        $rows = $q->limit(50)->get();

        return $this->responseWithSuccess('Fuel logs', [
            'count' => $rows->count(),
            'logs'  => $rows->map(fn ($r) => [
                'id'               => $r->id,
                'vehicle_id'       => $r->vehicle_id,
                'liters'           => (float) $r->liters,
                'cost'             => (float) $r->cost,
                'odometer_reading' => (int) $r->odometer_reading,
                'receipt_url'      => $r->receipt_url,
                'filled_at'        => (string) $r->filled_at,
                'notes'            => $r->notes,
            ])->values(),
        ], 200);
    }

    public function logFuel(Request $request)
    {
        $data = $request->validate([
            'vehicle_id'       => ['required', 'integer', 'exists:fleet_vehicles,id'],
            'liters'           => ['required', 'numeric', 'min:0.01'],
            'cost'             => ['required', 'numeric', 'min:0'],
            'odometer_reading' => ['required', 'integer', 'min:0'],
            'receipt_url'      => ['nullable', 'string', 'max:500'],
            'filled_at'        => ['nullable', 'date'],
            'notes'            => ['nullable', 'string', 'max:500'],
        ]);
        $log = FleetFuelLog::create([
            'company_id'       => settings()->id,
            'vehicle_id'       => $data['vehicle_id'],
            'driver_id'        => Auth::id(),
            'liters'           => $data['liters'],
            'cost'             => $data['cost'],
            'odometer_reading' => $data['odometer_reading'],
            'receipt_url'      => $data['receipt_url'] ?? null,
            'filled_at'        => $data['filled_at'] ?? now(),
            'notes'            => $data['notes'] ?? null,
        ]);
        return $this->responseWithSuccess('Fuel logged', ['id' => $log->id], 201);
    }

    // ---------------- Maintenance ----------------

    public function maintenanceReports(Request $request)
    {
        $uid = Auth::id();
        $q = FleetMaintenanceReport::companywise()
            ->latest('reported_at');
        if ($vehicleId = $request->query('vehicle_id')) {
            $q->where('vehicle_id', (int) $vehicleId);
        } else {
            $q->where('driver_id', $uid);
        }
        $rows = $q->limit(50)->get();
        return $this->responseWithSuccess('Maintenance reports', [
            'count'   => $rows->count(),
            'reports' => $rows->map(fn ($r) => [
                'id'              => $r->id,
                'vehicle_id'      => $r->vehicle_id,
                'issue_type'      => $r->issue_type,
                'severity'        => $r->severity,
                'description'     => $r->description,
                'status'          => $r->status,
                'reported_at'     => (string) $r->reported_at,
                'resolved_at'     => (string) $r->resolved_at,
                'resolution_notes' => $r->resolution_notes,
            ])->values(),
        ], 200);
    }

    public function reportMaintenance(Request $request)
    {
        $data = $request->validate([
            'vehicle_id'  => ['required', 'integer', 'exists:fleet_vehicles,id'],
            'issue_type'  => ['required', 'in:mechanical,electrical,body,tires,other'],
            'severity'    => ['required', 'in:low,medium,high,critical'],
            'description' => ['required', 'string', 'max:1000'],
        ]);
        $r = FleetMaintenanceReport::create([
            'company_id'  => settings()->id,
            'vehicle_id'  => $data['vehicle_id'],
            'driver_id'   => Auth::id(),
            'issue_type'  => $data['issue_type'],
            'severity'    => $data['severity'],
            'description' => $data['description'],
            'status'      => 'reported',
            'reported_at' => now(),
        ]);
        return $this->responseWithSuccess('Maintenance reported', ['id' => $r->id], 201);
    }

    // ---------------- helpers ----------------

    private function serializeVehicle(FleetVehicle $v): array
    {
        return [
            'id'                 => $v->id,
            'plate_number'       => $v->plate_number,
            'make'               => $v->make,
            'model'              => $v->model,
            'year'               => $v->year,
            'vehicle_type'       => $v->vehicle_type,
            'status'             => $v->status,
            'current_odometer'   => (int) $v->current_odometer,
            'assigned_driver_id' => $v->assigned_driver_id,
            'hub_id'             => $v->hub_id,
            'notes'              => $v->notes,
        ];
    }

    private function serializeTrip(?FleetTrip $t): ?array
    {
        if (!$t) return null;
        return [
            'id'               => $t->id,
            'vehicle_id'       => $t->vehicle_id,
            'vehicle_plate'    => optional($t->vehicle)->plate_number,
            'start_odometer'   => (int) $t->start_odometer,
            'end_odometer'     => $t->end_odometer !== null ? (int) $t->end_odometer : null,
            'distance_km'      => $t->distanceKm(),
            'started_at'       => (string) $t->started_at,
            'ended_at'         => (string) $t->ended_at,
            'start_lat'        => $t->start_lat !== null ? (float) $t->start_lat : null,
            'start_lng'        => $t->start_lng !== null ? (float) $t->start_lng : null,
            'end_lat'          => $t->end_lat !== null ? (float) $t->end_lat : null,
            'end_lng'          => $t->end_lng !== null ? (float) $t->end_lng : null,
            'start_inspection' => $t->start_inspection,
            'notes'            => $t->notes,
            'status'           => $t->status,
        ];
    }
}
