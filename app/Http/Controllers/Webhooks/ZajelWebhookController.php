<?php

namespace App\Http\Controllers\Webhooks;

use App\Enums\ParcelStatus;
use App\Http\Controllers\Controller;
use App\Models\Backend\Parcel;
use App\Models\Backend\ParcelEvent;
use App\Models\Backend\Parcels_3pl;
use App\Repositories\Parcel\ParcelInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Receives status events from Zajel for shipments we previously created.
 * Auth: shared secret in the `X-AUTH-API-KEY` header matching
 * config('services.zajel.webhook_secret').
 *
 * Per the Zajel docs, the body is JSON of the shape:
 * {
 *   reference_number, customer_reference_number, status, event_date_time,
 *   description, received_by, delivery_courier, pod, failure_reason
 * }
 */
class ZajelWebhookController extends Controller
{
    public function __construct(private ParcelInterface $parcelRepo) {}

    public function handle(Request $request)
    {
        // 1. Shared-secret check
        $expected = (string) config('services.zajel.webhook_secret');
        $provided = (string) $request->header('X-AUTH-API-KEY', '');
        if ($expected === '' || ! hash_equals($expected, $provided)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // 2. Parse
        $ref    = trim((string) $request->input('reference_number'));
        $status = strtolower(trim((string) $request->input('status')));
        $desc   = (string) ($request->input('description') ?? '');
        $when   = (string) ($request->input('event_date_time') ?? '');

        if ($ref === '' || $status === '') {
            return response()->json(['message' => 'Missing reference_number or status'], 422);
        }

        // 3. Resolve to a known parcel
        $row = Parcels_3pl::where('awb_number', $ref)
            ->where('parcel_3pl_name', 'zajel')
            ->first();
        if (! $row) {
            Log::info('Zajel webhook: unknown reference', ['ref' => $ref, 'status' => $status]);
            return response()->json(['message' => 'Unknown reference_number'], 404);
        }
        $parcel = Parcel::find($row->parcel_id);
        if (! $parcel) {
            return response()->json(['message' => 'Linked parcel missing'], 404);
        }

        // 4. Always: refresh tracking columns on parcels_3pl
        $row->update([
            'current_status'  => strtoupper($status),
            'status_datetime' => $when !== '' ? $when : now(),
        ]);

        // 5. Map → local ParcelStatus and apply
        $mapped = $this->mapStatus($status);
        $note   = trim('Zajel: ' . $status . ($desc !== '' ? ' — ' . $desc : ''));

        if ($mapped === null) {
            // Log-only event (attempted, on_hold, softdata_*) — don't change status
            $this->logEvent($parcel->id, (int) $parcel->status, $note);
            return response()->json(['message' => 'Event logged'], 200);
        }

        // For DELIVERED, route through the repo so balances/notifications fire.
        if ($mapped === ParcelStatus::DELIVERED
            && (int) $parcel->status === ParcelStatus::DELIVERY_MAN_ASSIGN) {
            try {
                $this->parcelRepo->parcelDelivered($parcel->id, new Request([
                    'note'              => $note,
                    'send_sms_customer' => 'off',
                    'send_sms_merchant' => 'off',
                ]));
                return response()->json(['message' => 'Delivered'], 200);
            } catch (\Throwable $e) {
                Log::error('Zajel webhook auto-deliver failed', [
                    'parcel_id' => $parcel->id,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        // Idempotent — skip if already at target status (but still log so the
        // timeline reflects the Zajel ping).
        if ((int) $parcel->status === $mapped) {
            $this->logEvent($parcel->id, $mapped, $note);
            return response()->json(['message' => 'Already at status'], 200);
        }

        try {
            $parcel->status = $mapped;
            // Cancellation logging is handled by the model's `updated` hook —
            // setting cancellationReason routes the webhook description into
            // the auto-created timeline event note.
            if ($mapped === ParcelStatus::CANCELLED) {
                $parcel->cancellationReason = $note;
            }
            $parcel->save();
        } catch (\Throwable $e) {
            Log::warning('Zajel webhook status update failed', [
                'parcel_id' => $parcel->id, 'error' => $e->getMessage(),
            ]);
        }

        // Manually log non-cancel status changes (cancel is auto-logged by the model hook).
        if ($mapped !== ParcelStatus::CANCELLED) {
            $this->logEvent($parcel->id, $mapped, $note);
        }

        return response()->json(['message' => 'Updated'], 200);
    }

    /** Map a Zajel webhook status to a local ParcelStatus, or null = log-only. */
    private function mapStatus(string $zajelStatus): ?int
    {
        return match (strtolower($zajelStatus)) {
            'outfordelivery'   => ParcelStatus::DELIVERY_MAN_ASSIGN,
            'cancelled'        => ParcelStatus::CANCELLED,
            'delivered'        => ParcelStatus::DELIVERED,
            'inscan_at_hub',
            'reachedathub'     => ParcelStatus::RECEIVED_BY_HUB,
            'pickup_awaited'   => ParcelStatus::PICKUP_ASSIGN,
            'pickup_completed' => ParcelStatus::RECEIVED_BY_PICKUP_MAN,
            'rto'              => ParcelStatus::RETURN_TO_COURIER,
            'rto_delivered'    => ParcelStatus::RETURN_RECEIVED_BY_MERCHANT,
            // attempted / on_hold / rto_attempted / softdata_* → log-only
            default            => null,
        };
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
            Log::warning('Zajel webhook event-log failed', [
                'parcel_id' => $parcelId,
                'error'     => $ex->getMessage(),
            ]);
        }
    }
}
