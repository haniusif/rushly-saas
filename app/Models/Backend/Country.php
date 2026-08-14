<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $table = 'countries';

    protected $fillable = [
        'code',
        'name',
        'en_name',
        'sorting',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sorting'   => 'integer',
    ];

    public function merchants()
    {
        return $this->belongsToMany(Merchant::class, 'merchant_countries', 'country_id', 'merchant_id')
            ->withTimestamps();
    }
}
