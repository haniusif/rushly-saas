<?php

namespace App\Models\Backend\Wms;

use App\Models\Backend\Hub;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\Backend\Wms\Concerns\Companywise;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class WmsFulfillment extends Model
{
    use HasFactory, LogsActivity, SoftDeletes, Companywise;

    protected $table = 'wms_fulfillments';

    protected $fillable = [
        'company_id', 'fulfillment_number', 'parcel_id', 'hub_id', 'merchant_id',
        'status', 'picker_id', 'packer_id', 'picked_at', 'packed_at',
        'dispatched_at', 'sla_deadline', 'notes',
    ];

    protected $casts = [
        'picked_at'     => 'datetime',
        'packed_at'     => 'datetime',
        'dispatched_at' => 'datetime',
        'sla_deadline'  => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'fulfillment_number', 'status', 'picker_id', 'packer_id',
                'picked_at', 'packed_at', 'dispatched_at',
            ])
            ->logOnlyDirty();
    }

    public function parcel()   { return $this->belongsTo(Parcel::class, 'parcel_id'); }
    public function hub()      { return $this->belongsTo(Hub::class, 'hub_id'); }
    public function merchant() { return $this->belongsTo(Merchant::class, 'merchant_id'); }
    public function picker()   { return $this->belongsTo(User::class, 'picker_id'); }
    public function packer()   { return $this->belongsTo(User::class, 'packer_id'); }
    public function items()    { return $this->hasMany(WmsFulfillmentItem::class, 'fulfillment_id'); }

    public function isSlaBreached(): bool
    {
        return $this->sla_deadline && $this->sla_deadline->isPast()
            && !in_array($this->status, ['dispatched', 'cancelled']);
    }
}
