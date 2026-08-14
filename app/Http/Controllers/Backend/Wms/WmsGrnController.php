<?php

namespace App\Http\Controllers\Backend\Wms;

use App\Enums\Wms\GrnStatus;
use App\Http\Controllers\Backend\Wms\Concerns\RendersInertiaIndex;
use App\Http\Controllers\Controller;
use App\Models\Backend\Wms\WmsLocation;
use App\Models\Backend\Wms\WmsProduct;
use App\Repositories\Hub\HubInterface;
use App\Repositories\Merchant\MerchantInterface;
use App\Repositories\Wms\WmsGrnRepositoryInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WmsGrnController extends Controller
{
    use RendersInertiaIndex;

    public function __construct(
        protected WmsGrnRepositoryInterface $repo,
        protected MerchantInterface $merchantRepo,
        protected HubInterface $hubRepo
    ) {}

    public function index(Request $request)
    {
        $paginator = $this->repo->all($request);
        $merchants = $this->merchantRepo->all();
        $hubs      = $this->hubRepo->all();

        $rows = collect($paginator->items())->map(fn ($g) => [
            'id'           => $g->id,
            'grn_number'   => $g->grn_number,
            'merchant'     => optional($g->merchant)->business_name,
            'hub'          => optional($g->hub)->name,
            'received_by'  => optional($g->receivedBy)->name,
            'items_count'  => (int) ($g->items_count ?? $g->items?->count() ?? 0),
            'status'       => $g->status,
            'status_label' => ucwords(str_replace('_', ' ', $g->status)),
            'created_at'   => optional($g->created_at)->diffForHumans(),
            'url'          => route('wms.grn.show', $g->id),
        ])->values();

        return Inertia::render('Admin/Wms/Grn/Index', [
            'rows'        => $rows,
            'pagination'  => $this->paginateMeta($paginator),
            'filters'     => [
                'status'      => $request->input('status', ''),
                'merchant_id' => $request->input('merchant_id', ''),
                'hub_id'      => $request->input('hub_id', ''),
            ],
            'lookups'     => [
                'statuses' => ['draft', 'in_progress', 'completed', 'discrepancy'],
                'merchants'=> $this->lookupRows($merchants, fn ($m) => ['id' => $m->id, 'name' => $m->business_name]),
                'hubs'     => $this->lookupRows($hubs, fn ($h) => ['id' => $h->id, 'name' => $h->name]),
            ],
            'permissions' => ['create' => hasPermission('wms_manage')],
            'urls' => [
                'index'  => route('wms.grn.index'),
                'create' => route('wms.grn.create'),
            ],
            't' => $this->indexLabels([
                'title' => 'Receiving (GRN)', 'grn_number' => 'GRN #',
                'merchant' => 'Merchant', 'hub' => 'Hub',
                'received_by' => 'Received by', 'items' => 'Items', 'created' => 'Created',
            ]),
        ]);
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

        return Inertia::render('Admin/Wms/Grn/Create', [
            'lookups' => [
                'merchants' => $this->lookupRows($merchants, fn ($m) => ['id' => $m->id, 'name' => $m->business_name]),
                'hubs'      => $this->lookupRows($hubs,      fn ($h) => ['id' => $h->id, 'name' => $h->name]),
                'products'  => $productOptions,
                'locations' => $locationOptions,
            ],
            'next_number' => $nextNumber,
            'urls' => [
                'submit' => route('wms.grn.store'),
                'cancel' => route('wms.grn.index'),
            ],
            't' => [
                'title'        => 'New GRN',
                'title_index'  => 'Receiving (GRN)',
                'grn_number'   => 'GRN number',
                'merchant'     => 'Merchant',
                'hub'          => 'Hub',
                'reference'    => 'Reference number',
                'reference_hint' => 'Supplier invoice / PO number (optional)',
                'notes'        => 'Notes',
                'items'        => 'Items',
                'add_item'     => 'Add item',
                'product'      => 'Product',
                'location'     => 'Location',
                'expected_qty' => 'Expected',
                'received_qty' => 'Received',
                'batch'        => 'Batch',
                'expiry'       => 'Expiry',
                'condition'    => 'Condition',
                'good'         => 'Good',
                'damaged'      => 'Damaged',
                'expired'      => 'Expired',
                'item_notes'   => 'Notes',
                'remove'       => 'Remove',
                'cancel'       => __('levels.cancel') ?: 'Cancel',
                'save'         => 'Save GRN (draft)',
                'no_items'     => 'Click "Add item" to start. At least one item is required.',
                'optional'     => 'optional',
                'select_merchant_first' => 'Select merchant first',
                'select_hub_first'      => 'Select hub first',
            ],
        ]);
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
