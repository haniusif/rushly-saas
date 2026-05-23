<?php

namespace App\Repositories;

use App\Enums\AbnormalSeverity;
use App\Enums\ParcelStatus;
use App\Models\Backend\AbnormalShipment;
use App\Models\Backend\Parcel;
use App\Models\Backend\ParcelEvent;
use App\Models\Config;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AbnormalShipmentRepository implements AbnormalShipmentRepositoryInterface
{
    public function all(Request $request = null)
    {
        $q = AbnormalShipment::companywise()->with(['parcel', 'assignedTo', 'resolvedBy']);

        if ($request) {
            if ($request->filled('severity'))    $q->where('severity', $request->input('severity'));
            if ($request->filled('status'))      $q->where('status', $request->input('status'));
            if ($request->filled('assigned_to')) $q->where('assigned_to', $request->input('assigned_to'));
            if ($request->filled('min_days'))    $q->where('stale_days', '>=', (int) $request->input('min_days'));
        }

        return $q->orderByDesc('stale_days')->paginate(25);
    }

    public function find(int $id): ?AbnormalShipment
    {
        return AbnormalShipment::companywise()
            ->with(['parcel.merchant', 'assignedTo', 'resolvedBy', 'ndrs'])
            ->find($id);
    }

    /**
     * Detect abnormal shipments for the *current tenant* (settings()->id).
     * Caller (the console command) iterates tenants and calls this per-tenant.
     */
    public function detect(int $thresholdDays = 3): array
    {
        $companyId = settings()->id;
        $cutoff    = Carbon::now()->subDays($thresholdDays);

        // Statuses considered "terminal" — never abnormal regardless of inactivity.
        $terminal = [
            ParcelStatus::DELIVERED,
            ParcelStatus::PARTIAL_DELIVERED,
            ParcelStatus::RETURNED_MERCHANT,
            ParcelStatus::RETURN_RECEIVED_BY_MERCHANT,
            ParcelStatus::DELIVERED_CANCEL,           // explicitly cancelled doesn't count
        ];

        // Parcels for this tenant that haven't had a parcel_events row since cutoff.
        $latestEvent = DB::table('parcel_events')
            ->select('parcel_id', DB::raw('MAX(created_at) as last_event_at'))
            ->groupBy('parcel_id');

        $candidates = DB::table('parcels')
            ->leftJoinSub($latestEvent, 'le', 'le.parcel_id', '=', 'parcels.id')
            ->where('parcels.company_id', $companyId)
            ->whereNotIn('parcels.status', $terminal)
            ->where(function ($q) use ($cutoff) {
                $q->whereNull('le.last_event_at')
                  ->orWhere('le.last_event_at', '<=', $cutoff);
            })
            ->select('parcels.id as parcel_id', 'le.last_event_at', 'parcels.updated_at as fallback_at')
            ->get();

        $created = 0;
        $updated = 0;

        foreach ($candidates as $row) {
            $lastAt = $row->last_event_at ?: $row->fallback_at;
            if (!$lastAt) continue;

            $staleDays = Carbon::parse($lastAt)->diffInDays(Carbon::now());
            $severity  = $this->severityFor($staleDays);

            $existing = AbnormalShipment::companywise()
                ->where('parcel_id', $row->parcel_id)
                ->whereNotIn('status', ['resolved', 'closed_lost'])
                ->first();

            if ($existing) {
                $existing->stale_days    = $staleDays;
                $existing->severity      = $severity;
                $existing->last_event_at = $lastAt;
                $existing->save();
                $updated++;
            } else {
                AbnormalShipment::create([
                    'parcel_id'     => $row->parcel_id,
                    'company_id'    => $companyId,
                    'detected_at'   => now(),
                    'last_event_at' => $lastAt,
                    'stale_days'    => $staleDays,
                    'severity'      => $severity,
                    'status'        => 'open',
                ]);
                $created++;
            }
        }

        return ['created' => $created, 'updated' => $updated, 'scanned' => $candidates->count()];
    }

    public function assign(AbnormalShipment $a, int $userId): bool
    {
        $a->assigned_to = $userId;
        if ($a->status === 'open') $a->status = 'investigating';
        return (bool) $a->save();
    }

    public function resolve(AbnormalShipment $a, int $userId, ?string $note = null): bool
    {
        $a->status          = 'resolved';
        $a->resolved_by     = $userId;
        $a->resolved_at     = now();
        if ($note) $a->resolution_note = $note;
        return (bool) $a->save();
    }

    public function autoResolveByParcel(int $parcelId): int
    {
        return AbnormalShipment::companywise()
            ->where('parcel_id', $parcelId)
            ->whereNotIn('status', ['resolved', 'closed_lost'])
            ->update([
                'status'      => 'resolved',
                'resolved_at' => now(),
                'resolution_note' => 'Auto-resolved: new parcel event recorded.',
            ]);
    }

    public function getThresholdDays(?int $companyId = null): int
    {
        $companyId = $companyId ?? settings()->id;
        $val = Config::where('company_id', $companyId)
            ->where('key', 'abnormal_threshold_days')
            ->value('value');
        return $val ? max(1, (int) $val) : 3;
    }

    protected function severityFor(int $days): string
    {
        if ($days >= 7) return AbnormalSeverity::CRITICAL;
        if ($days >= 5) return AbnormalSeverity::DANGER;
        return AbnormalSeverity::WARNING;
    }
}
