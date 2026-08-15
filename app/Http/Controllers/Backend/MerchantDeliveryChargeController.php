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

    /**
     * Shared shape for the create/edit screens.
     *
     * The Blade form loaded the selected rate card's defaults over AJAX
     * (merchant.deliveryCharge.deliveryChargeInfo -> a Blade partial). There
     * are only a handful of rate cards, so the React form ships them all as
     * props and fills the inputs client-side — one request instead of one per
     * category change, and no partial to keep in sync with the form.
     */
    private function chargeOptions()
    {
        return collect($this->repo->delivery_charges_get())->map(fn ($c) => [
            'id'                 => $c->id,
            'category'           => optional($c->category)->title,
            'weight'             => (float) ($c->weight ?? 0),
            // Defaults the form pre-fills when this card is picked.
            'extra_weight_price' => (float) ($c->extra_weight_price ?? 0),
            'same_day'           => (float) ($c->same_day ?? 0),
            'next_day'           => (float) ($c->next_day ?? 0),
            'sub_city'           => (float) ($c->sub_city ?? 0),
            'outside_city'       => (float) ($c->outside_city ?? 0),
        ])->values();
    }

    private function merchantProps($m)
    {
        return [
            'id'            => $m->id,
            'business_name' => $m->business_name,
            'unique_id'     => $m->merchant_unique_id,
            'name'          => optional($m->user)->name,
            'image'         => optional($m->user)->image,
        ];
    }

    private function formTranslations()
    {
        return [
            'title_index'    => 'Merchants',
            'title_parent'   => 'Delivery charges',
            'category'       => __('levels.category') ?: 'Category',
            'weight'         => __('merchant.weight') ?: 'Weight',
            'extra_weight'   => 'Extra weight price',
            'same_day'       => __('levels.same_day') ?: 'Same day',
            'next_day'       => __('levels.next_day') ?: 'Next day',
            'sub_city'       => __('levels.sub_city') ?: 'Sub city',
            'outside_city'   => __('levels.outside_city') ?: 'Outside city',
            'status'         => __('levels.status') ?: 'Status',
            'active'         => __('status.1') ?: 'Active',
            'inactive'       => __('status.0') ?: 'Inactive',
            'save'           => __('levels.save') ?: 'Save',
            'cancel'         => __('levels.cancel') ?: 'Cancel',
            'saving'         => 'Saving…',
            'back_to_list'   => 'Back to delivery charges',
            'rates_section'  => 'Rates',
            'category_hint'  => 'Rates below are pre-filled from the selected rate card — adjust them for this merchant.',
            'weight_hint'    => 'Set on the rate card; not editable per merchant.',
            'empty_title'    => __('merchant.delivery_charge_empty_title') ?: 'No rate cards yet',
            'empty_body'     => __('merchant.delivery_charge_empty_body') ?: 'Create a delivery-charge rate card before assigning one to a merchant.',
            'empty_cta'      => __('merchant.delivery_charge_empty_cta') ?: 'Create a rate card',
            'empty_view_all' => __('merchant.delivery_charge_empty_view_all') ?: 'View all rate cards',
        ];
    }

    public function create($id){
        $singleMerchant = $this->repoMerchant->get($id);
        if(blank($singleMerchant)){
            abort(404);
        }

        return Inertia::render('Admin/Merchant/DeliveryCharge/Form', [
            'mode'     => 'create',
            'merchant' => $this->merchantProps($singleMerchant),
            'charges'  => $this->chargeOptions(),
            'row'      => null,
            'currency' => settings()->currency,
            'urls'     => [
                'submit'        => route('merchant.deliveryCharge.store', $singleMerchant->id),
                'index'         => route('merchant.deliveryCharge.index', $singleMerchant->id),
                'view'          => route('merchant.view', $singleMerchant->id),
                'charge_create' => route('delivery-charge.create'),
                'charge_index'  => route('delivery-charge.index'),
            ],
            't' => $this->formTranslations() + ['title' => __('merchant.create_delivery_charge') ?: 'Add delivery charge'],
        ]);
    }

    public function store(MerchantDeliveryChargeRequest $request, $merchant){

        if($this->repo->store($request,$merchant)){
            // Flash via ->with() so HandleInertiaRequests exposes it as
            // props.flash for the AdminLayout FlashBanner. Toastr writes to a
            // legacy session key the Inertia frontend never reads, so these
            // used to redirect with no visible feedback at all.
            return redirect()->route('merchant.deliveryCharge.index',$merchant)
                ->with('success', __('merchant.delivery_charge_added_msg'));
        }

        return redirect()->back()->withInput()
            ->with('error', __('merchant.delivery_charge_error_msg'));
    }

    public function edit($merchant,$id){
        $merchantDeliveryCharge = $this->repo->get($merchant,$id);
        $singleMerchant         = $this->repoMerchant->get($merchant);
        if(blank($singleMerchant) || blank($merchantDeliveryCharge)){
            abort(404);
        }

        return Inertia::render('Admin/Merchant/DeliveryCharge/Form', [
            'mode'     => 'edit',
            'merchant' => $this->merchantProps($singleMerchant),
            'charges'  => $this->chargeOptions(),
            'row'      => [
                'id'                 => $merchantDeliveryCharge->id,
                'delivery_charge_id' => $merchantDeliveryCharge->delivery_charge_id,
                'weight'             => (float) ($merchantDeliveryCharge->weight ?? 0),
                'extra_weight_price' => (float) ($merchantDeliveryCharge->extra_weight_price ?? 0),
                'same_day'           => (float) ($merchantDeliveryCharge->same_day ?? 0),
                'next_day'           => (float) ($merchantDeliveryCharge->next_day ?? 0),
                'sub_city'           => (float) ($merchantDeliveryCharge->sub_city ?? 0),
                'outside_city'       => (float) ($merchantDeliveryCharge->outside_city ?? 0),
                'status'             => (int) $merchantDeliveryCharge->status,
            ],
            'currency' => settings()->currency,
            'urls'     => [
                'submit'        => route('merchant.deliveryCharge.update', ['merchant' => $singleMerchant->id, 'id' => $merchantDeliveryCharge->id]),
                'index'         => route('merchant.deliveryCharge.index', $singleMerchant->id),
                'view'          => route('merchant.view', $singleMerchant->id),
                'charge_create' => route('delivery-charge.create'),
                'charge_index'  => route('delivery-charge.index'),
            ],
            't' => $this->formTranslations() + ['title' => __('merchant.edit_delivery_charge') ?: 'Edit delivery charge'],
        ]);
    }

    public function update(MerchantDeliveryChargeRequest $request,$merchant,$id){

        if($this->repo->update($request,$id,$merchant)){
            return redirect()->route('merchant.deliveryCharge.index',$merchant)
                ->with('success', __('merchant.delivery_charge_update_msg'));
        }

        return redirect()->back()->withInput()
            ->with('error', __('merchant.delivery_charge_update_msg'));
    }
    public function delete($merchant,$id){
        $this->repo->delete($id,$merchant);
        return back()->with('success', __('merchant.delivery_charge_delete_msg'));
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
