<?php

namespace App\Http\Controllers\Api\V10\Wms;

use App\Enums\Wms\GrnStatus;
use App\Http\Controllers\Controller;
use App\Models\Backend\Wms\WmsGrn;
use App\Models\Backend\Wms\WmsGrnItem;
use App\Repositories\Wms\WmsGrnRepositoryInterface;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WmsGrnApiController extends Controller
{
    use ApiReturnFormatTrait;

    public function __construct(protected WmsGrnRepositoryInterface $repo) {}

    /**
     * POST /api/v10/wms/grn/{grn}/scan
     * Mobile receiver scans an item barcode and reports the received qty.
     * Body: { product_id, location_id, received_qty, batch_number?, expiry_date?, condition? }
     * Either updates an existing GRN line for this product, or creates a new line.
     */
    public function scanItem(Request $request, int $grn)
    {
        $g = WmsGrn::companywise()->find($grn);
        if (!$g) return $this->responseWithError('GRN not found', [], 404);
        if (in_array($g->status, [GrnStatus::COMPLETED, GrnStatus::DISCREPANCY], true)) {
            return $this->responseWithError('GRN is already finalised', [], 409);
        }

        $v = Validator::make($request->all(), [
            'product_id'   => ['required','integer','exists:wms_products,id'],
            'location_id'  => ['required','integer','exists:wms_locations,id'],
            'received_qty' => ['required','integer','min:1'],
            'expected_qty' => ['nullable','integer','min:0'],
            'batch_number' => ['nullable','string','max:191'],
            'expiry_date'  => ['nullable','date'],
            'condition'    => ['nullable','string','in:good,damaged,expired'],
        ]);
        if ($v->fails()) return $this->responseWithError('Validation failed', ['errors' => $v->errors()], 422);
        $data = $v->validated();

        // Find an existing line for the same product+location+batch — or create one.
        $line = WmsGrnItem::where('grn_id', $g->id)
            ->where('product_id', $data['product_id'])
            ->where('location_id', $data['location_id'])
            ->where('batch_number', $data['batch_number'] ?? null)
            ->first();

        if ($line) {
            $line->received_qty = (int) $line->received_qty + (int) $data['received_qty'];
            if ($data['expected_qty'] ?? null) $line->expected_qty = (int) $data['expected_qty'];
            if ($data['condition'] ?? null)    $line->condition    = $data['condition'];
            if ($data['expiry_date'] ?? null)  $line->expiry_date  = $data['expiry_date'];
            $line->save();
        } else {
            $line = WmsGrnItem::create([
                'grn_id'       => $g->id,
                'product_id'   => $data['product_id'],
                'location_id'  => $data['location_id'],
                'expected_qty' => $data['expected_qty'] ?? $data['received_qty'],
                'received_qty' => $data['received_qty'],
                'batch_number' => $data['batch_number'] ?? null,
                'expiry_date'  => $data['expiry_date'] ?? null,
                'condition'    => $data['condition'] ?? 'good',
            ]);
        }

        // Move GRN out of draft on first scan.
        if ($g->status === GrnStatus::DRAFT) {
            $g->status = GrnStatus::IN_PROGRESS;
            $g->save();
        }

        return $this->responseWithSuccess('Item recorded', ['line' => $line, 'grn_status' => $g->status], 200);
    }

    /**
     * POST /api/v10/wms/grn/{grn}/complete
     * Finalises the GRN: credits stock + flags discrepancies.
     */
    public function complete(int $grn)
    {
        $g = WmsGrn::companywise()->find($grn);
        if (!$g) return $this->responseWithError('GRN not found', [], 404);
        if (in_array($g->status, [GrnStatus::COMPLETED, GrnStatus::DISCREPANCY], true)) {
            return $this->responseWithError('Already finalised', [], 409);
        }
        try {
            $this->repo->complete($g);
            $g->refresh();
            return $this->responseWithSuccess('GRN completed', [
                'grn_number' => $g->grn_number,
                'status'     => $g->status,
                'discrepancy'=> $g->status === GrnStatus::DISCREPANCY,
            ], 200);
        } catch (\Throwable $e) {
            return $this->responseWithError('Could not complete', ['error' => $e->getMessage()], 500);
        }
    }
}
