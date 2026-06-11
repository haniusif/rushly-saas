<?php

namespace App\Console\Commands;

use App\Enums\ParcelStatus;
use App\Models\Backend\Parcel;
use App\Models\Backend\ParcelEvent;
use App\Models\Backend\Parcels_3pl;
use App\Repositories\Parcel\ParcelInterface;
use App\Services\JetService;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Poll J&T (Jet) TrackOrder per AWB and sync status into the parcel timeline.
 * Jet's track endpoint accepts one AWB per request, so this iterates rather
 * than batches.
 *
 * Schedule (registered in app/Console/Kernel.php):
 *   $schedule->command('jet:sync-tracking')->everyFifteenMinutes()->withoutOverlapping();
 *
 * NOTE: Same tenant-scoping caveat as Panda/Aramex jobs — runs unscoped until
 * `parcels_3pl.company_id` is added (see 3PL.md issue #3).
 */
class JetSyncTracking extends Command
{
    protected $signature = 'jet:sync-tracking {--limit=200 : Max AWBs per run}';
    protected $description = 'Pull tracking updates from J&T (Jet) and sync parcel status';

    public function __construct(
        private JetService $jet,
        private ParcelInterface $parcelRepo
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! $this->jet->isConfigured()) {
            $this->warn('Jet is not configured — skipping.');
            return self::SUCCESS;
        }

        $terminal = [
            ParcelStatus::DELIVERED,
            ParcelStatus::PARTIAL_DELIVERED,
            ParcelStatus::CANCELLED,
            ParcelStatus::RETURN_RECEIVED_BY_MERCHANT,
        ];

        $rows = Parcels_3pl::where('parcel_3pl_name', 'jet')
            ->whereNotNull('awb_number')
            ->whereHas('parcel', fn ($q) => $q->whereNotIn('status', $terminal))
            ->orderByDesc('id')
            ->limit((int) $this->option('limit'))
            ->get(['id', 'parcel_id', 'awb_number']);

        if ($rows->isEmpty()) {
            $this->info('No Jet AWBs to sync.');
            return self::SUCCESS;
        }

        $this->info('Querying ' . $rows->count() . ' AWB(s)…');
        $updated = 0;
        $delivered = 0;

        foreach ($rows as $row) {
            $resp = $this->jet->trackOrder((string) $row->awb_number);

            if (! empty($resp['_error']) || isset($resp['error_id'])) {
                Log::info('Jet track error', ['awb' => $row->awb_number, 'resp' => $resp]);
                continue;
            }

            $history = $resp['history'] ?? [];
            if (empty($history)) continue;

            // Latest event is last in the array per the docs sample. Pull last.
            $latest = end($history);
            if (! is_array($latest)) continue;

            $code   = (int) ($latest['status_code'] ?? 0);
            $desc   = (string) ($latest['status'] ?? '');
            $when   = (string) ($latest['date_time'] ?? '');
            $note   = trim('Jet: ' . ($desc !== '' ? $desc : ('code ' . $code)));

            // Refresh cached tracking columns
            Parcels_3pl::where('id', $row->id)->update([
                'current_status'  => strtoupper(mb_substr($desc, 0, 50)),
                'status_datetime' => $when !== '' ? $when : now(),
            ]);

            $parcel = Parcel::find($row->parcel_id);
            if (! $parcel) continue;

            $mapped = $this->mapStatus($code, $desc);
            if ($mapped === null) {
                $this->logEvent($parcel->id, (int) $parcel->status, $note);
                continue;
            }

            if ($mapped === ParcelStatus::DELIVERED
                && (int) $parcel->status === ParcelStatus::DELIVERY_MAN_ASSIGN) {
                try {
                    $this->parcelRepo->parcelDelivered($parcel->id, new Request([
                        'note'              => $note,
                        'send_sms_customer' => 'off',
                        'send_sms_merchant' => 'off',
                    ]));
                    $delivered++;
                    continue;
                } catch (\Throwable $e) {
                    Log::error('Jet sync auto-deliver failed', [
                        'parcel_id' => $parcel->id, 'error' => $e->getMessage(),
                    ]);
                }
            }

            if ((int) $parcel->status !== $mapped) {
                try {
                    $parcel->status = $mapped;
                    if ($mapped === ParcelStatus::CANCELLED) {
                        $parcel->cancellationReason = $note;
                    }
                    $parcel->save();
                    $updated++;
                } catch (\Throwable $e) {
                    Log::warning('Jet sync status update failed', [
                        'parcel_id' => $parcel->id, 'error' => $e->getMessage(),
                    ]);
                }
            }

            // Cancellations auto-log via the model `updated` hook.
            if ($mapped !== ParcelStatus::CANCELLED) {
                $this->logEvent($parcel->id, $mapped, $note);
            }
        }

        $this->info("✅ Updated $updated parcel(s); $delivered auto-delivered.");
        return self::SUCCESS;
    }

    /**
     * Map a J&T tracking code (with description fallback) to local ParcelStatus.
     * Codes per docs: 101 Manifes, 100 various, 150/151/152 Problem, 162 Cancel
     * by Seller, 163 Cancel by J&T, 200 Delivered, 401/402 Returned.
     */
    private function mapStatus(int $code, string $description): ?int
    {
        switch ($code) {
            case 200: return ParcelStatus::DELIVERED;
            case 162:
            case 163: return ParcelStatus::CANCELLED;
            case 401: return ParcelStatus::RETURN_TO_COURIER;
            case 402: return ParcelStatus::RETURN_RECEIVED_BY_MERCHANT;
            case 101: return ParcelStatus::PENDING;
            case 150:
            case 151:
            case 152: return ParcelStatus::ABNORMAL;
        }

        // Code 100 covers multiple events; disambiguate via the description text.
        if ($code === 100) {
            $d = mb_strtolower($description);
            if (str_contains($d, 'telah diterima oleh')) return ParcelStatus::RECEIVED_BY_PICKUP_MAN; // picked up
            if (str_contains($d, 'akan dikirimkan ke'))   return ParcelStatus::TRANSFER_TO_HUB;
            if (str_contains($d, 'telah sampai di'))      return ParcelStatus::RECEIVED_BY_HUB;
            if (str_contains($d, 'akan dikirim ke alamat penerima')) return ParcelStatus::DELIVERY_MAN_ASSIGN;
        }

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
            Log::warning('Jet sync event-log failed', [
                'parcel_id' => $parcelId,
                'error'     => $ex->getMessage(),
            ]);
        }
    }
}
