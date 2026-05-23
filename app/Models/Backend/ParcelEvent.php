<?php

namespace App\Models\Backend;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ParcelEvent extends Model
{
    
    
    use HasFactory;
    
    protected $casts = [
    'delivered_images' => 'array',
];


    public function deliveryMan(){
        return $this->belongsTo(DeliveryMan::class,'delivery_man_id','id')->with(['user']);
    }
    public function pickupman(){
        return $this->belongsTo(DeliveryMan::class,'pickup_man_id','id')->with(['user']);
    }
    public function transferDeliveryman(){
        return $this->belongsTo(DeliveryMan::class,'transfer_delivery_man_id','id');
    }
    public function hub(){
        return $this->belongsTo(Hub::class,'hub_id','id');
    }
    public function user(){
        return $this->belongsTo(User::class,'created_by','id');
    }

    public function parcel(){
        return $this->belongsTo(Parcel::class,'parcel_id','id');
    }

    /**
     * Cross-module hook: a new event proves the parcel is no longer stalled,
     * so any open AbnormalShipment for it should auto-resolve. We resolve the
     * row directly (raw query) to avoid the companywise() scope needing tenant
     * context inside the model boot — events fire from anywhere (HTTP, API,
     * console commands).
     */
    protected static function booted(): void
    {
        static::created(function (ParcelEvent $event) {
            try {
                AbnormalShipment::where('parcel_id', $event->parcel_id)
                    ->whereNotIn('status', ['resolved', 'closed_lost'])
                    ->update([
                        'status'          => 'resolved',
                        'resolved_at'     => now(),
                        'resolution_note' => 'Auto-resolved: new parcel event ('.$event->parcel_status.') recorded.',
                    ]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('AbnormalShipment auto-resolve failed', [
                    'parcel_id' => $event->parcel_id,
                    'event_id'  => $event->id,
                    'error'     => $e->getMessage(),
                ]);
            }
        });
    }
}
