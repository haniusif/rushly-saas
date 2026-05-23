<?php

namespace App\Http\Controllers\Backend\Wms;

use App\Http\Controllers\Controller;
use App\Models\Backend\Wms\WmsDamageReport;
use App\Models\Backend\Wms\WmsLocation;
use App\Models\Backend\Wms\WmsProduct;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WmsDamageController extends Controller
{
    public function index(Request $request)
    {
        $q = WmsDamageReport::companywise()->with(['product', 'location', 'reportedBy']);
        if ($request->filled('cause'))        $q->where('cause', $request->input('cause'));
        if ($request->filled('action_taken')) $q->where('action_taken', $request->input('action_taken'));
        $damages = $q->latest('id')->paginate(25);

        return view('backend.wms.damage.index', compact('damages'));
    }

    public function create()
    {
        $products  = WmsProduct::companywise()->where('is_active', true)->orderBy('name')->get();
        $locations = WmsLocation::companywise()->where('is_active', true)->orderBy('code')->get();
        return view('backend.wms.damage.create', compact('products', 'locations'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id'       => ['required', 'integer', 'exists:wms_products,id'],
            'location_id'      => ['required', 'integer', 'exists:wms_locations,id'],
            'quantity_damaged' => ['required', 'integer', 'min:1'],
            'cause'            => ['required', 'string', 'in:transit_damage,handling,water,expiry,unknown'],
            'notes'            => ['nullable', 'string'],
            'action_taken'     => ['nullable', 'string', 'in:written_off,returned_to_merchant,quarantine'],
            'photos.*'         => ['nullable', 'image', 'max:5120'],
        ]);

        $photoPaths = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $f) {
                $name = date('YmdHis') . uniqid() . '.' . $f->getClientOriginalExtension();
                $f->move(public_path('uploads/wms/damage/'), $name);
                $photoPaths[] = 'uploads/wms/damage/' . $name;
            }
        }

        $report = WmsDamageReport::create([
            'company_id'       => settings()->id,
            'product_id'       => $data['product_id'],
            'location_id'      => $data['location_id'],
            'reported_by'      => Auth::id(),
            'quantity_damaged' => $data['quantity_damaged'],
            'cause'            => $data['cause'],
            'photos'           => $photoPaths ?: null,
            'notes'            => $data['notes'] ?? null,
            'action_taken'     => $data['action_taken'] ?? null,
        ]);

        Toastr::success(__('Damage report logged.'));
        return redirect()->route('wms.damage.show', $report->id);
    }

    public function show(int $id)
    {
        $report = WmsDamageReport::companywise()->with(['product', 'location', 'reportedBy'])->find($id);
        if (!$report) return redirect()->route('wms.damage.index');
        return view('backend.wms.damage.show', compact('report'));
    }
}
