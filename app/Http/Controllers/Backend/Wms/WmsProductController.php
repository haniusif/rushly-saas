<?php

namespace App\Http\Controllers\Backend\Wms;

use App\Enums\Wms\ProductUnit;
use App\Http\Controllers\Controller;
use App\Models\Backend\Wms\WmsProduct;
use App\Repositories\Hub\HubInterface;
use App\Repositories\Merchant\MerchantInterface;
use App\Repositories\Wms\WmsProductRepositoryInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WmsProductController extends Controller
{
    public function __construct(
        protected WmsProductRepositoryInterface $repo,
        protected MerchantInterface $merchantRepo,
        protected HubInterface $hubRepo
    ) {}

    public function index(Request $request)
    {
        $products  = $this->repo->all($request);
        $merchants = $this->merchantRepo->all();
        $hubs      = $this->hubRepo->all();
        return view('backend.wms.products.index', compact('products', 'merchants', 'hubs'));
    }

    public function create()
    {
        $merchants = $this->merchantRepo->all();
        $hubs      = $this->hubRepo->all();
        $units     = $this->unitOptions();
        return view('backend.wms.products.create', compact('merchants', 'hubs', 'units'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'merchant_id'   => ['required', 'integer', 'exists:merchants,id'],
            'hub_id'        => ['required', 'integer', 'exists:hubs,id'],
            'name'          => ['required', 'string', 'max:191'],
            'sku'           => ['required', 'string', 'max:191', 'unique:wms_products,sku'],
            'barcode'       => ['nullable', 'string', 'max:191'],
            'description'   => ['nullable', 'string'],
            'category'      => ['nullable', 'string', 'max:191'],
            'weight'        => ['nullable', 'numeric'],
            'unit'          => ['required', 'string'],
            'reorder_point' => ['nullable', 'integer', 'min:0'],
            'track_expiry'  => ['nullable'],
            'is_active'     => ['nullable'],
            'dim_l'         => ['nullable', 'numeric'],
            'dim_w'         => ['nullable', 'numeric'],
            'dim_h'         => ['nullable', 'numeric'],
        ]);

        // Pack dimensions
        if (!empty($data['dim_l']) || !empty($data['dim_w']) || !empty($data['dim_h'])) {
            $data['dimensions'] = ['l' => $data['dim_l'] ?? null, 'w' => $data['dim_w'] ?? null, 'h' => $data['dim_h'] ?? null];
        }
        unset($data['dim_l'], $data['dim_w'], $data['dim_h']);

        // Auto-barcode if blank: use SKU prefixed so it's unique-ish.
        if (empty($data['barcode'])) {
            $data['barcode'] = 'BAR' . preg_replace('/[^A-Z0-9]/i', '', strtoupper($data['sku']));
        }

        $data['track_expiry'] = $request->boolean('track_expiry');
        $data['is_active']    = $request->boolean('is_active', true);

        $p = $this->repo->create($data);
        Toastr::success(__('Product created.'));
        return redirect()->route('wms.products.show', $p->id);
    }

    public function show(int $id)
    {
        $product = $this->repo->find($id);
        if (!$product) {
            Toastr::error(__('Product not found.'));
            return redirect()->route('wms.products.index');
        }
        return view('backend.wms.products.show', compact('product'));
    }

    public function edit(int $id)
    {
        $product   = $this->repo->find($id);
        if (!$product) {
            Toastr::error(__('Product not found.'));
            return redirect()->route('wms.products.index');
        }
        $merchants = $this->merchantRepo->all();
        $hubs      = $this->hubRepo->all();
        $units     = $this->unitOptions();
        return view('backend.wms.products.edit', compact('product', 'merchants', 'hubs', 'units'));
    }

    public function update(Request $request, int $id)
    {
        $product = $this->repo->find($id);
        if (!$product) return redirect()->route('wms.products.index');

        $data = $request->validate([
            'merchant_id'   => ['required', 'integer', 'exists:merchants,id'],
            'hub_id'        => ['required', 'integer', 'exists:hubs,id'],
            'name'          => ['required', 'string', 'max:191'],
            'sku'           => ['required', 'string', 'max:191', 'unique:wms_products,sku,' . $product->id],
            'barcode'       => ['nullable', 'string', 'max:191'],
            'description'   => ['nullable', 'string'],
            'category'      => ['nullable', 'string', 'max:191'],
            'weight'        => ['nullable', 'numeric'],
            'unit'          => ['required', 'string'],
            'reorder_point' => ['nullable', 'integer', 'min:0'],
            'dim_l'         => ['nullable', 'numeric'],
            'dim_w'         => ['nullable', 'numeric'],
            'dim_h'         => ['nullable', 'numeric'],
        ]);
        if (!empty($data['dim_l']) || !empty($data['dim_w']) || !empty($data['dim_h'])) {
            $data['dimensions'] = ['l' => $data['dim_l'] ?? null, 'w' => $data['dim_w'] ?? null, 'h' => $data['dim_h'] ?? null];
        }
        unset($data['dim_l'], $data['dim_w'], $data['dim_h']);
        $data['track_expiry'] = $request->boolean('track_expiry');
        $data['is_active']    = $request->boolean('is_active', true);

        $this->repo->update($product, $data);
        Toastr::success(__('Product updated.'));
        return redirect()->route('wms.products.show', $product->id);
    }

    public function destroy(int $id)
    {
        $product = $this->repo->find($id);
        if (!$product) return redirect()->route('wms.products.index');
        $this->repo->delete($product);
        Toastr::success(__('Product deleted.'));
        return redirect()->route('wms.products.index');
    }

    /** Render a printable barcode PNG for the product (uses milon/barcode). */
    public function barcode(int $product)
    {
        $p = $this->repo->find($product);
        if (!$p) abort(404);

        $code = $p->barcode ?: $p->sku;
        try {
            $generator = new \Milon\Barcode\DNS1D();
            $png = $generator->getBarcodePNG($code, 'C128', 2, 60);
            $html = '<!doctype html><html><head><meta charset="utf-8"><title>'.e($p->sku).'</title>'
                . '<style>body{font-family:sans-serif;text-align:center;padding:24px}.lbl{font-size:13px;margin-top:8px}.code{font-family:monospace;font-size:14px;letter-spacing:1px}.btn{margin-top:16px}@media print{.btn{display:none}}</style>'
                . '</head><body>'
                . '<h3 style="margin:0 0 8px;">'.e($p->name).'</h3>'
                . '<img src="data:image/png;base64,'.$png.'" alt="barcode">'
                . '<div class="code">'.e($code).'</div>'
                . '<div class="lbl">SKU: '.e($p->sku).'  ·  Unit: '.e($p->unit).'</div>'
                . '<button class="btn" onclick="window.print()">'.e(__('Print')).'</button>'
                . '</body></html>';
            return response($html)->header('Content-Type', 'text/html');
        } catch (\Throwable $e) {
            return response('Barcode generator failed: ' . $e->getMessage(), 500);
        }
    }

    protected function unitOptions(): array
    {
        $rc = new \ReflectionClass(ProductUnit::class);
        return array_values($rc->getConstants());
    }
}
