<?php

namespace App\Http\Controllers\Backend\MerchantPanel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\MerchantPanel\Shops\StoreRequest;
use App\Http\Requests\MerchantPanel\Shops\UpdateRequest;
use App\Repositories\MerchantPanel\Shops\ShopsInterface;
use Illuminate\Support\Facades\Auth;
use Brian2694\Toastr\Facades\Toastr;
use Inertia\Inertia;
class ShopsController extends Controller
{
    protected $repo;
    public function __construct(ShopsInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $singleMerchant = $this->repo->getMerchant(Auth::user()->id);
        $paginator      = $this->repo->all($singleMerchant->id);
        $i = (($paginator->currentPage() - 1) * $paginator->perPage()) + 1;

        $rows = collect($paginator->items())->map(function ($shop) use (&$i) {
            return [
                'serial'     => $i++,
                'id'         => $shop->id,
                'name'       => $shop->name,
                'contact_no' => $shop->contact_no,
                'address'    => $shop->address,
                'is_active'  => (int) $shop->status === \App\Enums\Status::ACTIVE,
                'edit_url'   => route('merchant-panel.shops.edit', $shop->id),
            ];
        })->values();

        return Inertia::render('Merchant/Shops/Index', [
            'rows'       => $rows,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
                'links'        => $paginator->linkCollection()->map(fn ($l) => [
                    'url'    => $l['url'],
                    'label'  => $l['label'],
                    'active' => (bool) $l['active'],
                ])->values(),
            ],
            'urls' => [
                'create' => route('merchant-panel.shops.create'),
            ],
            't' => [
                'title'     => __('merchantshops.title') ?: 'Shops',
                'list'      => __('levels.list') ?: 'List',
                'add'       => __('levels.add') ?: 'Add',
                'dashboard' => __('levels.dashboard') ?: 'Dashboard',
                'id'        => __('levels.id') ?: 'ID',
                'name'      => __('merchantshops.name') ?: 'Name',
                'contact'   => __('merchantshops.contact') ?: 'Contact',
                'address'   => __('merchantshops.address') ?: 'Address',
                'status'    => __('levels.status') ?: 'Status',
                'actions'   => __('levels.actions') ?: 'Actions',
                'active'    => __('merchantshops.active') ?: 'Active',
                'inactive'  => __('merchantshops.inactive') ?: 'Inactive',
                'edit'      => __('levels.edit') ?: 'Edit',
                'empty'     => __('levels.no_data_found') ?: 'No shops yet.',
            ],
        ]);
    }

    //merchant shops create page
    public function create(){
        return Inertia::render('Merchant/Shops/Create', [
            'urls' => [
                'store'  => route('merchant-panel.shops.store'),
                'cancel' => route('merchant-panel.shops.index'),
            ],
            't' => [
                'title'        => __('merchantshops.title') ?: 'Pickup point',
                'add'          => __('levels.add') ?: 'Add',
                'name'         => __('levels.name') ?: 'Name',
                'name_ph'      => __('merchantPlaceholder.name') ?: 'Name',
                'contact'      => __('levels.phone') ?: 'Contact',
                'contact_ph'   => __('merchantPlaceholder.phone') ?: 'Phone',
                'address'      => __('levels.address') ?: 'Address',
                'address_ph'   => __('placeholder.address') ?: 'Address',
                'status'       => __('levels.status') ?: 'Status',
                'active'       => __('levels.active') ?: 'Active',
                'inactive'     => __('levels.inactive') ?: 'Inactive',
                'save'         => __('levels.save') ?: 'Save',
                'cancel'       => __('levels.cancel') ?: 'Cancel',
                'title_index'  => __('merchantshops.title') ?: 'Pickup points',
            ],
        ]);
    }

    //merchant shops store
    public function store(StoreRequest $request){
        if($this->repo->store(Auth::user()->merchant->id, $request)){
            Toastr::success(__('merchantshops.added_msg'),__('message.success'));
            return redirect()->route('merchant-panel.shops.index');
        }else{
            Toastr::error(__('merchantshops.error_msg'),__('message.error'));
            return Redirect::back()->withInput();
        }
    }

    public function edit($id){ // shop id
        $shop = $this->repo->get($id);
        return view('backend.merchant_panel.shops.edit', compact('shop'));
    }

    public function update($id, UpdateRequest $request){
        if($this->repo->update($id, $request)){
            Toastr::success(__('merchantshops.update_msg'),__('message.success'));
            return redirect()->route('merchant-panel.shops.index');
        }else{
            Toastr::error(__('merchantshops.update_msg'),__('message.error'));
            return Redirect::back()->withInput();
        }
    }
    public function delete($id){
        $this->repo->delete($id);
        Toastr::success(__('merchantshops.delete_msg'),__('message.success'));
        return back();
    }
}
