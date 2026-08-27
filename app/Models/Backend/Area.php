<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Model;
use App\Models\Backend\City;

class Area extends Model
{
    protected $table = 'areas';

    // area_code and region_id are real columns on `areas` but were absent
    // here, so mass assignment silently dropped them.
    protected $fillable = [
        'city_id',
        'region_id',
        'name',
        'en_name',
        'area_code',
        'sorting',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sorting'   => 'integer',
    ];

    /**
     * العلاقة مع المدينة
     */
    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }
}
