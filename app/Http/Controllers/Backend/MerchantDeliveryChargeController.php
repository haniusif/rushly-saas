<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantDeliveryCharge\MerchantDeliveryChargeRequest;
use App\Models\Backend\DeliveryCharge;
use App\Repositories\Merchant\MerchantInterface;
use App\Repositories\MerchantDeliveryCharge\MerchantDeliveryChargeInterface;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;
use Inertia\Inertia;

class MerchantDeliveryChargeController extends Controller
{
    protected $repo;
    protected $repoMerchant;

    public function __construct(MerchantDeliveryChargeInterface $repo,MerchantInterface $repoMerchant )
    {
            $this->repo=$repo;
            $this->repoMerchant=$repoMerchant;

    }
    public function index($id){
        $m = $this->repoMerchant->get($id);
        if(blank($m)){ abort(404); }
        $charges = $this->repo->all($id);

        $rows = $charges->getCollection()->map(fn ($c) => [
            'id'             => $c->id,
            'category'       => optional(optional($c->deliveryCharge)->category)->title,
            'weight'         => (float) (optional($c->deliveryCharge)->weight ?? 0),
            'extra_weight'   => (float) (optional($c->deliveryCharge)->extra_weight_price ?? 0),
            'same_day'       => (float) ($c->same_day ?? 0),
            'next_day'       => (float) ($c->next_day ?? 0),
            'sub_city'       => (float) ($c->sub_city ?? 0),
            'outside_city'   => (float) ($c->outside_city ?? 0),
            'status'         => (int) $c->status,
            'urls' => [
                'edit'   => route('merchant.deliveryCharge.edit',   ['merchant' => $m->id, 'id' => $c->id]),
                'delete' => route('merchant.deliveryCharge.delete', ['merchant' => $m->id, 'id' => $c->id]),
            ],
        ])->values();

        return Inertia::render('Admin/Merchant/DeliveryCharge/Index', [
            'merchant' => [
                'id'            => $m->id,
                'business_name' => $m->business_name,
                'unique_id'     => $m->merchant_unique_id,
                'name'          => optional($m->user)->name,
                'image'         => optional($m->user)->image,
            ],
            'rows'        => $rows,
            'currency'    => settings()->currency,
            'permissions' => [
                'create' => hasPermission('merchant_delivery_charge_create'),
                'update' => hasPermission('merchant_delivery_charge_update'),
                'delete' => hasPermission('merchant_delivery_charge_delete'),
            ],
            'urls' => [
                'view'   => route('merchant.view', $m->id),
                'index'  => route('merchant.deliveryCharge.index', $m->id),
                'create' => route('merchant.deliveryCharge.create', $m->id),
            ],
            't' => [
                'title'           => 'Delivery charges',
                'title_index'     => 'Merchants',
                'category'        => __('merchant.category') ?: 'Category',
                'weight'          => __('merchant.weight') ?: 'Weight',
                'extra_weight'    => 'Extra weight',
                'same_day'        => __('merchant.same_day') ?: 'Same day',
                'next_day'        => __('merchant.next_day') ?: 'Next day',
                'sub_city'        => __('merchant.sub_city') ?: 'Sub city',
                'outside_city'    => __('merchant.outside_city') ?: 'Outside city',
                'status'          => __('levels.status') ?: 'Status',
                'actions'         => __('levels.actions') ?: 'Actions',
                'active'          => __('status.1') ?: 'Active',
                'inactive'        => __('status.0') ?: 'Inactive',
                'add'             => __('levels.add') ?: 'Add',
                'edit'            => __('levels.edit') ?: 'Edit',
                'delete'          => __('levels.delete') ?: 'Delete',
                'delete_confirm'  => 'Delete this rule?',
                'no_rows'         => 'No delivery-charge rules set yet.',
                'back_to_view'    => 'Back to merchant',
            ],
        ]);
    }

    public function create($id){
        $deliveryCharges = $this->repo->delivery_charges_get();
        $singleMerchant  = $this->repoMerchant->get($id);

        if(blank($singleMerchant)){
            abort(404);
        }
        return view('backend.merchant.delivery-charge.create',compact('deliveryCharges','singleMerchant'));
    }

    public function store(MerchantDeliveryChargeRequest $request, $merchant){
     
        if($this->repo->store($request,$merchant)){
            Toastr::success(__('merchant.delivery_charge_added_msg'),__('message.success'));
            return redirect()->route('merchant.deliveryCharge.index',$merchant);
        }else{
            Toastr::error(__('merchant.delivery_charge_error_msg'),__('message.error'));
            return redirect()->back()->withInput();
        }
    }

    public function edit($merchant,$id){
        $deliveryCharges        = $this->repo->delivery_charges_get();
        $merchantDeliveryCharge = $this->repo->get($merchant,$id);
        $singleMerchant         = $this->repoMerchant->get($merchant);
        if(blank($singleMerchant) || blank($merchantDeliveryCharge)){
            abort(404);
        }
        return view('backend.merchant.delivery-charge.edit', compact('deliveryCharges','merchantDeliveryCharge','singleMerchant'));
    }

    public function update(MerchantDeliveryChargeRequest $request,$merchant,$id){

        if($this->repo->update($request,$id,$merchant)){
            Toastr::success(__('merchant.delivery_charge_update_msg'),__('message.success'));
            return redirect()->route('merchant.deliveryCharge.index',$merchant);
        }else{
            Toastr::error(__('merchant.delivery_charge_update_msg'),__('message.error'));
            return redirect()->back()->withInput();
        }
    }
    public function delete($merchant,$id){
        $this->repo->delete($id,$merchant);
        Toastr::success(__('merchant.delivery_charge_delete_msg'),__('message.success'));
        return back();
    }

    public function deliveryChargeInfo(Request $request)
    {
        if (request()->ajax()) {
            if ($request->delivery_charge_id) {
                $deliveryCharge = DeliveryCharge::find($request->delivery_charge_id);
               
                if (!blank($deliveryCharge)) {
                    return view('backend.merchant.delivery-charge.deliveryChargeInfo', compact('deliveryCharge'));
                }
                return '';
            }
        }
        return '';
    }


}
