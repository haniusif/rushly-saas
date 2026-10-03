<?php

namespace App\Http\Controllers\Backend\Wms;

use App\Http\Controllers\Controller;
use App\Models\Backend\Wms\WmsDamageReport;
use App\Models\Backend\Wms\WmsLocation;
use App\Models\Backend\Wms\WmsProduct;
use App\Http\Controllers\Backend\Wms\Concerns\RendersInertiaIndex;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WmsDamageController extends Controller
{
    use RendersInertiaIndex;

    public function index(Request $request)
    {
        $q = WmsDamageReport::companywise()->with(['product', 'location', 'reportedBy']);
        if ($request->filled('cause'))        $q->where('cause', $request->input('cause'));
        if ($request->filled('action_taken')) $q->where('action_taken', $request->input('action_taken'));
        $paginator = $q->latest('id')->paginate(25);

        $rows = collect($paginator->items())->map(fn ($d) => [
            'id'             => $d->id,
            'sku'            => optional($d->product)->sku,
            'product_name'   => optional($d->product)->name,
            'location_code'  => optional($d->location)->code,
            'quantity'       => (int) $d->quantity_damaged,
            'cause'          => $d->cause,
            'action_taken'   => $d->action_taken,
            'reported_by'    => optional($d->reportedBy)->name,
            'created_at'     => optional($d->created_at)->diffForHumans(),
            'url'            => route('wms.damage.show', $d->id),
        ])->values();

        return Inertia::render('Admin/Wms/Damage/Index', [
            'rows'        => $rows,
            'pagination'  => $this->paginateMeta($paginator),
            'filters'     => [
                'cause'        => $request->input('cause', ''),
                'action_taken' => $request->input('action_taken', ''),
            ],
            'permissions' => ['create' => hasPermission('wms_manage')],
            'urls' => [
                'index'  => route('wms.damage.index'),
                'create' => route('wms.damage.create'),
            ],
            't' => $this->indexLabels(['title' => 'Damage reports', 'sku' => 'SKU', 'product' => 'Product', 'location' => 'Location', 'qty' => 'Qty', 'cause' => 'Cause', 'action' => 'Action', 'reported_by' => 'Reported by', 'when' => 'When']),
        ]);
    }

    public function create()
    {
        $products  = WmsProduct::companywise()->where('is_active', true)->orderBy('name')
            ->get(['id', 'sku', 'name'])->map(fn ($p) => [
                'id' => $p->id, 'sku' => $p->sku, 'name' => $p->name,
            ])->all();
        $locations = WmsLocation::companywise()->where('is_active', true)->orderBy('code')
            ->get(['id', 'code'])->map(fn ($l) => ['id' => $l->id, 'code' => $l->code])->all();

        return Inertia::render('Admin/Wms/Damage/Create', [
            'lookups' => [
                'products'  => $products,
                'locations' => $locations,
                'causes'    => [
                    ['value' => 'transit_damage', 'label' => 'Transit damage'],
                    ['value' => 'handling',       'label' => 'Handling'],
                    ['value' => 'water',          'label' => 'Water'],
                    ['value' => 'expiry',         'label' => 'Expiry'],
                    ['value' => 'unknown',        'label' => 'Unknown'],
                ],
                'actions'   => [
                    ['value' => 'written_off',           'label' => 'Written off'],
                    ['value' => 'returned_to_merchant',  'label' => 'Returned to merchant'],
                    ['value' => 'quarantine',            'label' => 'Quarantine'],
                ],
            ],
            'urls' => [
                'submit' => route('wms.damage.store'),
                'cancel' => route('wms.damage.index'),
            ],
            't' => [
                'title'        => 'New damage report',
                'title_index'  => 'Damage reports',
                'product'      => 'Product',
                'location'     => 'Location',
                'quantity'     => 'Quantity damaged',
                'cause'        => 'Cause',
                'action_taken' => 'Action taken',
                'action_hint'  => 'Leave blank if you haven\'t decided yet',
                'notes'        => 'Notes',
                'photos'       => 'Photo evidence',
                'photos_hint'  => 'JPEG / PNG, up to 5 MB each. Multiple photos allowed.',
                'add_photos'   => 'Choose photos…',
                'optional'     => 'optional',
                'cancel'       => __('levels.cancel') ?: 'Cancel',
                'save'         => 'Log damage report',
            ],
        ]);
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
