<?php

namespace App\Http\Controllers\Backend\Wms;

use App\Enums\Wms\ProductUnit;
use App\Http\Controllers\Backend\Wms\Concerns\RendersInertiaIndex;
use App\Http\Controllers\Controller;
use App\Models\Backend\Wms\WmsProduct;
use App\Repositories\Hub\HubInterface;
use App\Repositories\Merchant\MerchantInterface;
use App\Repositories\Wms\WmsProductRepositoryInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WmsProductController extends Controller
{
    use RendersInertiaIndex;

    public function __construct(
        protected WmsProductRepositoryInterface $repo,
        protected MerchantInterface $merchantRepo,
        protected HubInterface $hubRepo
    ) {}

    public function index(Request $request)
    {
        $paginator = $this->repo->all($request);
        $merchants = $this->merchantRepo->all();
        $hubs      = $this->hubRepo->all();

        $rows = collect($paginator->items())->map(function ($p) {
            $onHand = (int) ($p->stocks?->sum('quantity') ?? 0);
            $reorderPoint = (int) ($p->reorder_point ?? 0);
            return [
                'id'            => $p->id,
                'sku'           => $p->sku,
                'name'          => $p->name,
                'merchant'      => optional($p->merchant)->business_name,
                'hub'           => optional($p->hub)->name,
                'barcode'       => $p->barcode,
                'on_hand'       => $onHand,
                'reorder_point' => $reorderPoint,
                'low'           => $reorderPoint > 0 && $onHand <= $reorderPoint,
                'urls' => [
                    'view'    => route('wms.products.show', $p->id),
                    'barcode' => route('wms.products.barcode', $p->id),
                ],
            ];
        })->values();

        return Inertia::render('Admin/Wms/Products/Index', [
            'rows'        => $rows,
            'pagination'  => $this->paginateMeta($paginator),
            'filters'     => [
                'q'           => $request->input('q', ''),
                'merchant_id' => $request->input('merchant_id', ''),
                'hub_id'      => $request->input('hub_id', ''),
            ],
            'lookups'     => [
                'merchants' => $this->lookupRows($merchants, fn ($m) => ['id' => $m->id, 'name' => $m->business_name]),
                'hubs'      => $this->lookupRows($hubs, fn ($h) => ['id' => $h->id, 'name' => $h->name]),
            ],
            'permissions' => ['create' => hasPermission('wms_manage')],
            'urls' => [
                'index'  => route('wms.products.index'),
                'create' => route('wms.products.create'),
            ],
            't' => $this->indexLabels([
                'title' => 'Products', 'sku' => 'SKU', 'name' => 'Name',
                'merchant' => 'Merchant', 'hub' => 'Hub', 'barcode' => 'Barcode',
                'on_hand' => 'On hand', 'search' => 'Search name / SKU / barcode',
                'low' => 'LOW', 'ok' => 'OK',
            ]),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Wms/Products/Form', $this->formProps([
            'title' => 'New product',
            'mode'  => 'create',
            'urls'  => [
                'submit' => route('wms.products.store'),
                'cancel' => route('wms.products.index'),
            ],
        ]));
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
        $product->loadMissing(['merchant', 'hub', 'stocks.location']);

        $stockRows = collect($product->stocks ?? [])->map(fn ($s) => [
            'location' => optional($s->location)->code,
            'quantity' => (int) ($s->quantity ?? 0),
            'reserved' => (int) ($s->reserved_quantity ?? $s->reserved ?? 0),
        ])->values();

        $onHand   = $stockRows->sum('quantity');
        $reserved = $stockRows->sum('reserved');
        $reorder  = (int) ($product->reorder_point ?? 0);

        return Inertia::render('Admin/Wms/Products/Show', [
            'product' => [
                'id'            => $product->id,
                'sku'           => $product->sku,
                'name'          => $product->name,
                'description'   => $product->description,
                'category'      => $product->category,
                'barcode'       => $product->barcode,
                'unit'          => $product->unit,
                'weight'        => $product->weight,
                'dimensions'    => $product->dimensions ?: null,
                'reorder_point' => $reorder,
                'track_expiry'  => (bool) $product->track_expiry,
                'is_active'     => (bool) $product->is_active,
                'merchant'      => optional($product->merchant)->business_name,
                'hub'           => optional($product->hub)->name,
                'created_at'    => optional($product->created_at)->toDateTimeString(),
                'updated_at'    => optional($product->updated_at)->toDateTimeString(),
            ],
            'stock' => [
                'rows'     => $stockRows,
                'on_hand'  => $onHand,
                'reserved' => $reserved,
                'available'=> max($onHand - $reserved, 0),
                'reorder'  => $reorder,
                'low'      => $reorder > 0 && $onHand <= $reorder,
            ],
            'permissions' => [
                'update' => hasPermission('wms_manage'),
                'delete' => hasPermission('wms_manage'),
            ],
            'urls' => [
                'index'   => route('wms.products.index'),
                'edit'    => route('wms.products.edit', $product->id),
                'destroy' => route('wms.products.destroy', $product->id),
                'barcode' => route('wms.products.barcode', $product->id),
            ],
            't' => [
                'title'         => 'Product',
                'list'          => 'Products',
                'back_to_list'  => 'Back to products',
                'edit'          => __('levels.edit') ?: 'Edit',
                'delete'        => __('levels.delete') ?: 'Delete',
                'delete_confirm'=> 'Delete this product?',
                'print_barcode' => 'Print barcode',
                'sku'           => 'SKU',
                'barcode'       => 'Barcode',
                'name'          => 'Name',
                'description'   => 'Description',
                'category'      => __('levels.category') ?: 'Category',
                'unit'          => 'Unit',
                'weight'        => __('levels.weight') ?: 'Weight',
                'dimensions'    => 'Dimensions (LxWxH)',
                'merchant'      => 'Merchant',
                'hub'           => 'Hub',
                'reorder_point' => 'Reorder point',
                'track_expiry'  => 'Track expiry',
                'is_active'     => 'Active',
                'identity'      => 'Identity',
                'organization'  => 'Organization',
                'specifications'=> 'Specifications',
                'stock_summary' => 'Stock summary',
                'stock_per_location' => 'Stock per location',
                'location'      => 'Location',
                'on_hand'       => 'On hand',
                'reserved'      => 'Reserved',
                'available'     => 'Available',
                'low'           => 'LOW STOCK',
                'ok'            => 'OK',
                'no_stock'      => 'No stock recorded yet.',
                'yes'           => 'Yes', 'no' => 'No',
                'created_at'    => __('levels.created_at') ?: 'Created',
                'updated_at'    => __('parcel.updated_on') ?: 'Updated',
            ],
        ]);
    }

    public function edit(int $id)
    {
        $product = $this->repo->find($id);
        if (!$product) {
            Toastr::error(__('Product not found.'));
            return redirect()->route('wms.products.index');
        }

        return Inertia::render('Admin/Wms/Products/Form', $this->formProps([
            'title'   => 'Edit product',
            'mode'    => 'edit',
            'product' => [
                'id'            => $product->id,
                'merchant_id'   => $product->merchant_id,
                'hub_id'        => $product->hub_id,
                'name'          => $product->name,
                'sku'           => $product->sku,
                'barcode'       => $product->barcode,
                'description'   => $product->description,
                'category'      => $product->category,
                'weight'        => $product->weight,
                'unit'          => $product->unit,
                'reorder_point' => (int) ($product->reorder_point ?? 0),
                'dimensions'    => $product->dimensions ?: null,
                'track_expiry'  => (bool) $product->track_expiry,
                'is_active'     => (bool) $product->is_active,
            ],
            'urls' => [
                'submit' => route('wms.products.update', $product->id),
                'cancel' => route('wms.products.show', $product->id),
            ],
        ]));
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

    /** Shared props for create + edit Inertia forms. Pass per-page extras via $extra. */
    protected function formProps(array $extra = []): array
    {
        return array_merge([
            'lookups' => [
                'merchants' => $this->lookupRows($this->merchantRepo->all(), fn ($m) => ['id' => $m->id, 'name' => $m->business_name]),
                'hubs'      => $this->lookupRows($this->hubRepo->all(),      fn ($h) => ['id' => $h->id, 'name' => $h->name]),
                'units'     => $this->unitOptions(),
            ],
            'product' => null,
            't' => [
                'title_index'    => 'Products',
                'list'           => __('levels.list') ?: 'List',
                'name'           => __('levels.name') ?: 'Name',
                'sku'            => 'SKU',
                'barcode'        => 'Barcode',
                'barcode_hint'   => 'Leave blank to auto-generate from SKU',
                'merchant'       => 'Merchant',
                'hub'            => 'Hub',
                'category'       => 'Category',
                'unit'           => 'Unit',
                'weight'         => 'Weight (kg)',
                'reorder_point'  => 'Reorder point',
                'dim_l'          => 'Length (cm)',
                'dim_w'          => 'Width (cm)',
                'dim_h'          => 'Height (cm)',
                'track_expiry'   => 'Track expiry',
                'is_active'      => 'Active',
                'description'    => 'Description',
                'identity'       => 'Identity',
                'classification' => 'Classification',
                'metrics'        => 'Metrics',
                'flags'          => 'Flags',
                'cancel'         => __('levels.cancel') ?: 'Cancel',
                'save'           => __('levels.save') ?: 'Save',
                'update'         => __('levels.update') ?: 'Update',
                'required'       => 'required',
            ],
        ], $extra);
    }
}
