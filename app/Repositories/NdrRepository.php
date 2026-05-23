<?php

namespace App\Repositories;

use App\Enums\NdrAction;
use App\Enums\NdrStatus;
use App\Enums\ParcelStatus;
use App\Models\Backend\Ndr;
use App\Repositories\Parcel\ParcelInterface;
use App\Services\FollowupNotificationDispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NdrRepository implements NdrRepositoryInterface
{
    protected ParcelInterface $parcelRepo;
    protected AbnormalShipmentRepositoryInterface $abnormalRepo;
    protected FollowupNotificationDispatcher $notify;

    public function __construct(
        ParcelInterface $parcelRepo,
        AbnormalShipmentRepositoryInterface $abnormalRepo,
        FollowupNotificationDispatcher $notify
    ) {
        $this->parcelRepo    = $parcelRepo;
        $this->abnormalRepo  = $abnormalRepo;
        $this->notify        = $notify;
    }

    public function all(Request $request = null)
    {
        $q = Ndr::companywise()->with(['parcel', 'deliveryman', 'createdBy', 'resolvedBy']);

        if ($request) {
            if ($request->filled('status'))         $q->where('status', $request->input('status'));
            if ($request->filled('failure_reason')) $q->where('failure_reason', $request->input('failure_reason'));
            if ($request->filled('deliveryman_id')) $q->where('deliveryman_id', $request->input('deliveryman_id'));
            if ($request->filled('date_from'))      $q->whereDate('created_at', '>=', $request->input('date_from'));
            if ($request->filled('date_to'))        $q->whereDate('created_at', '<=', $request->input('date_to'));
        }

        return $q->latest('id')->paginate(25);
    }

    public function find(int $id): ?Ndr
    {
        return Ndr::companywise()->with(['parcel.merchant', 'deliveryman', 'createdBy', 'resolvedBy'])->find($id);
    }

    public function create(array $data): Ndr
    {
        $data['company_id']     = settings()->id;
        $data['created_by']     = $data['created_by'] ?? (Auth::id() ?? null);
        $data['attempt_number'] = $data['attempt_number'] ?? 1;
        $data['status']         = $data['status'] ?? NdrStatus::OPEN;

        $ndr = Ndr::create($data);

        // Cross-module: link to any open AbnormalShipment for this parcel.
        $openAbn = \App\Models\Backend\AbnormalShipment::companywise()
            ->where('parcel_id', $ndr->parcel_id)
            ->whereNotIn('status', ['resolved', 'closed_lost'])
            ->first();
        if ($openAbn) {
            $ndr->abnormal_shipment_id = $openAbn->id;
            $ndr->save();
        }

        // Business rule: after attempt 3 → auto-RETURN + parcel goes to RETURN_TO_COURIER.
        if ((int) $ndr->attempt_number >= 3) {
            $ndr->status = NdrStatus::RETURNED;
            $ndr->save();
            $this->parcelRepo->statusUpdate($ndr->parcel_id, ParcelStatus::RETURN_TO_COURIER);
            $this->notify->ndrAttemptThree($ndr);
        } else {
            // Flag the parcel as NDR_CREATED so list views can show the badge without joining.
            $this->parcelRepo->statusUpdate($ndr->parcel_id, ParcelStatus::NDR_CREATED);
            $this->notify->ndrCreated($ndr);
        }

        return $ndr;
    }

    public function applyAction(Ndr $ndr, string $action, Request $request): bool
    {
        $ndr->action_taken = $action;
        $ndr->status       = NdrStatus::IN_PROGRESS;

        switch ($action) {
            case NdrAction::RESCHEDULE:
                if ($request->filled('next_attempt_date')) {
                    $ndr->next_attempt_date = $request->input('next_attempt_date');
                }
                break;

            case NdrAction::RETURN_TO_MERCHANT:
                $this->parcelRepo->returnAssignToMerchant($ndr->parcel_id, $request);
                $ndr->status = NdrStatus::RETURNED;
                break;

            case NdrAction::TRANSFER_HUB:
                if ($request->filled('hub_id')) {
                    $this->parcelRepo->transfertohub($ndr->parcel_id, $request);
                }
                break;

            case NdrAction::ESCALATE:
                // Escalation just marks state; downstream notification handled in Phase 6.
                break;
        }

        return (bool) $ndr->save();
    }

    public function resolve(Ndr $ndr, int $resolvedByUserId): bool
    {
        $ndr->status      = NdrStatus::RESOLVED;
        $ndr->resolved_by = $resolvedByUserId;
        $ndr->resolved_at = now();
        $saved = (bool) $ndr->save();

        // Cross-module auto-resolve: closing an NDR closes the parcel's open AbnormalShipment.
        if ($saved) {
            $this->abnormalRepo->autoResolveByParcel((int) $ndr->parcel_id);
        }

        return $saved;
    }

    public function todayOpenForParcel(int $parcelId): ?Ndr
    {
        return Ndr::companywise()
            ->where('parcel_id', $parcelId)
            ->whereDate('created_at', today())
            ->whereIn('status', [NdrStatus::OPEN, NdrStatus::IN_PROGRESS])
            ->first();
    }

    public function stats(): array
    {
        $base = Ndr::companywise();
        return [
            'today'        => (clone $base)->whereDate('created_at', today())->count(),
            'open'         => (clone $base)->where('status', NdrStatus::OPEN)->count(),
            'in_progress'  => (clone $base)->where('status', NdrStatus::IN_PROGRESS)->count(),
            'resolved'     => (clone $base)->where('status', NdrStatus::RESOLVED)->count(),
            'returned'     => (clone $base)->where('status', NdrStatus::RETURNED)->count(),
            'return_rate'  => $this->returnRate(),
        ];
    }

    protected function returnRate(): float
    {
        $total    = Ndr::companywise()->count();
        $returned = Ndr::companywise()->where('status', NdrStatus::RETURNED)->count();
        return $total > 0 ? round(($returned / $total) * 100, 1) : 0.0;
    }
}
