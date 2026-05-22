<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Model;

class RejectionReason extends Model
{
    protected $table = 'rejection_reasons';

    protected $fillable = [
        'name',
        'en_name',
    ];

    public $timestamps = true;

    /**
     * العلاقة مع جدول الشحنات المرفوضة
     */
    public function rejectedParcels()
    {
        return $this->hasMany(RejectedParcel::class, 'rejection_reason_id');
    }
}
