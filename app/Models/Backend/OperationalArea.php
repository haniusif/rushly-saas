<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperationalArea extends Model
{
    use HasFactory;

    protected $fillable = ['company_id', 'name', 'code', 'status'];

    public function scopeCompanywise($query)
    {
        return $query->where('company_id', settings()->id);
    }

    public function deliverymen()
    {
        return $this->hasMany(DeliveryMan::class, 'operational_area_id');
    }

    public function getMyStatusAttribute()
    {
        return $this->status == 1
            ? '<span class="badge badge-pill badge-success">' . trans('status.1') . '</span>'
            : '<span class="badge badge-pill badge-danger">'  . trans('status.2') . '</span>';
    }
}
