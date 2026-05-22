<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Model;
use App\Models\Backend\Emirate;

class City extends Model
{
    protected $table = 'cities';

    protected $fillable = [
        'emirate_id',
        'name',
        'en_name',
        'sorting',
        'is_active',
    ];

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
