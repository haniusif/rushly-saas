<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ParcelImage extends Model
{
    use HasFactory;

    protected $table = 'parcel_images';

    protected $fillable = [
        'parcel_id',
        'parcel_event_id', // اختياري لو تربط الصورة بالـ event
        'image_path',
        'type',            // delivered | not_delivered | signature
        'created_by'
    ];

    protected $casts = [
        'parcel_id' => 'integer',
        'parcel_event_id' => 'integer',
    ];

    /* ============================================
       RELATIONS
    ============================================ */

    public function parcel()
    {
        return $this->belongsTo(Parcel::class);
    }

    public function event()
    {
        return $this->belongsTo(ParcelEvent::class, 'parcel_event_id');
    }

    /* ============================================
       ACCESSOR
    ============================================ */

    public function getImageUrlAttribute()
    {
        return asset('storage/' . $this->image_path);
    }
}