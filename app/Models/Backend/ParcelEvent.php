<?php

namespace App\Models\Backend;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ParcelEvent extends Model
{
    use HasFactory;

    protected $casts = [
        'delivered_images' => 'array',
    ];

    // `company_id` was added by the 2026_06_27_000002 migration so events can
    // be filtered without joining parcels. Adding it to fillable lets the
    // existing ParcelEvent::create([...]) callers pass it through; the
    // `creating` hook below auto-populates it when they don't.
    protected $fillable = [
        'parcel_id', 'company_id', 'delivery_man_id', 'pickup_man_id', 'hub_id',
        'transfer_delivery_man_id', 'note', 'parcel_status',
        'delivery_lat', 'delivery_long',
        'signature_image', 'delivered_image', 'delivered_images',
        'created_by', 'rejection_reason_id',
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
     * Boot hooks:
     *   - tenant global scope: every query auto-filters by company_id when a
     *     tenant context is active. Mirrors the Parcel model's `tenant` scope.
     *   - creating hook: populates company_id from the parent parcel (or the
     *     current tenant as a fallback) so existing callers that build events
     *     via `new ParcelEvent()` keep working without touching them.
     *   - created hook: legacy AbnormalShipment auto-resolve (kept as-is).
     */
    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (!function_exists('tenant') || !tenant() || !tenant()->company_id) {
                return;
            }
            $table = $query->getModel()->getTable();
            $query->where($table . '.company_id', (int) tenant()->company_id);
        });

        static::creating(function (ParcelEvent $event) {
            if ($event->company_id) {
                return;
            }
            // Prefer deriving from the parent parcel — it's authoritative
            // and stays correct even if the request didn't come through a
            // tenant-resolving HTTP path (queue jobs, console commands).
            if ($event->parcel_id) {
                $event->company_id = DB::table('parcels')
                    ->where('id', $event->parcel_id)
                    ->value('company_id');
            }
            // Last-resort fallback: current tenant context. Only kicks in if
            // the parent parcel can't be found (shouldn't happen in practice).
            if (! $event->company_id
                && function_exists('tenant') && tenant() && tenant()->company_id
            ) {
                $event->company_id = (int) tenant()->company_id;
            }
        });

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
