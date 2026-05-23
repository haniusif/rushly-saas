<?php

namespace App\Models\Backend\Wms;

use App\Models\Backend\Wms\Concerns\Companywise;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class WmsAdjustment extends Model
{
    use HasFactory, LogsActivity, Companywise;

    protected $table = 'wms_adjustments';

    protected $fillable = [
        'company_id', 'product_id', 'location_id', 'adjusted_by',
        'quantity_before', 'quantity_after', 'quantity_change',
        'reason', 'reference', 'photo', 'notes',
        'approval_status', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'quantity_before' => 'integer',
        'quantity_after'  => 'integer',
        'quantity_change' => 'integer',
        'approved_at'     => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'product_id', 'location_id', 'quantity_change',
                'reason', 'approval_status',
            ])
            ->logOnlyDirty();
    }

    public function product()    { return $this->belongsTo(WmsProduct::class, 'product_id'); }
    public function location()   { return $this->belongsTo(WmsLocation::class, 'location_id'); }
    public function adjustedBy() { return $this->belongsTo(User::class, 'adjusted_by'); }
    public function approvedBy() { return $this->belongsTo(User::class, 'approved_by'); }

    /** % of the before-quantity (absolute). Used by dual-approval gate (≥20%). */
    public function getPercentChangeAttribute(): float
    {
        $before = (int) $this->quantity_before;
        if ($before === 0) {
            return $this->quantity_change == 0 ? 0.0 : 100.0;
        }
        return round(abs($this->quantity_change) / $before * 100, 2);
    }
}
