<?php

namespace App\Http\Controllers\Backend\Wms;

use App\Enums\Wms\OutboundType;
use App\Http\Controllers\Controller;
use App\Models\Backend\Wms\WmsLocation;
use App\Models\Backend\Wms\WmsProduct;
use App\Repositories\Hub\HubInterface;
use App\Repositories\Merchant\MerchantInterface;
use App\Repositories\Wms\WmsOutboundRepositoryInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WmsOutboundController extends Controller
{
    public function __construct(
        protected WmsOutboundRepositoryInterface $repo,
        protected MerchantInterface $merchantRepo,
        protected HubInterface $hubRepo
    ) {}

    public function index(Request $request)
    {
        $outbounds = $this->repo->all($request);
        $merchants = $this->merchantRepo->all();
        $hubs      = $this->hubRepo->all();
        return view('backend.wms.outbound.index', compact('outbounds', 'merchants', 'hubs'));
    }

    public function create()
    {
        $merchants = $this->merchantRepo->all();
        $hubs      = $this->hubRepo->all();
        $types     = $this->typeOptions();

        $productOptions = WmsProduct::companywise()->where('is_active', true)->orderBy('name')
            ->get(['id', 'sku', 'name', 'merchant_id', 'hub_id'])
            ->map(fn ($p) => ['id'=>$p->id, 'sku'=>$p->sku, 'name'=>$p->name, 'merchant_id'=>$p->merchant_id, 'hub_id'=>$p->hub_id])
            ->all();
        $locationOptions = WmsLocation::companywise()->where('is_active', true)->orderBy('code')
            ->get(['id', 'code', 'hub_id'])
            ->map(fn ($l) => ['id'=>$l->id, 'code'=>$l->code, 'hub_id'=>$l->hub_id])
            ->all();

        $nextNumber = $this->repo->nextOutboundNumber();
        return view('backend.wms.outbound.create', compact('merchants', 'hubs', 'types', 'productOptions', 'locationOptions', 'nextNumber'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'hub_id'              => ['required', 'integer', 'exists:hubs,id'],
            'merchant_id'         => ['required', 'integer', 'exists:merchants,id'],
            'type'                => ['required', 'string'],
            'items'               => ['required', 'array', 'min:1'],
            'items.*.product_id'  => ['required', 'integer', 'exists:wms_products,id'],
            'items.*.location_id' => ['required', 'integer', 'exists:wms_locations,id'],
            'items.*.quantity'    => ['required', 'integer', 'min:1'],
            'items.*.batch_number'=> ['nullable', 'string', 'max:191'],
        ]);

        $o = $this->repo->create([
            'hub_id'       => $data['hub_id'],
            'merchant_id'  => $data['merchant_id'],
            'type'         => $data['type'],
            'processed_by' => Auth::id(),
        ], $data['items']);

        Toastr::success(__('Outbound :n created. Click Complete to deduct stock.', ['n' => $o->outbound_number]));
        return redirect()->route('wms.outbound.show', $o->id);
    }

    public function show(int $id)
    {
        $outbound = $this->repo->find($id);
        if (!$outbound) return redirect()->route('wms.outbound.index');
        return view('backend.wms.outbound.show', compact('outbound'));
    }

    public function complete(int $id)
    {
        $outbound = $this->repo->find($id);
        if (!$outbound) return redirect()->route('wms.outbound.index');
        if ($outbound->status === 'completed') {
            Toastr::info(__('Already completed.'));
            return redirect()->route('wms.outbound.show', $outbound->id);
        }
        try {
            $this->repo->complete($outbound);
            Toastr::success(__('Outbound completed — stock deducted.'));
        } catch (\App\Exceptions\Wms\InsufficientStockException $e) {
            Toastr::error($e->getMessage());
        } catch (\Throwable $e) {
            Toastr::error(__('Failed: :m', ['m' => $e->getMessage()]));
        }
        return redirect()->route('wms.outbound.show', $outbound->id);
    }

    public function edit() { abort(404); }
    public function update() { abort(404); }
    public function destroy() { abort(404); }

    protected function typeOptions(): array
    {
        $rc = new \ReflectionClass(OutboundType::class);
        return array_values($rc->getConstants());
    }
}
