<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Model;
use App\Models\Backend\City;

class Area extends Model
{
    protected $table = 'areas';

    protected $fillable = [
        'city_id',
        'name',
        'en_name',
        'sorting',
        'is_active',
    ];

    /**
     * العلاقة مع المدينة
     */
    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }
}
