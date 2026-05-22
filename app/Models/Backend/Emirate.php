<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Model;
use App\Models\Backend\City;

class Emirate extends Model
{
    protected $table = 'emirates';

    protected $fillable = [
        'name',
        'en_name',
    ];

    /**
     * العلاقة مع المدن
     */
    public function cities()
    {
        return $this->hasMany(City::class, 'emirate_id');
    }
}
