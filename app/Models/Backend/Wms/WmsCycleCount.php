<?php

namespace App\Models\Backend\Wms;

use App\Models\Backend\Hub;
use App\Models\Backend\Wms\Concerns\Companywise;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class WmsCycleCount extends Model
{
    use HasFactory, LogsActivity, Companywise;

    protected $table = 'wms_cycle_counts';

    protected $fillable = [
        'company_id', 'count_number', 'hub_id', 'assigned_to', 'scope',
        'zone', 'status', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['count_number', 'scope', 'status', 'completed_at'])
            ->logOnlyDirty();
    }

    public function hub()        { return $this->belongsTo(Hub::class, 'hub_id'); }
    public function assignedTo() { return $this->belongsTo(User::class, 'assigned_to'); }
}
