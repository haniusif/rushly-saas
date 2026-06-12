<?php

namespace App\Models\Backend;


use App\Models\MerchantShops;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Models\User;
use App\Models\Backend\Deliverycategory;
use App\Models\Backend\Packaging;
use App\Enums\ParcelStatus;
use App\Support\ParcelStatusHelper;
use App\Enums\DeliveryType;
use App\Models\Backend\Merchantpanel\Invoice;
use DNS1D;
use DNS2D;
use Illuminate\Support\Facades\Auth;

class Parcel extends Model
{
    
 
     
    use HasFactory, LogsActivity;

    /**
     * Transient cancel reason — set by ::cancelShipment() before save so the
     * `updated` model hook can include it as the ParcelEvent log note. Not a
     * DB column; resets per model instance.
     */
    public ?string $cancellationReason = null;

    protected $fillable = [
        'company_id' , 'merchant_id', 'merchant_shop_id', 'pickup_address', 'pickup_phone', 'customer_name', 'customer_phone', 'awb_label' ,
        'customer_address', 'invoice_no', 'category_id', 'weight', 'delivery_type_id', 'pickup_date', 'delivery_date', 'packaging_id','cash_collection','first_hub_id','hub_id',
        'selling_price','liquid_fragile_amount','packaging_amount','delivery_charge','cod_charge','cod_amount','reference_number',
        'vat','vat_amount','total_delivery_amount','current_payable','note','tracking_id','status','created_at','updated_at','pickup_lat','pickup_long','customer_lat','customer_long' , 'city_id' , 'area_id' , 'number_of_attempts' , 'number_of_boxes', 'reschedule_area_id' ,  'additional_phone' , 'reschedule_delivery_time' , 'reschedule_delivery_date'
    ];

    protected $table = 'parcels';
    public function scopeOrderByDesc($query, $data)
    {
        $query->orderBy($data, 'desc');
    }
    
    
    protected static function booted()
{
    /*
    |--------------------------------------------------------------------------
    | Tenant isolation (global scope)
    |--------------------------------------------------------------------------
    | Every Eloquent query against Parcel (find, where, ::all, eager loads
    | via Eloquent — anything that goes through the query builder) is
    | automatically constrained to the current tenant's company_id. This
    | closes a class of leaks where a caller did `Parcel::find($id)` with
    | $id coming from the URL or request payload, bypassing the local
    | scopeCompanywise() scope.
    |
    | Guards (skip the scope when ANY of these is true):
    |  - tenant() is null         (CLI, artisan, jobs, queue workers, tinker,
    |                              cron, scheduler, central-domain requests).
    |    The settings() helper falls back to id=1 in those contexts and that
    |    fallback would silently clamp every CLI query to tenant 1 — worse
    |    than no scope at all.
    |  - tenant()->company_id is null  (defensive — half-resolved tenant).
    |  - Authenticated user is SUPER_ADMIN (cross-tenant access by design).
    |
    | Escape hatch:
    |    Parcel::withoutGlobalScope('tenant')->find($id)
    | Use this for super-admin tools, cross-tenant analytics, or webhook
    | callbacks that look up by carrier reference rather than by trust.
    |
    | The legacy scopeCompanywise() local scope is retained for backward
    | compatibility — calls like Parcel::companywise()->... still work,
    | they're just redundant now.
    */
    static::addGlobalScope('tenant', function ($query) {
        // stancl/tenancy resolves tenant() during the request. If it's
        // null, we're outside any tenant HTTP context — skip the scope.
        if (!function_exists('tenant') || !tenant() || !tenant()->company_id) {
            return;
        }
        // Super-admins have explicit cross-tenant authority.
        if (\Illuminate\Support\Facades\Auth::check()
            && (int) \Illuminate\Support\Facades\Auth::user()->user_type === \App\Enums\UserType::SUPER_ADMIN) {
            return;
        }
        $table = (new static())->getTable();
        $query->where($table . '.company_id', (int) tenant()->company_id);
    });

    static::updating(function ($parcel) {
        // Once a shipment is CANCELLED, no further updates of any kind are
        // allowed. Returning false aborts the save. Callers should check
        // isCancelled() before attempting any change.
        if ((int) $parcel->getOriginal('status') === ParcelStatus::CANCELLED) {
            return false;
        }

        // Check if status is being updated AND the new status is different
        if ($parcel->isDirty('status') && $parcel->status == ParcelStatus::DELIVERY_MAN_ASSIGN) {

            $parcel->number_of_attempts = $parcel->number_of_attempts + 1;

        }


    });

    // Universal cancellation logger: whenever ANY path flips status to
    // CANCELLED (single-parcel cancel button, bulk cancel, direct status
    // update, API, etc.), record a ParcelEvent so it shows in the timeline.
    // Best-effort: a logging failure must not undo the cancel — the row
    // status update has already committed by the time `updated` fires.
    static::updated(function ($parcel) {
        if (! $parcel->wasChanged('status') || (int) $parcel->status !== ParcelStatus::CANCELLED) {
            return;
        }
        try {
            $event                = new ParcelEvent();
            $event->parcel_id     = $parcel->id;
            $event->parcel_status = ParcelStatus::CANCELLED;
            $event->note          = $parcel->cancellationReason;
            $event->created_by    = Auth::id();
            $event->save();
        } catch (\Throwable $e) {
            logger()->warning('Cancelled shipment event-log failed', [
                'parcel_id' => $parcel->id,
                'error'     => $e->getMessage(),
            ]);
        }
    });
}

