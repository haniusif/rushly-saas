<?php

namespace App\Console\Commands;

use App\Enums\ParcelStatus;
use App\Models\Backend\Parcel;
use App\Models\Backend\ParcelEvent;
use App\Models\Backend\Parcels_3pl;
use App\Repositories\Parcel\ParcelInterface;
use App\Services\AramexService;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Poll Aramex TrackShipments for all outstanding Aramex-assigned parcels and
 * sync their status into the local timeline.
 *
 * Schedule (already wired in app/Console/Kernel.php):
 *   $schedule->command('aramex:sync-tracking')->everyFifteenMinutes();
 *
 * NOTE: Until `parcels_3pl.company_id` is added, this job runs unscoped across
 * all tenants — same caveat as Panda's tracking job (see 3PL.md issue #3).
 */
class AramexSyncTracking extends Command
{
    protected $signature = 'aramex:sync-tracking {--limit=500 : Max AWBs per run}';
    protected $description = 'Pull tracking updates from Aramex and sync parcel status';

    public function __construct(
        private AramexService $aramex,
        private ParcelInterface $parcelRepo
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! $this->aramex->isConfigured()) {
            $this->warn('Aramex is not configured — skipping.');
            return self::SUCCESS;
        }

        // Only fetch AWBs that are still in flight. Terminal statuses (delivered,
        // cancelled, returned-to-merchant) are filtered out to keep the call small.
        $terminal = [
            ParcelStatus::DELIVERED,
            ParcelStatus::PARTIAL_DELIVERED,
            ParcelStatus::CANCELLED,
            ParcelStatus::RETURN_RECEIVED_BY_MERCHANT,
        ];

        $rows = Parcels_3pl::where('parcel_3pl_name', 'aramex')
            ->whereNotNull('awb_number')
            ->whereHas('parcel', fn ($q) => $q->whereNotIn('status', $terminal))
            ->orderByDesc('id')
            ->limit((int) $this->option('limit'))
            ->get(['id', 'parcel_id', 'awb_number']);

        if ($rows->isEmpty()) {
            $this->info('No Aramex AWBs to sync.');
            return self::SUCCESS;
        }

        $awbs = $rows->pluck('awb_number')->all();
        $this->info('Querying ' . count($awbs) . ' AWB(s)…');

        $resp = $this->aramex->trackShipments($awbs, true);
        if (! empty($resp['_error']) || ! empty($resp['HasErrors'])) {
            $this->error('Aramex TrackShipments failed: ' . ($resp['message'] ?? 'HasErrors=true'));
            Log::warning('Aramex sync failed', ['response' => $resp]);
            return self::FAILURE;
        }

        // The response shape is awkward — array of {Key, Value: {TrackingResult: [...]}}
        $results = $resp['TrackingResults']['KeyValueOfstringArrayOfTrackingResultmFAtaEAk'] ?? [];
        if (! isset($results[0])) {
            $results = $results ? [$results] : [];
        }

        $updated = 0;
        $delivered = 0;

        foreach ($results as $r) {
            $awb     = $r['Key'] ?? null;
            $events  = $r['Value']['TrackingResult'] ?? [];
            if (! isset($events[0])) {
                $events = $events ? [$events] : [];
            }
            if (! $awb || empty($events)) {
                continue;
            }

            $latest = $events[0]; // GetLastTrackingUpdateOnly was true, so this is the most recent
            $code   = (string) ($latest['UpdateCode'] ?? '');
            $desc   = (string) ($latest['UpdateDescription'] ?? '');
            $when   = (string) ($latest['UpdateDateTime'] ?? '');

            $row    = $rows->firstWhere('awb_number', $awb);
            if (! $row) continue;
            $parcel = Parcel::find($row->parcel_id);
            if (! $parcel) continue;

            // Refresh tracking columns on parcels_3pl
            Parcels_3pl::where('id', $row->id)->update([
                'current_status'  => strtoupper($desc),
                'status_datetime' => $when !== '' ? $when : now(),
            ]);

            $mapped = $this->mapStatus($code, $desc);
            if ($mapped === null) {
                $this->logEvent($parcel->id, (int) $parcel->status, "Aramex: $code — $desc");
                continue;
            }

            if ($mapped === ParcelStatus::DELIVERED
                && (int) $parcel->status === ParcelStatus::DELIVERY_MAN_ASSIGN) {
                try {
                    $this->parcelRepo->parcelDelivered($parcel->id, new Request([
                        'note'              => "Aramex: $desc",
                        'send_sms_customer' => 'off',
                        'send_sms_merchant' => 'off',
                    ]));
                    $delivered++;
                    continue;
                } catch (\Throwable $e) {
                    Log::error('Aramex sync auto-deliver failed', [
                        'parcel_id' => $parcel->id, 'error' => $e->getMessage(),
                    ]);
                }
            }

            if ((int) $parcel->status !== $mapped) {
                try {
                    $parcel->status = $mapped;
                    if ($mapped === ParcelStatus::CANCELLED) {
                        $parcel->cancellationReason = "Aramex: $desc";
                    }
                    $parcel->save();
                    $updated++;
                } catch (\Throwable $e) {
                    Log::warning('Aramex sync status update failed', [
                        'parcel_id' => $parcel->id, 'error' => $e->getMessage(),
                    ]);
                }
            }

            // Cancellations are auto-logged by the model's `updated` hook
            if ($mapped !== ParcelStatus::CANCELLED) {
                $this->logEvent($parcel->id, $mapped, "Aramex: $desc");
            }
        }

        $this->info("✅ Updated $updated parcel(s); $delivered auto-delivered.");
        return self::SUCCESS;
    }

    /**
     * Map an Aramex tracking code/description to a local ParcelStatus.
     * Falls back to the description if the code is unfamiliar — Aramex has
     * many codes; the description is the most stable signal.
     */
    private function mapStatus(string $code, string $description): ?int
    {
        $d = strtolower($description);

        if (str_contains($d, 'delivered'))                  return ParcelStatus::DELIVERED;
        if (str_contains($d, 'out for delivery'))           return ParcelStatus::DELIVERY_MAN_ASSIGN;
        if (str_contains($d, 'picked up'))                  return ParcelStatus::RECEIVED_BY_PICKUP_MAN;
        if (str_contains($d, 'received') && str_contains($d, 'destination')) return ParcelStatus::RECEIVED_BY_HUB;
        if (str_contains($d, 'received') && str_contains($d, 'origin'))      return ParcelStatus::RECEIVED_WAREHOUSE;
        if (str_contains($d, 'return') && str_contains($d, 'shipper'))       return ParcelStatus::RETURN_RECEIVED_BY_MERCHANT;
        if (str_contains($d, 'return'))                                       return ParcelStatus::RETURN_TO_COURIER;
        if (str_contains($d, 'cancel'))                     return ParcelStatus::CANCELLED;
        if (str_contains($d, 'shipper provided'))           return ParcelStatus::PENDING;
        if (str_contains($d, 'in transit'))                 return ParcelStatus::TRANSFER_TO_HUB;

        return null; // log-only
    }

    private function logEvent(int $parcelId, int $statusId, string $note): void
    {
        try {
            $e = new ParcelEvent();
            $e->parcel_id     = $parcelId;
            $e->parcel_status = $statusId;
            $e->note          = $note;
            $e->created_by    = Auth::id();
            $e->save();
        } catch (\Throwable $ex) {
            Log::warning('Aramex sync event-log failed', [
                'parcel_id' => $parcelId,
                'error'     => $ex->getMessage(),
            ]);
        }
    }
}
