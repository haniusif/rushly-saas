<?php

namespace App\Http\Controllers\Backend\Wms;

use App\Enums\Wms\GrnStatus;
use App\Http\Controllers\Controller;
use App\Models\Backend\Wms\WmsLocation;
use App\Models\Backend\Wms\WmsProduct;
use App\Repositories\Hub\HubInterface;
use App\Repositories\Merchant\MerchantInterface;
use App\Repositories\Wms\WmsGrnRepositoryInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WmsGrnController extends Controller
{
    public function __construct(
        protected WmsGrnRepositoryInterface $repo,
        protected MerchantInterface $merchantRepo,
        protected HubInterface $hubRepo
    ) {}

    public function index(Request $request)
    {
        $grns      = $this->repo->all($request);
        $merchants = $this->merchantRepo->all();
        $hubs      = $this->hubRepo->all();
        return view('backend.wms.grn.index', compact('grns', 'merchants', 'hubs'));
    }

    public function create(Request $request)
    {
        $merchants = $this->merchantRepo->all();
        $hubs      = $this->hubRepo->all();

        // Pre-build flat arrays for the row-template JS to avoid embedding closures in Blade.
        $productOptions = WmsProduct::companywise()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'sku', 'name', 'merchant_id', 'hub_id'])
            ->map(fn ($p) => [
                'id'          => $p->id,
                'sku'         => $p->sku,
                'name'        => $p->name,
                'merchant_id' => $p->merchant_id,
                'hub_id'      => $p->hub_id,
            ])->values()->all();

        $locationOptions = WmsLocation::companywise()
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'hub_id'])
            ->map(fn ($l) => ['id' => $l->id, 'code' => $l->code, 'hub_id' => $l->hub_id])
            ->values()->all();

        $nextNumber = $this->repo->nextGrnNumber();
        return view('backend.wms.grn.create', compact('merchants', 'hubs', 'productOptions', 'locationOptions', 'nextNumber'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'hub_id'           => ['required', 'integer', 'exists:hubs,id'],
            'merchant_id'      => ['required', 'integer', 'exists:merchants,id'],
            'reference_number' => ['nullable', 'string', 'max:191'],
            'notes'            => ['nullable', 'string'],
            'items'                       => ['required', 'array', 'min:1'],
            'items.*.product_id'          => ['required', 'integer', 'exists:wms_products,id'],
            'items.*.location_id'         => ['required', 'integer', 'exists:wms_locations,id'],
            'items.*.expected_qty'        => ['required', 'integer', 'min:1'],
            'items.*.received_qty'        => ['required', 'integer', 'min:0'],
            'items.*.batch_number'        => ['nullable', 'string', 'max:191'],
            'items.*.expiry_date'         => ['nullable', 'date'],
            'items.*.condition'           => ['required', 'string', 'in:good,damaged,expired'],
            'items.*.notes'               => ['nullable', 'string'],
        ]);

        $grn = $this->repo->create([
            'hub_id'           => $data['hub_id'],
            'merchant_id'      => $data['merchant_id'],
            'reference_number' => $data['reference_number'] ?? null,
            'notes'            => $data['notes'] ?? null,
            'received_by'      => Auth::id(),
            'status'           => GrnStatus::DRAFT,
        ], $data['items']);

        Toastr::success(__('GRN :n created in draft. Click Complete when receiving is done.', ['n' => $grn->grn_number]));
        return redirect()->route('wms.grn.show', $grn->id);
    }

    public function show(int $id)
    {
        $grn = $this->repo->find($id);
        if (!$grn) {
            Toastr::error(__('GRN not found.'));
            return redirect()->route('wms.grn.index');
        }
        return view('backend.wms.grn.show', compact('grn'));
    }

    public function edit(int $id)
    {
        // Editing a GRN line item is intentionally not supported once created — the
        // workflow is: create as draft, adjust received_qty on the show page (future
        // enhancement), then click Complete. Redirect to show.
        return redirect()->route('wms.grn.show', $id);
    }

    public function update(Request $request, int $id)
    {
        // Same as edit — no-op for now.
        return redirect()->route('wms.grn.show', $id);
    }

    public function complete(int $id)
    {
        $grn = $this->repo->find($id);
        if (!$grn) return redirect()->route('wms.grn.index');

        if (in_array($grn->status, [GrnStatus::COMPLETED, GrnStatus::DISCREPANCY], true)) {
            Toastr::info(__('This GRN is already finalised.'));
            return redirect()->route('wms.grn.show', $grn->id);
        }

        if ($this->repo->complete($grn)) {
            $grn->refresh();
            if ($grn->status === GrnStatus::DISCREPANCY) {
                Toastr::warning(__('GRN completed with discrepancies — review highlighted items.'));
            } else {
                Toastr::success(__('GRN completed. Stock credited.'));
            }
        } else {
            Toastr::error(__('Could not complete GRN.'));
        }
        return redirect()->route('wms.grn.show', $grn->id);
    }

    public function destroy(int $id)
    {
        $grn = $this->repo->find($id);
        if (!$grn) return redirect()->route('wms.grn.index');
        if (in_array($grn->status, [GrnStatus::COMPLETED, GrnStatus::DISCREPANCY], true)) {
            Toastr::error(__('Cannot delete a completed GRN.'));
            return redirect()->route('wms.grn.show', $grn->id);
        }
        $this->repo->delete($grn);
        Toastr::success(__('GRN deleted.'));
        return redirect()->route('wms.grn.index');
    }
}
