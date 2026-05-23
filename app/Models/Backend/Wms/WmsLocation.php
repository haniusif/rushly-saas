<?php

namespace App\Models\Backend\Wms;

use App\Models\Backend\Hub;
use App\Models\Backend\Wms\Concerns\Companywise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class WmsLocation extends Model
{
    use HasFactory, LogsActivity, Companywise;

    protected $table = 'wms_locations';

    protected $fillable = [
        'company_id', 'hub_id', 'zone', 'aisle', 'rack', 'shelf', 'bin',
        'code', 'type', 'capacity', 'is_active',
    ];

    protected $casts = [
        'capacity'  => 'integer',
        'is_active' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['code', 'zone', 'aisle', 'rack', 'shelf', 'type', 'is_active'])
            ->logOnlyDirty();
    }

    public function hub()    { return $this->belongsTo(Hub::class, 'hub_id'); }
    public function stocks() { return $this->hasMany(WmsStock::class, 'location_id'); }

    /** Auto-generate `code` from zone/aisle/rack/shelf if not provided. */
    public static function buildCode(array $parts): string
    {
        return collect($parts)
            ->filter()
            ->map(fn ($p) => strtoupper(preg_replace('/\s+/', '', (string) $p)))
            ->implode('-');
    }
}
