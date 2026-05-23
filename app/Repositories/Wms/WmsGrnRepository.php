<?php

namespace App\Repositories\Wms;

use App\Enums\Wms\AdjustmentReason;
use App\Enums\Wms\GrnStatus;
use App\Enums\Wms\ItemCondition;
use App\Models\Backend\Wms\WmsGrn;
use App\Models\Backend\Wms\WmsGrnItem;
use App\Models\Backend\Wms\WmsDamageReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WmsGrnRepository implements WmsGrnRepositoryInterface
{
    public function __construct(protected WmsStockRepositoryInterface $stock) {}

    public function all(?Request $request = null)
    {
        $q = WmsGrn::companywise()->with(['merchant', 'hub', 'receivedBy']);
        if ($request) {
            if ($request->filled('status'))     $q->where('status', $request->input('status'));
            if ($request->filled('merchant_id')) $q->where('merchant_id', $request->input('merchant_id'));
            if ($request->filled('hub_id'))      $q->where('hub_id', $request->input('hub_id'));
        }
        return $q->latest('id')->paginate(25);
    }

    public function find(int $id): ?WmsGrn
    {
        return WmsGrn::companywise()
            ->with(['merchant', 'hub', 'receivedBy', 'items.product', 'items.location'])
            ->find($id);
    }

    public function create(array $data, array $items): WmsGrn
    {
        return DB::transaction(function () use ($data, $items) {
            $data['company_id']  = settings()->id;
            $data['received_by'] = $data['received_by'] ?? Auth::id();
            $data['grn_number']  = $data['grn_number'] ?? $this->nextGrnNumber();
            $data['status']      = $data['status'] ?? GrnStatus::DRAFT;

            $grn = WmsGrn::create($data);

            foreach ($items as $i) {
                WmsGrnItem::create([
                    'grn_id'       => $grn->id,
                    'product_id'   => $i['product_id'],
                    'location_id'  => $i['location_id'],
                    'expected_qty' => (int) $i['expected_qty'],
                    'received_qty' => (int) ($i['received_qty'] ?? 0),
                    'batch_number' => $i['batch_number'] ?? null,
                    'expiry_date'  => $i['expiry_date'] ?? null,
                    'condition'    => $i['condition'] ?? ItemCondition::GOOD,
                    'notes'        => $i['notes'] ?? null,
                ]);
            }

            return $grn->load('items');
        });
    }

    public function complete(WmsGrn $grn): bool
    {
        return DB::transaction(function () use ($grn) {
            $grn->load('items');
            $hasDiscrepancy = false;

            foreach ($grn->items as $item) {
                if ((int) $item->expected_qty !== (int) $item->received_qty) {
                    $hasDiscrepancy = true;
                }

                // Damaged items at receiving → auto-create WmsDamageReport, do NOT credit.
                if ($item->condition === ItemCondition::DAMAGED && $item->received_qty > 0) {
                    WmsDamageReport::create([
                        'company_id'       => settings()->id,
                        'product_id'       => $item->product_id,
                        'location_id'      => $item->location_id,
                        'reported_by'      => $grn->received_by,
                        'quantity_damaged' => $item->received_qty,
                        'cause'            => 'transit_damage',
                        'notes'            => 'Auto-logged during GRN ' . $grn->grn_number,
                    ]);
                    continue;
                }
                if ($item->condition === ItemCondition::EXPIRED && $item->received_qty > 0) {
                    // Don't credit expired goods.
                    continue;
                }

                if ((int) $item->received_qty > 0) {
                    $this->stock->adjustStock(
                        (int) $item->product_id,
                        (int) $item->location_id,
                        (int) $item->received_qty,
                        'FIFO',
                        [
                            'batch_number' => $item->batch_number,
                            'expiry_date'  => $item->expiry_date,
                            'reason'       => AdjustmentReason::OTHER,
                            'reference'    => 'GRN ' . $grn->grn_number,
                            'notes'        => 'Goods receipt credit',
                            'user_id'      => $grn->received_by,
                        ]
                    );
                }
            }

            $grn->status      = $hasDiscrepancy ? GrnStatus::DISCREPANCY : GrnStatus::COMPLETED;
            $grn->received_at = now();
            $grn->save();

            return true;
        });
    }

    public function delete(WmsGrn $grn): bool
    {
        return (bool) $grn->delete();
    }

    public function nextGrnNumber(): string
    {
        $year = date('Y');
        $next = WmsGrn::companywise()->whereYear('created_at', $year)->count() + 1;
        return sprintf('GRN-%s-%05d', $year, $next);
    }
}
