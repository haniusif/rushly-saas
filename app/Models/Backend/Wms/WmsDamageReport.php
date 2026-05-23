<?php

namespace App\Models\Backend\Wms;

use App\Models\Backend\Wms\Concerns\Companywise;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class WmsDamageReport extends Model
{
    use HasFactory, LogsActivity, Companywise;

    protected $table = 'wms_damage_reports';

    protected $fillable = [
        'company_id', 'product_id', 'location_id', 'reported_by',
        'quantity_damaged', 'cause', 'photos', 'notes', 'action_taken',
    ];

    protected $casts = [
        'photos'           => 'array',
        'quantity_damaged' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['product_id', 'quantity_damaged', 'cause', 'action_taken'])
            ->logOnlyDirty();
    }

    public function product()    { return $this->belongsTo(WmsProduct::class, 'product_id'); }
    public function location()   { return $this->belongsTo(WmsLocation::class, 'location_id'); }
    public function reportedBy() { return $this->belongsTo(User::class, 'reported_by'); }
}
