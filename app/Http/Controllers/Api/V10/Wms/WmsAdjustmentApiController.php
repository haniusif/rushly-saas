<?php

namespace App\Http\Controllers\Api\V10\Wms;

use App\Http\Controllers\Controller;
use App\Repositories\Wms\WmsAdjustmentRepositoryInterface;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WmsAdjustmentApiController extends Controller
{
    use ApiReturnFormatTrait;

    public function __construct(protected WmsAdjustmentRepositoryInterface $repo) {}

    /**
     * POST /api/v10/wms/adjustments
     * Body: { product_id, location_id, quantity_after, reason, reference?, notes? }
     * Returns 201 created; if ≥20% delta the adjustment is `pending_approval` and
     * stock is NOT changed until a second supervisor approves via the web UI.
     */
    public function store(Request $request)
    {
        $v = Validator::make($request->all(), [
            'product_id'     => ['required', 'integer', 'exists:wms_products,id'],
            'location_id'    => ['required', 'integer', 'exists:wms_locations,id'],
            'quantity_after' => ['required', 'integer', 'min:0'],
            'reason'         => ['required', 'string'],
            'reference'      => ['nullable', 'string', 'max:191'],
            'notes'          => ['nullable', 'string'],
        ]);
        if ($v->fails()) return $this->responseWithError('Validation failed', ['errors' => $v->errors()], 422);

        try {
            $adj = $this->repo->submit($v->validated());
            return $this->responseWithSuccess('Adjustment submitted', [
                'id'              => $adj->id,
                'quantity_before' => $adj->quantity_before,
                'quantity_after'  => $adj->quantity_after,
                'quantity_change' => $adj->quantity_change,
                'percent_change'  => $adj->percent_change,
                'approval_status' => $adj->approval_status,
                'requires_approval' => $adj->approval_status === 'pending_approval',
            ], 201);
        } catch (\Throwable $e) {
            return $this->responseWithError('Could not submit', ['error' => $e->getMessage()], 500);
        }
    }
}
