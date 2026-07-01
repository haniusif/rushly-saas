<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Model;

class TourEvent extends Model
{
    protected $table = 'tour_events';

    /**
     * Append-only. We set created_at explicitly on insert and disable
     * updated_at to keep the table cheap.
     */
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'company_id', 'tour_key', 'event',
        'step_index', 'duration_ms', 'meta', 'created_at',
    ];

    protected $casts = [
        'meta'        => 'array',
        'step_index'  => 'integer',
        'duration_ms' => 'integer',
        'created_at'  => 'datetime',
    ];

    public const EVENT_STARTED         = 'started';
    public const EVENT_STEP_FORWARD    = 'step_forward';
    public const EVENT_STEP_BACK       = 'step_back';
    public const EVENT_SKIPPED         = 'skipped';
    public const EVENT_COMPLETED       = 'completed';
    public const EVENT_DISMISSED       = 'dismissed';
    public const EVENT_ELEMENT_MISSING = 'element_missing';

    public function scopeCompanywise($query)
    {
        return $query->where('company_id', settings()->id);
    }
}
