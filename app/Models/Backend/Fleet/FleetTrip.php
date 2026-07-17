<?php

namespace App\Models\Backend\Fleet;

use Illuminate\Database\Eloquent\Model;

class FleetTrip extends Model
{
    protected $table = 'fleet_trips';

    protected $fillable = [
        'company_id', 'vehicle_id', 'driver_id',
        'start_odometer', 'end_odometer',
        'started_at', 'ended_at',
        'start_lat', 'start_lng', 'end_lat', 'end_lng',
        'start_inspection', 'notes', 'status',
    ];

    protected $casts = [
        'start_inspection' => 'array',
        'started_at'       => 'datetime',
        'ended_at'         => 'datetime',
    ];

    public function scopeCompanywise($query)
    {
        return $query->where($this->getTable() . '.company_id', settings()->id);
    }

    public function vehicle()
    {
        return $this->belongsTo(FleetVehicle::class, 'vehicle_id');
    }

    public function distanceKm(): ?int
    {
        if ($this->end_odometer === null) return null;
        return (int) $this->end_odometer - (int) $this->start_odometer;
    }
}
