<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Model;

class Parcels_3pl extends Model
{
    // Table & PK
    protected $table = 'parcels_3pl';   // matches your table
    protected $primaryKey = 'id';
    public $timestamps = true;

    // Mass-assignable fields
    protected $fillable = [
        'parcel_id',
        'parcel_3pl_name',
        'awb_number',
        'awb_pdf',
        'response',
    ];

    // Cast JSON response to array automatically
    protected $casts = [
        'response' => 'array',
    ];

    // Relationships
    public function parcel()
    {
        // 'parcels_id' is your FK in parcels_3pl
        return $this->belongsTo(Parcel::class, 'parcel_id');
    }
}
