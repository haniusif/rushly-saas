<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Model;

class Nationality extends Model
{
    protected $table = 'nationalities';

    protected $fillable = [
        'name', 'en_name', 'code', 'sorting', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sorting'   => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
