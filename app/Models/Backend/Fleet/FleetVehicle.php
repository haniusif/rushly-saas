<?php

namespace App\Models\Backend\Fleet;

use Illuminate\Database\Eloquent\Model;

class FleetVehicle extends Model
{
    protected $table = 'fleet_vehicles';

    protected $fillable = [
        'company_id', 'plate_number', 'make', 'model', 'year',
        'vehicle_type', 'status', 'current_odometer',
        'assigned_driver_id', 'hub_id', 'notes',
    ];

    public function scopeCompanywise($query)
    {
        return $query->where($this->getTable() . '.company_id', settings()->id);
    }
}
