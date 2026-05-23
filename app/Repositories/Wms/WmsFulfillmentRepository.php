<?php

namespace App\Repositories\Wms;

use App\Enums\ParcelStatus;
use App\Enums\Wms\AdjustmentReason;
use App\Enums\Wms\FulfillmentStatus;
use App\Models\Backend\ParcelEvent;
use App\Models\Backend\Wms\WmsFulfillment;
use App\Models\Backend\Wms\WmsFulfillmentItem;
use App\Repositories\Parcel\ParcelInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WmsFulfillmentRepository implements WmsFulfillmentRepositoryInterface
{
    public function __construct(
        protected WmsStockRepositoryInterface $stock,
        protected ParcelInterface $parcelRepo
    ) {}

    public function all(?Request $request = null)
    {
        $q = WmsFulfillment::companywise()->with(['parcel', 'hub', 'merchant', 'picker', 'packer']);
        if ($request) {
            if ($request->filled('status'))      $q->where('status', $request->input('status'));
            if ($request->filled('hub_id'))      $q->where('hub_id', $request->input('hub_id'));
            if ($request->filled('merchant_id')) $q->where('merchant_id', $request->input('merchant_id'));
            if ($request->filled('picker_id'))   $q->where('picker_id', $request->input('picker_id'));
            if ($request->boolean('sla_breached')) {
                $q->whereNotIn('status', ['dispatched', 'cancelled'])
                  ->whereNotNull('sla_deadline')
                  ->where('sla_deadline', '<', now());
            }
        }
        return $q->latest('id')->paginate(25);
    }

    public function find(int $id): ?WmsFulfillment
    {
        return WmsFulfillment::companywise()
            ->with(['parcel.merchant', 'hub', 'merchant', 'picker', 'packer', 'items.product', 'items.location'])
            ->find($id);
    }

    public function create(array $data, array $items): WmsFulfillment
    {
        return DB::transaction(function () use ($data, $items) {
            $data['company_id']         = settings()->id;
            $data['fulfillment_number'] = $data['fulfillment_number'] ?? $this->nextFulfillmentNumber();
            $data['status']             = $data['status'] ?? FulfillmentStatus::PENDING;
            $data['sla_deadline']       = $data['sla_deadline'] ?? now()->addHours((int) $this->slaHours());

            $f = WmsFulfillment::create($data);

            foreach ($items as $i) {
                WmsFulfillmentItem::create([
                    'fulfillment_id'    => $f->id,
                    'product_id'        => $i['product_id'],
                    'location_id'       => $i['location_id'],
                    'quantity_required' => (int) $i['quantity_required'],
                    'quantity_picked'   => 0,
                    'status'            => 'pending',
                ]);
            }

            // Transition the linked parcel.
            $this->parcelRepo->statusUpdate($f->parcel_id, ParcelStatus::WMS_FULFILLMENT_PENDING);
            $this->logParcelEvent($f, ParcelStatus::WMS_FULFILLMENT_PENDING);

            // Link FK for fast joins.
            \App\Models\Backend\Parcel::where('id', $f->parcel_id)->update(['wms_fulfillment_id' => $f->id]);

            return $f->load('items');
        });
    }

    public function confirmPick(WmsFulfillment $f, int $userId, array $picks): bool
    {
        return DB::transaction(function () use ($f, $userId, $picks) {
            // First time entering picking phase → reserve stock.
            if ($f->status === FulfillmentStatus::PENDING) {
                foreach ($f->items as $it) {
                    try {
                        $this->stock->reserve($it->product_id, $it->location_id, $it->quantity_required);
                    } catch (\Throwable $e) { /* short pick — handled below */ }
                }
                $f->status     = FulfillmentStatus::PICKING;
                $f->picker_id  = $userId;
                $this->parcelRepo->statusUpdate($f->parcel_id, ParcelStatus::WMS_PICKING);
                $this->logParcelEvent($f, ParcelStatus::WMS_PICKING);
            }

            // Apply each pick to the matching item.
            foreach ($picks as $itemId => $qtyPicked) {
                $item = WmsFulfillmentItem::find($itemId);
                if (!$item || $item->fulfillment_id !== $f->id) continue;
                $item->quantity_picked = min((int) $qtyPicked, $item->quantity_required);
                $item->status = ($item->quantity_picked >= $item->quantity_required) ? 'picked' : 'short';
                $item->save();
            }

            // All items fully picked → move to PACKING.
            $allPicked = $f->items()->where('status', '!=', 'picked')->doesntExist();
            if ($allPicked) {
                $f->status    = FulfillmentStatus::PACKING;
                $f->picked_at = now();
                $this->parcelRepo->statusUpdate($f->parcel_id, ParcelStatus::WMS_PACKING);
                $this->logParcelEvent($f, ParcelStatus::WMS_PACKING);
            }

            return (bool) $f->save();
        });
    }

    public function confirmPack(WmsFulfillment $f, int $userId): bool
    {
        $f->packer_id = $userId;
        $f->packed_at = now();
        $f->status    = FulfillmentStatus::READY;
        $saved = (bool) $f->save();

        $this->parcelRepo->statusUpdate($f->parcel_id, ParcelStatus::WMS_READY_TO_SHIP);
        $this->logParcelEvent($f, ParcelStatus::WMS_READY_TO_SHIP);
        return $saved;
    }

    public function dispatch(WmsFulfillment $f): bool
    {
        return DB::transaction(function () use ($f) {
            // Consume the reserved stock (debit + release).
            foreach ($f->items as $it) {
                $picked = (int) $it->quantity_picked;
                if ($picked <= 0) continue;

                // Release reservation first so debit can subtract from quantity cleanly.
                $this->stock->release($it->product_id, $it->location_id, $picked);
                $this->stock->adjustStock(
                    $it->product_id,
                    $it->location_id,
                    -$picked,
                    'FEFO',
                    [
                        'reason'    => AdjustmentReason::OTHER,
                        'reference' => 'FUL ' . $f->fulfillment_number,
                        'notes'     => 'Dispatched fulfillment',
                        'user_id'   => Auth::id() ?? $f->packer_id ?? 0,
                    ]
                );
            }

            $f->status        = FulfillmentStatus::DISPATCHED;
            $f->dispatched_at = now();
            $f->save();

            // Hand off to the courier workflow.
            $this->parcelRepo->statusUpdate($f->parcel_id, ParcelStatus::DELIVERY_MAN_ASSIGN);
            $this->logParcelEvent($f, ParcelStatus::DELIVERY_MAN_ASSIGN);
            return true;
        });
    }

    public function nextFulfillmentNumber(): string
    {
        $year = date('Y');
        $next = WmsFulfillment::companywise()->whereYear('created_at', $year)->count() + 1;
        return sprintf('FUL-%s-%05d', $year, $next);
    }

    public function breachedSla()
    {
        return WmsFulfillment::companywise()
            ->whereNotIn('status', [FulfillmentStatus::DISPATCHED, FulfillmentStatus::CANCELLED])
            ->whereNotNull('sla_deadline')
            ->where('sla_deadline', '<', now())
            ->get();
    }

    protected function slaHours(): int
    {
        $row = \App\Models\Config::where('company_id', settings()->id)
            ->where('key', 'wms_sla_hours')->first();
        return $row ? max(1, (int) $row->value) : 24;
    }

    protected function logParcelEvent(WmsFulfillment $f, int $parcelStatus): void
    {
        $e = new ParcelEvent();
        $e->parcel_id     = $f->parcel_id;
        $e->parcel_status = $parcelStatus;
        $e->note          = 'WMS: ' . $f->fulfillment_number;
        $e->created_by    = Auth::id() ?? $f->picker_id ?? $f->packer_id ?? 0;
        $e->save();
    }
}
