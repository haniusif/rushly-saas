<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Model;

class UserTourProgress extends Model
{
    protected $table = 'user_tour_progress';

    protected $fillable = [
        'user_id', 'company_id', 'tour_key', 'tour_version',
        'status', 'current_step', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'tour_version' => 'integer',
        'current_step' => 'integer',
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    public const STATUS_STARTED   = 'started';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_SKIPPED   = 'skipped';
    public const STATUS_DISMISSED = 'dismissed';

    public function scopeCompanywise($query)
    {
        return $query->where('company_id', settings()->id);
    }
}