    public function isCancelled(): bool
    {
        return (int) $this->status === ParcelStatus::CANCELLED;
    }

    /**
     * A shipment can only be cancelled while still in the "Created" state.
     * Once it's been picked up, assigned to a courier, etc., cancellation
     * requires going through the normal return/refund flow instead.
     */
    public function isCancellable(): bool
    {
        return (int) $this->status === ParcelStatus::PENDING;
    }

    public function cancelShipment(?string $reason = null): bool
    {
        if (! $this->isCancellable()) {
            return false;
        }
        $this->status = ParcelStatus::CANCELLED;
        if ($reason !== null && $reason !== '') {
            $this->note = trim((string) $this->note.' | Cancelled: '.$reason);
        }
        // Hand the reason to the `updated` hook in booted() so it gets stored
        // as the note on the ParcelEvent timeline entry.
        $this->cancellationReason = $reason ?: null;
        return (bool) $this->save();
    }


    /**
    * Activity Log
    */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('parcel')
        ->logOnly(['merchant.business_name','pickup_address','pickup_phone','customer_name','customer_phone','customer_address','invoice_no','cash_collection','selling_price','delivery_charge','total_delivery_amount','current_payable'])
        ->setDescriptionForEvent(fn(string $eventName) => "{$eventName}");
    }

    // Merchant details
    public function merchant()
    {
        return $this->belongsTo(Merchant::class)->with('user');
    }

    // Merchant shop details
    public function merchantShop()
    {
        return $this->belongsTo(MerchantShops::class, 'merchant_shop_id', 'id');
    }

    // Delivery Category details
    public function deliveryCategory()
    {
        return $this->belongsTo(Deliverycategory::class, 'category_id', 'id');
    }

    // Delivery Category details
    public function packaging()
    {
        return $this->belongsTo(Packaging::class);
    }
    public function shop()
    {
        return $this->belongsTo(MerchantShops::class,'merchant_shop_id','id');
    }
    public function parcelEvent()
    {
        return $this->hasMany(ParcelEvent::class,'parcel_id','id');
    }
    
    public function lastParcelEvent()
{
    return $this->hasOne(ParcelEvent::class, 'parcel_id', 'id')->latest('id'); 
}


    public function deliverymanStatement()
    {
        return $this->hasMany(DeliverymanStatement::class,'parcel_id','id');
    }
    
     public function parcels_3pl()
    {
        return $this->hasMany(Parcels_3pl::class,'parcel_id','id');
    }
    
      public function rejected_parcels()
    {
        return $this->hasMany(RejectedParcel::class,'parcel_id','id');
    }
    
    
    
    public function lastParcel3pl()
{
    return $this->hasOne(Parcels_3pl::class, 'parcel_id', 'id')->latest();
}


 


public function lastDeliveryMan()
{
    return $this->hasOne(ParcelEvent::class, 'parcel_id', 'id')
                ->whereNotNull('delivery_man_id')
                ->latest('id');
}

