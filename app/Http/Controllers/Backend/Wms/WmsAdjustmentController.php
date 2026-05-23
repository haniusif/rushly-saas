<?php

namespace App\Http\Controllers\Backend\Wms;

use App\Enums\Wms\AdjustmentReason;
use App\Http\Controllers\Controller;
use App\Models\Backend\Wms\WmsLocation;
use App\Models\Backend\Wms\WmsProduct;
use App\Models\Backend\Wms\WmsStock;
use App\Repositories\Wms\WmsAdjustmentRepositoryInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WmsAdjustmentController extends Controller
{
    public function __construct(protected WmsAdjustmentRepositoryInterface $repo) {}

    public function index(Request $request)
    {
        $adjustments = $this->repo->all($request);
        $reasons     = $this->reasonOptions();
        return view('backend.wms.adjustments.index', compact('adjustments', 'reasons'));
    }

    public function create(Request $request)
    {
        // Pre-fill product+location from query string (e.g. linked from stock page).
        $products  = WmsProduct::companywise()->where('is_active', true)->orderBy('name')->get();
        $locations = WmsLocation::companywise()->where('is_active', true)->orderBy('code')->get();
        $reasons   = $this->reasonOptions();
        $preProduct  = $request->input('product_id');
        $preLocation = $request->input('location_id');
        $currentQty  = null;
        if ($preProduct && $preLocation) {
            $row = WmsStock::companywise()->where('product_id', $preProduct)->where('location_id', $preLocation)->first();
            $currentQty = $row?->quantity ?? 0;
        }
        return view('backend.wms.adjustments.create', compact('products', 'locations', 'reasons', 'preProduct', 'preLocation', 'currentQty'));
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
