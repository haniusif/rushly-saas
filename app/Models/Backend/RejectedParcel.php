<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Model;

class RejectedParcel extends Model
{
    protected $table = 'rejected_parcels';

    protected $fillable = [
        'parcel_id',
        'rejection_reason_id',
        'comments',
        'attachments',
        'deliveryman_id',
        'created_by_type',
        'created_by',
    ];
    
    
 
    protected $casts = [
        'attachments' => 'array',
    ];

    public function parcel()
    {
        return $this->belongsTo(Parcel::class, 'parcel_id');
    }

    public function reason()
    {
        return $this->belongsTo(RejectionReason::class, 'rejection_reason_id');
    }

    public function deliveryman()
    {
        return $this->belongsTo(DeliveryMan::class, 'deliveryman_id');
    }
}
