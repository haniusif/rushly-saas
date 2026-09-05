<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Model;
use App\Models\Backend\Emirate;

class City extends Model
{
    protected $table = 'cities';

    // country_id and city_code are real columns on `cities` but were absent
    // here, so mass assignment silently dropped them.
    protected $fillable = [
        'country_id',
        'emirate_id',
        'name',
        'en_name',
        'city_code',
        'sorting',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sorting'   => 'integer',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    /**
     * العلاقة مع الإمارة
     */
    public function emirate()
    {
        return $this->belongsTo(Emirate::class, 'emirate_id');
    }

    /**
     * العلاقة مع المناطق (areas)
     */
    public function areas()
    {
        return $this->hasMany(Area::class, 'city_id');
    }
}
