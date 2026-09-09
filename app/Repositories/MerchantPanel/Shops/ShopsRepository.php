<?php
namespace App\Repositories\MerchantPanel\Shops;

use App\Models\MerchantShops;
use App\Repositories\MerchantPanel\Shops\ShopsInterface;
use App\Models\Backend\Merchant;
use App\Enums\Status;

class ShopsRepository implements ShopsInterface{

    public function all($id){
        return MerchantShops::where('merchant_id',$id)->orderBy('id','desc')->paginate(10);
    }

    public function get($id){
        return MerchantShops::where('id',$id)->first();
    }

    public function getMerchant($id){
        return Merchant::where('user_id',$id)->first();
    }

    public function store($id, $request){
        try {
                $shop              = new MerchantShops();
                $shop->merchant_id = $id;
                $shop->name        = $request->name;
                $shop->contact_no  = $request->contact_no;
                $shop->address     = $request->address;
                $shop->merchant_lat= $request->lat;
                $shop->merchant_long= $request->long;
                $shop->status      = $request->status;

                // A merchant first pickup point becomes their default.
                // Everything that resolves a pickup point - the import
                // template prefill, quick shipment, the parcel form - asks
                // for the default, so a merchant whose only shop is not
                // flagged effectively has none. Merchant creation already
                // does this for the shop it makes; this path never did.
                $isFirst = ! MerchantShops::where('merchant_id', $id)->exists();
                $shop->default_shop = $isFirst ? Status::ACTIVE : Status::INACTIVE;

                $shop->save();
                return true;

        } catch (\Throwable $th) {
            return false;
        }
    }

    public function update($id, $request){

        try {
                $shop               = MerchantShops::where('id',$id)->first();
                $shop->name         = $request->name;
                $shop->contact_no   = $request->contact_no;
                $shop->address      = $request->address;
                $shop->merchant_lat = $request->lat;
                $shop->merchant_long= $request->long;
                $shop->status       = $request->status;
                $shop->save();
                return true;
        } catch (\Throwable $th) {
            return false;
        }

    }

    public function delete($id){
        $shop = MerchantShops::find($id);
        if (! $shop) {
            return 0;
        }

        $wasDefault  = (int) $shop->default_shop === (int) Status::ACTIVE;
        $merchantId  = $shop->merchant_id;
        $removed     = MerchantShops::destroy($id);

        // Removing the default used to leave the merchant with shops but
        // no default at all, which reads to every caller as no pickup
        // point. Promote the oldest survivor instead.
        if ($removed && $wasDefault) {
            $next = MerchantShops::where('merchant_id', $merchantId)->orderBy('id')->first();
            if ($next) {
                $next->default_shop = Status::ACTIVE;
                $next->save();
            }
        }

        return $removed;
    }
    
     public function firstForMerchant(int $merchantId)
    {
        return MerchantShops::where('merchant_id', $merchantId)
            ->where('status', 1)          // متجر نشط
            ->orderByDesc('default_shop') // فضّل الافتراضي إن وجد
            ->orderBy('id')               // وإلا أقدم متجر
            ->first();
    }


}

