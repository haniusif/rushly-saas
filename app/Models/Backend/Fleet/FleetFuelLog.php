<?php

namespace App\Models\Backend\Fleet;

use Illuminate\Database\Eloquent\Model;

class FleetFuelLog extends Model
{
    protected $table = 'fleet_fuel_logs';

    protected $fillable = [
        'company_id', 'vehicle_id', 'driver_id',
        'liters', 'cost', 'odometer_reading',
        'receipt_url', 'filled_at', 'notes',
    ];

    protected $casts = [
        'liters'    => 'decimal:2',
        'cost'      => 'decimal:2',
        'filled_at' => 'datetime',
    ];

    public function scopeCompanywise($query)
    {
        return $query->where($this->getTable() . '.company_id', settings()->id);
    }

    public function vehicle()
    {
        return $this->belongsTo(FleetVehicle::class, 'vehicle_id');
    }
}
