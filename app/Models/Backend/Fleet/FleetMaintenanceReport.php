<?php

namespace App\Models\Backend\Fleet;

use Illuminate\Database\Eloquent\Model;

class FleetMaintenanceReport extends Model
{
    protected $table = 'fleet_maintenance_reports';

    protected $fillable = [
        'company_id', 'vehicle_id', 'driver_id',
        'issue_type', 'severity', 'description',
        'status', 'reported_at', 'resolved_at', 'resolution_notes',
    ];

    protected $casts = [
        'reported_at' => 'datetime',
        'resolved_at' => 'datetime',
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
