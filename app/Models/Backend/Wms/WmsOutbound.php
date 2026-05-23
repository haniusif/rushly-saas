<?php

namespace App\Models\Backend\Wms;

use App\Models\Backend\Hub;
use App\Models\Backend\Merchant;
use App\Models\Backend\Wms\Concerns\Companywise;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class WmsOutbound extends Model
{
    use HasFactory, LogsActivity, SoftDeletes, Companywise;

    protected $table = 'wms_outbound';

    protected $fillable = [
        'company_id', 'outbound_number', 'hub_id', 'merchant_id', 'type',
        'fulfillment_id', 'processed_by', 'status', 'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['outbound_number', 'type', 'status', 'completed_at'])
            ->logOnlyDirty();
    }

    public function hub()         { return $this->belongsTo(Hub::class, 'hub_id'); }
    public function merchant()    { return $this->belongsTo(Merchant::class, 'merchant_id'); }
    public function processedBy() { return $this->belongsTo(User::class, 'processed_by'); }
    public function items()       { return $this->hasMany(WmsOutboundItem::class, 'outbound_id'); }
    public function fulfillment() { return $this->belongsTo(WmsFulfillment::class, 'fulfillment_id'); }
}
