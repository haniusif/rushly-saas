<?php

namespace App\Services;

use App\Models\Backend\AbnormalShipment;
use App\Models\Backend\Ndr;
use App\Models\Backend\NotificationSettings;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Wraps the existing SMS (SmsSendSettingHelper) + FCM push pipelines so the
 * NDR + Abnormal modules don't each have to re-implement the matrix.
 *
 * Channels are best-effort: helpers may be no-ops in dev. Every dispatch is
 * logged so issues surface in the activity log + storage/logs.
 */
class FollowupNotificationDispatcher
{
    /** Event: NDR created (attempts 1–2) → supervisor push. */
    public function ndrCreated(Ndr $ndr): void
    {
        $this->push(
            $this->supervisors(),
            __('NDR raised'),
            __('Parcel :tid attempt :n — :reason', [
                'tid'    => optional($ndr->parcel)->tracking_id ?? ('#'.$ndr->parcel_id),
                'n'      => $ndr->attempt_number,
                'reason' => ucwords(str_replace('_', ' ', $ndr->failure_reason)),
            ])
        );
    }

    /** Event: NDR hit attempt 3 → admin + merchant push + SMS. */
    public function ndrAttemptThree(Ndr $ndr): void
    {
        $title = __('Parcel returning');
        $body  = __('Parcel :tid hit 3 NDR attempts; auto-marked for return to courier.', [
            'tid' => optional($ndr->parcel)->tracking_id ?? ('#'.$ndr->parcel_id),
        ]);
        $this->push($this->admins(),    $title, $body);
        $this->push($this->merchant($ndr), $title, $body);
        $this->sms($ndr->parcel?->customer_phone, $body, 'ndr_attempt_three');
        $this->sms($ndr->parcel?->merchant?->user?->mobile, $body, 'ndr_attempt_three');
    }

    /** Event: Abnormal detected (threshold reached). */
    public function abnormalDetected(AbnormalShipment $a): void
    {
        $this->push(
            $this->supervisors(),
            __('Abnormal shipment'),
            __('Parcel :tid stale for :d days.', [
                'tid' => optional($a->parcel)->tracking_id ?? ('#'.$a->parcel_id),
                'd'   => $a->stale_days,
            ])
        );
    }

    /** Event: Abnormal escalated to critical → admins + email-style log. */
    public function abnormalCritical(AbnormalShipment $a): void
    {
        $title = __('Critical abnormal shipment');
        $body  = __('Parcel :tid stale :d days — please investigate immediately.', [
            'tid' => optional($a->parcel)->tracking_id ?? ('#'.$a->parcel_id),
            'd'   => $a->stale_days,
        ]);
        $this->push($this->admins(), $title, $body);
    }

    /** Event: Daily 8 AM digest to supervisors. */
    public function dailyDigest(int $companyId, array $counts): void
    {
        $this->push(
            $this->supervisorsForCompany($companyId),
            __('Daily follow-up digest'),
            __('Open NDRs: :n · Critical abnormals: :c', [
                'n' => $counts['open_ndrs'] ?? 0,
                'c' => $counts['critical_abnormals'] ?? 0,
            ])
        );
    }

    /** Event: Shipment closed as lost → admin + merchant push + SMS. */
    public function closedAsLost(AbnormalShipment $a): void
    {
        $title = __('Shipment closed as lost');
        $body  = __('Parcel :tid has been closed as lost.', [
            'tid' => optional($a->parcel)->tracking_id ?? ('#'.$a->parcel_id),
        ]);
        $this->push($this->admins(), $title, $body);
        $this->push($this->merchantForParcel($a->parcel_id), $title, $body);
        $this->sms($a->parcel?->merchant?->user?->mobile, $body, 'shipment_closed_lost');
    }

    // ===== Channels =====

    protected function push($recipients, string $title, string $body): void
    {
        if (empty($recipients)) return;
        try {
            $svc = class_exists(PushNotificationService::class) ? app(PushNotificationService::class) : null;
            foreach ($recipients as $u) {
                if ($svc && method_exists($svc, 'sendNotification')) {
                    $svc->sendNotification($u, $title, $body);
                } elseif ($svc && method_exists($svc, 'send')) {
                    $svc->send($u->fcm_token ?? null, $title, $body);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('followup.push failed', ['err' => $e->getMessage(), 'title' => $title]);
        }
        Log::info('followup.push', ['recipients' => collect($recipients)->pluck('id')->all(), 'title' => $title]);
    }

    protected function sms(?string $to, string $message, string $eventKey): void
    {
        if (!$to) return;
        try {
            if (!function_exists('SmsSendSettingHelper') || !SmsSendSettingHelper($eventKey)) {
                Log::info('followup.sms skipped (event disabled)', ['event' => $eventKey, 'to' => $to]);
                return;
            }
            $svc = class_exists(SmsService::class) ? app(SmsService::class) : null;
            if ($svc && method_exists($svc, 'sendSms')) {
                $svc->sendSms($to, $message);
            }
            Log::info('followup.sms sent', ['event' => $eventKey, 'to' => $to]);
        } catch (\Throwable $e) {
            Log::warning('followup.sms failed', ['err' => $e->getMessage(), 'event' => $eventKey]);
        }
    }

    // ===== Recipient resolution =====

    protected function supervisors(): array
    {
        return User::where('company_id', settings()->id)
            ->whereIn('user_type', [1])   // admin/supervisor user_types per Rushly enum
            ->get()->all();
    }

    protected function admins(): array
    {
        return User::where('company_id', settings()->id)
            ->where('company_owner', 1)
            ->get()->all();
    }

    protected function supervisorsForCompany(int $companyId): array
    {
        return User::where('company_id', $companyId)
            ->whereIn('user_type', [1])
            ->get()->all();
    }

    protected function merchant(Ndr $ndr): array
    {
        $u = $ndr->parcel?->merchant?->user;
        return $u ? [$u] : [];
    }

    protected function merchantForParcel(int $parcelId): array
    {
        $p = \App\Models\Backend\Parcel::with('merchant.user')->find($parcelId);
        $u = $p?->merchant?->user;
        return $u ? [$u] : [];
    }
}