public function lastPickupMan()
{
    return $this->hasOne(ParcelEvent::class, 'parcel_id', 'id')
                ->whereNotNull('pickup_man_id')
                ->latest('id');
}



 




    public function getMyItemTypeAttribute()
    {
        $itemType = '';
        foreach (trans("parcelType") as $key =>$value){
            if($this->item_type == $key){
                $itemType = $value;
            }
        }
        return $itemType;
    }

    public function getMyDeliveryTypeAttribute()
    {
        $deliveryType = '';
        foreach (trans("DeliveryType") as $key =>$value){
            if($this->delivery_type == $key){
                $deliveryType = $value;
            }
        }
        return $deliveryType;
    }



    /**
     * Status badge for $parcel->parcel_status — delegates to ParcelStatusHelper
     * so every status uses its custom color from the helper's $colorMap.
     * Adds a small hub-transfer chip for TRANSFER_TO_HUB.
     */
    public function getParcelStatusAttribute(): string
    {
        $html = $this->renderStatusBadge((int) $this->status);

        if ((int) $this->status === ParcelStatus::TRANSFER_TO_HUB && $this->hub && $this->transferhub) {
            $html .= '<br><span class="badge badge-pill badge-danger mt-1">'
                . e($this->hub->name) . ' To ' . e($this->transferhub->name) . '</span>';
        }
        return $html;
    }

    /**
     * Status badge for an arbitrary status id (used by views that need to render
     * a status they didn't load via $parcel->parcel_status).
     */
    public function getStatusParcelAttribute($status_id): string
    {
        return $this->renderStatusBadge((int) $status_id);
    }

    /**
     * Single render path. The custom per-status color comes from the CSS rules
     * emitted by ParcelStatusHelper::styleBlock() and targeted by the
     * `parcel-status-N` class returned by ::badgeClass().
     */
    protected function renderStatusBadge(int $statusId): string
    {
        $label = match ($statusId) {
            ParcelStatus::PENDING             => 'Created',
            ParcelStatus::DELIVERY_MAN_ASSIGN => 'OFD',
            ParcelStatus::RETURN_WAREHOUSE    => 'RTO',
            ParcelStatus::RETURNED_MERCHANT   => 'RTC',
            default                           => trans('parcelStatus.' . $statusId),
        };

        return '<span class="' . ParcelStatusHelper::badgeClass($statusId) . ' badge-pill">'
            . e($label) . '</span>';
    }

    public function getDeliveryTypeNameAttribute()
    {
        $delivery_type_id = trans("deliveryType." . $this->delivery_type_id);

        if($this->delivery_type_id == DeliveryType::SAMEDAY){
            $delivery_type_id = trans("deliveryType." . $this->delivery_type_id);
        }
        elseif($this->delivery_type_id == DeliveryType::NEXTDAY){
            $delivery_type_id = trans("deliveryType." . $this->delivery_type_id);
        }
        elseif($this->delivery_type_id == DeliveryType::SUBCITY){
            $delivery_type_id = trans("deliveryType." . $this->delivery_type_id);
        }
        elseif($this->delivery_type_id == DeliveryType::OUTSIDECITY){
            $delivery_type_id = trans("deliveryType." . $this->delivery_type_id);
        }
        return $delivery_type_id;
    }


    public function hub(){
        return $this->belongsTo(Hub::class,'hub_id','id');
    }
    public function transferhub(){
        return $this->belongsTo(Hub::class,'transfer_hub_id','id');
    }

    public function getBarcodePrintAttribute()
    {
        return DNS1D::getBarcodeHTML($this->tracking_id, 'C128',2,25); 
    }
    
    
      public function getQrcodeIdPrintAttribute()
    {
        return 'data:image/png;base64,' .DNS2D::getBarcodePNG("$this->id", 'QRCODE',10,10,array(1,1,1),false);
    }
   

    public function getQrcodePrintAttribute()
    {
        return 'data:image/png;base64,' .DNS2D::getBarcodePNG(url('/',$this->tracking_id), 'QRCODE',10,10,array(1,1,1),false);
    }

    public function getStatusNameAttribute(){
        return __('parcelStatus.'.$this->status);
    }

    public function getParcelInvoiceAttribute(){
        $invoice   = Invoice::where('merchant_id',Auth::user()->merchant->id)->get();
        $inv  = null;
        foreach ($invoice as $in) {
            if(in_array($this->id,$in->parcels_id) == true):
                $inv  = $in;
            endif;
        }
        return $inv;
    }

    public function getAdminParcelInvoiceAttribute(){
        $invoice   = Invoice::where('merchant_id',$this->merchant_id)->get();
        $inv  = null;
        foreach ($invoice as $in) {
            if(in_array($this->id,$in->parcels_id) == true):
                $inv  = $in;
            endif;
        }
        return $inv;
    }

    public function scopeCompanywise($query){
        return $query->where('company_id',settings()->id);
    }
    
    
    public function city()
{
    return $this->belongsTo(City::class);
}

public function area()
{
    return $this->belongsTo(Area::class);
}

public function images()
{
    return $this->hasMany(ParcelImage::class);
}

}
