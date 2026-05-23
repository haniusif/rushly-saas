<?php

namespace App\Models\Backend;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Ndr extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'ndrs';

    protected $fillable = [
        'parcel_id',
        'company_id',
        'deliveryman_id',
        'created_by',
        'attempt_number',
        'failure_reason',
        'driver_notes',
        'driver_photo',
        'customer_notified',
        'action_taken',
        'next_attempt_date',
        'resolved_by',
        'resolved_at',
        'status',
        'abnormal_shipment_id',
    ];

    protected $casts = [
        'customer_notified' => 'boolean',
        'next_attempt_date' => 'date',
        'resolved_at'       => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'parcel_id', 'attempt_number', 'failure_reason',
                'action_taken', 'status', 'resolved_at',
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

    public function deliveryman()
    {
        return $this->belongsTo(User::class, 'deliveryman_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function abnormalShipment()
    {
        return $this->belongsTo(AbnormalShipment::class, 'abnormal_shipment_id');
    }
}
