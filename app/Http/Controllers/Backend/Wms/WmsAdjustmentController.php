<?php

namespace App\Http\Controllers\Backend\Wms;

use App\Enums\Wms\AdjustmentReason;
use App\Http\Controllers\Backend\Wms\Concerns\RendersInertiaIndex;
use App\Http\Controllers\Controller;
use App\Models\Backend\Wms\WmsLocation;
use App\Models\Backend\Wms\WmsProduct;
use App\Models\Backend\Wms\WmsStock;
use App\Repositories\Wms\WmsAdjustmentRepositoryInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WmsAdjustmentController extends Controller
{
    use RendersInertiaIndex;

    public function __construct(protected WmsAdjustmentRepositoryInterface $repo) {}

    public function index(Request $request)
    {
        $paginator = $this->repo->all($request);
        $reasons   = $this->reasonOptions();

        $rows = collect($paginator->items())->map(fn ($a) => [
            'id'              => $a->id,
            'sku'             => optional($a->product)->sku,
            'product_name'    => optional($a->product)->name,
            'location_code'   => optional($a->location)->code,
            'before'          => (int) $a->quantity_before,
            'change'          => (int) $a->quantity_change,
            'after'           => (int) $a->quantity_after,
            'reason'          => $a->reason,
            'reason_label'    => $reasons[$a->reason] ?? $a->reason,
            'approval_status' => $a->approval_status,
            'created_by'      => optional($a->createdBy)->name,
            'created_at'      => optional($a->created_at)->diffForHumans(),
            'url'             => route('wms.adjustments.show', $a->id),
        ])->values();

        return Inertia::render('Admin/Wms/Adjustments/Index', [
            'rows'        => $rows,
            'pagination'  => $this->paginateMeta($paginator),
            'filters'     => [
                'reason' => $request->input('reason', ''),
                'status' => $request->input('status', ''),
            ],
            'lookups'     => [
                'reasons'  => collect($reasons)->map(fn ($l, $k) => ['value' => $k, 'label' => $l])->values(),
                'statuses' => ['approved', 'pending_approval', 'rejected'],
            ],
            'permissions' => ['create' => hasPermission('wms_manage')],
            'urls' => [
                'index'  => route('wms.adjustments.index'),
                'create' => route('wms.adjustments.create'),
            ],
            't' => $this->indexLabels([
                'title' => 'Adjustments', 'sku' => 'SKU', 'product' => 'Product', 'location' => 'Location',
                'before' => 'Before', 'change' => 'Change', 'after' => 'After',
                'reason' => 'Reason', 'approval' => 'Approval', 'by' => 'By', 'when' => 'When',
            ]),
        ]);
    }

    public function create(Request $request)
    {
        $products  = WmsProduct::companywise()->where('is_active', true)->orderBy('name')
            ->get(['id', 'sku', 'name'])->map(fn ($p) => [
                'id' => $p->id, 'sku' => $p->sku, 'name' => $p->name,
            ])->all();
        $locations = WmsLocation::companywise()->where('is_active', true)->orderBy('code')
            ->get(['id', 'code'])->map(fn ($l) => [
                'id' => $l->id, 'code' => $l->code,
            ])->all();
        $reasons     = $this->reasonOptions();
        $preProduct  = $request->input('product_id');
        $preLocation = $request->input('location_id');
        $currentQty  = null;
        if ($preProduct && $preLocation) {
            $row = WmsStock::companywise()->where('product_id', $preProduct)->where('location_id', $preLocation)->first();
            $currentQty = $row?->quantity ?? 0;
        }

        return \Inertia\Inertia::render('Admin/Wms/Adjustments/Create', [
            'lookups' => [
                'products'  => $products,
                'locations' => $locations,
                'reasons'   => $reasons,
            ],
            'pre' => [
                'product_id'  => $preProduct ? (int) $preProduct : null,
                'location_id' => $preLocation ? (int) $preLocation : null,
                'current_qty' => $currentQty,
            ],
            'urls' => [
                'submit'     => route('wms.adjustments.store'),
                'cancel'     => route('wms.adjustments.index'),
                'lookup_qty' => route('wms.adjustments.lookup-qty'),
            ],
            't' => [
                'title'         => 'New adjustment',
                'title_index'   => 'Adjustments',
                'product'       => 'Product',
                'location'      => 'Location',
                'current_qty'   => 'Current qty',
                'quantity_after'=> 'New qty (after)',
                'change'        => 'Change',
                'reason'        => 'Reason',
                'reference'     => 'Reference',
                'reference_hint'=> 'Linked GRN / PO / ticket (optional)',
                'notes'         => 'Notes',
                'photo'         => 'Photo evidence',
                'photo_hint'    => 'JPEG/PNG, max 5 MB',
                'optional'      => 'optional',
                'cancel'        => __('levels.cancel') ?: 'Cancel',
                'save'          => 'Submit adjustment',
                'approval_hint' => 'Changes of ±20% or more require a supervisor approval before stock is updated.',
                'select_pair'   => 'Pick a product + location to see the current quantity.',
            ],
        ]);
    }

    /** Tiny JSON endpoint the React form uses to fetch current qty on pick. */
    public function lookupQty(Request $request)
    {
        $p = (int) $request->input('product_id');
        $l = (int) $request->input('location_id');
        if (!$p || !$l) return response()->json(['quantity' => null]);
        $row = WmsStock::companywise()->where('product_id', $p)->where('location_id', $l)->first();
        return response()->json(['quantity' => $row?->quantity ?? 0]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id'     => ['required', 'integer', 'exists:wms_products,id'],
            'location_id'    => ['required', 'integer', 'exists:wms_locations,id'],
            'quantity_after' => ['required', 'integer', 'min:0'],
            'reason'         => ['required', 'string'],
            'reference'      => ['nullable', 'string', 'max:191'],
            'notes'          => ['nullable', 'string'],
            'photo'          => ['nullable', 'image', 'max:5120'],
        ]);
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $name = date('YmdHis') . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/wms/adjustments/'), $name);
            $data['photo'] = 'uploads/wms/adjustments/' . $name;
        }

        $adj = $this->repo->submit($data);

        if ($adj->approval_status === 'pending_approval') {
            Toastr::warning(__('Large change (≥20%) — a second supervisor must approve before stock is updated.'));
        } else {
            Toastr::success(__('Adjustment recorded. Stock updated by :d.', ['d' => $adj->quantity_change]));
        }
        return redirect()->route('wms.adjustments.show', $adj->id);
    }

    public function show(int $id)
    {
        $adjustment = $this->repo->find($id);
        if (!$adjustment) return redirect()->route('wms.adjustments.index');
        return view('backend.wms.adjustments.show', compact('adjustment'));
    }

    public function approve(int $id)
    {
        $adjustment = $this->repo->find($id);
        if (!$adjustment) return redirect()->route('wms.adjustments.index');

        try {
            $this->repo->approve($adjustment, Auth::id());
            Toastr::success(__('Approved. Stock updated.'));
        } catch (\Throwable $e) {
            Toastr::error($e->getMessage());
        }
        return redirect()->route('wms.adjustments.show', $adjustment->id);
    }

    public function reject(Request $request, int $id)
    {
        $adjustment = $this->repo->find($id);
        if (!$adjustment) return redirect()->route('wms.adjustments.index');
        $this->repo->reject($adjustment, Auth::id(), $request->input('note'));
        Toastr::success(__('Rejected.'));
        return redirect()->route('wms.adjustments.show', $adjustment->id);
    }

    protected function reasonOptions(): array
    {
        $rc = new \ReflectionClass(AdjustmentReason::class);
        return array_values($rc->getConstants());
    }
}
