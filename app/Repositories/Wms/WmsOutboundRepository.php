<?php

namespace App\Repositories\Wms;

use App\Enums\Wms\AdjustmentReason;
use App\Enums\Wms\OutboundType;
use App\Models\Backend\Wms\WmsOutbound;
use App\Models\Backend\Wms\WmsOutboundItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WmsOutboundRepository implements WmsOutboundRepositoryInterface
{
    public function __construct(protected WmsStockRepositoryInterface $stock) {}

    public function all(?Request $request = null)
    {
        $q = WmsOutbound::companywise()->with(['hub', 'merchant', 'processedBy']);
        if ($request) {
            if ($request->filled('type'))        $q->where('type', $request->input('type'));
            if ($request->filled('status'))      $q->where('status', $request->input('status'));
            if ($request->filled('merchant_id')) $q->where('merchant_id', $request->input('merchant_id'));
        }
        return $q->latest('id')->paginate(25);
    }

    public function find(int $id): ?WmsOutbound
    {
        return WmsOutbound::companywise()->with(['hub', 'merchant', 'processedBy', 'items.product', 'items.location'])->find($id);
    }

    public function create(array $data, array $items): WmsOutbound
    {
        return DB::transaction(function () use ($data, $items) {
            $data['company_id']      = settings()->id;
            $data['processed_by']    = $data['processed_by'] ?? Auth::id();
            $data['outbound_number'] = $data['outbound_number'] ?? $this->nextOutboundNumber();
            $data['status']          = $data['status'] ?? 'pending';
            $data['type']            = $data['type'] ?? OutboundType::MANUAL;

            $o = WmsOutbound::create($data);
            foreach ($items as $i) {
                WmsOutboundItem::create([
                    'outbound_id'  => $o->id,
                    'product_id'   => $i['product_id'],
                    'location_id'  => $i['location_id'],
                    'quantity'     => (int) $i['quantity'],
                    'batch_number' => $i['batch_number'] ?? null,
                ]);
            }
            return $o->load('items');
        });
    }

    public function complete(WmsOutbound $o): bool
    {
        return DB::transaction(function () use ($o) {
            $o->load('items');
            foreach ($o->items as $it) {
                $this->stock->adjustStock(
                    $it->product_id,
                    $it->location_id,
                    -(int) $it->quantity,
                    'FEFO',
                    [
                        'reason'    => AdjustmentReason::OTHER,
                        'reference' => 'OUT ' . $o->outbound_number,
                        'notes'     => 'Outbound: ' . $o->type,
                        'user_id'   => Auth::id() ?? $o->processed_by,
                    ]
                );
            }
            $o->status       = 'completed';
            $o->completed_at = now();
            return (bool) $o->save();
        });
    }

    public function nextOutboundNumber(): string
    {
        $year = date('Y');
        $next = WmsOutbound::companywise()->whereYear('created_at', $year)->count() + 1;
        return sprintf('OUT-%s-%05d', $year, $next);
    }
}
