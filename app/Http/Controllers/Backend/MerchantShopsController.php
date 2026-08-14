<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantShop\StoreRequest;
use App\Http\Requests\MerchantShop\UpdateRequest;
use App\Repositories\MerchantShops\ShopsInterface;
use App\Repositories\Merchant\MerchantInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Brian2694\Toastr\Facades\Toastr;
use Inertia\Inertia;
class MerchantShopsController extends Controller
{
    protected $repo;
    protected $repoMerchant;
    public function __construct(ShopsInterface $repo, MerchantInterface $repoMerchant )
    {

        $this->repo=$repo;
        $this->repoMerchant=$repoMerchant;
    }

    public function index($id){
        $m = $this->repoMerchant->get($id);
        if(blank($m)){ abort(404); }
        $shops = $this->repo->merchant_shops_get($id);

        $rows = collect($shops)->map(fn ($s) => [
            'id'          => $s->id,
            'name'        => $s->name,
            'contact_no'  => $s->contact_no,
            'address'     => $s->address,
            'status'      => (int) $s->status,
            'is_default'  => (int) ($s->default_shop ?? 0) === 1,
            'urls' => [
                'edit'    => route('merchant.shops.edit',    $s->id),
                'delete'  => route('merchant.shops.delete',  $s->id),
                'default' => route('merchant.shops.default', ['merchant_id' => $m->id, 'id' => $s->id]),
            ],
        ])->values();

        return Inertia::render('Admin/Merchant/Shops/Index', [
            'merchant' => [
                'id'            => $m->id,
                'business_name' => $m->business_name,
                'unique_id'     => $m->merchant_unique_id,
                'name'          => optional($m->user)->name,
                'image'         => optional($m->user)->image,
            ],
            'rows'        => $rows,
            'permissions' => [
                'create' => hasPermission('merchant_shop_create'),
                'update' => hasPermission('merchant_shop_update'),
                'delete' => hasPermission('merchant_shop_delete'),
            ],
            'urls' => [
                'view'   => route('merchant.view', $m->id),
                'index'  => route('merchant.shops.index', $m->id),
                'create' => route('merchant.shops.create', $m->id),
            ],
            't' => [
                'title'           => 'Shops',
                'title_index'     => 'Merchants',
                'shop_name'       => __('merchantshops.name') ?: 'Shop name',
                'contact'         => __('merchantshops.contact') ?: 'Contact',
                'address'         => __('merchantshops.address') ?: 'Address',
                'status'          => __('levels.status') ?: 'Status',
                'actions'         => __('levels.actions') ?: 'Actions',
                'active'          => __('status.1') ?: 'Active',
                'inactive'        => __('status.0') ?: 'Inactive',
                'add'             => __('levels.add') ?: 'Add',
                'edit'            => __('levels.edit') ?: 'Edit',
                'delete'          => __('levels.delete') ?: 'Delete',
                'set_default'     => 'Set as default',
                'default'         => 'Default',
                'delete_confirm'  => 'Delete this shop?',
                'no_rows'         => 'No shops registered for this merchant.',
                'back_to_view'    => 'Back to merchant',
            ],
        ]);
    }

    //merchant shops create page
    public function create($id){
        $merchant_id    = $id;
        $singleMerchant = $this->repoMerchant->get($id);
        if(blank($singleMerchant)){
            abort(404);
        }
        return view('backend.merchant.shops.create',compact('merchant_id','singleMerchant'));
    }

    //merchant shops store
    public function store(StoreRequest $request){

            if($this->repo->store($request)){
                Toastr::success(__('merchantshops.added_msg'));
                return redirect()->route('merchant.shops.index',$request->merchant_id);
            }else{
                Toastr::error(__('merchantshops.error_msg'),__('message.error'));
                return Redirect::back()->withInput();
            }
    }

    public function edit($id){

        $edit_shop      = $this->repo->get($id);
        $merchant_id    = $edit_shop->merchant_id;
        $singleMerchant = $this->repoMerchant->get($merchant_id);
        if(blank($singleMerchant) || blank($edit_shop)){
            abort(404);
        }
        return view('backend.merchant.shops.edit', compact('edit_shop','merchant_id','singleMerchant'));
    }

    public function update(UpdateRequest $request){

        if($this->repo->update($request)){
            Toastr::success(__('merchantshops.update_msg'),__('message.success'));
            return redirect()->route('merchant.shops.index',$request->merchant_id);
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

    public function defaultShop($merchant_id,$id)
    {
        $this->repo->defaultShop($merchant_id,$id);
        Toastr::success(__('merchantshops.update_msg'),__('message.success'));
        return back();
    }
}
