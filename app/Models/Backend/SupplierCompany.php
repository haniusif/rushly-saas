<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierCompany extends Model
{
    use HasFactory;

    protected $fillable = ['company_id', 'name', 'contact_phone', 'status'];

    public function scopeCompanywise($query)
    {
        return $query->where('company_id', settings()->id);
    }

    public function deliverymen()
    {
        return $this->hasMany(DeliveryMan::class, 'supplier_company_id');
    }
}
