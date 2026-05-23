<?php

namespace App\Models\Backend;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AbnormalShipment extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'abnormal_shipments';

    protected $fillable = [
        'parcel_id',
        'company_id',
        'detected_at',
        'last_event_at',
        'stale_days',
        'severity',
        'assigned_to',
        'status',
        'resolution_note',
        'resolved_by',
        'escalated_at',
        'resolved_at',
    ];

    protected $casts = [
        'detected_at'   => 'datetime',
        'last_event_at' => 'datetime',
        'stale_days'    => 'integer',
        'escalated_at'  => 'datetime',
        'resolved_at'   => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'parcel_id', 'stale_days', 'severity', 'status',
                'assigned_to', 'resolved_at',
            ])
            ->logOnlyDirty();
    }

    public function scopeCompanywise($query)
    {
        return $query->where('company_id', settings()->id);
    }

    public function parcel()
    {
        return $this->belongsTo(Parcel::class, 'parcel_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function ndrs()
    {
        return $this->hasMany(Ndr::class, 'abnormal_shipment_id');
    }
}
