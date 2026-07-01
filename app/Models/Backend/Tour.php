<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Tour extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'tours';

    protected $fillable = [
        'company_id', 'key', 'module', 'title', 'description',
        'role_scope', 'meta', 'version', 'is_active', 'auto_start', 'trigger_route',
    ];

    protected $casts = [
        'role_scope' => 'array',
        'meta'       => 'array',
        'is_active'  => 'boolean',
        'auto_start' => 'boolean',
        'version'    => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('Tour')
            ->logOnly(['key', 'module', 'title', 'is_active', 'version', 'auto_start'])
            ->setDescriptionForEvent(fn ($e) => "Tour {$e}");
    }

    public function steps(): HasMany
    {
        return $this->hasMany(TourStep::class)->orderBy('sort_order');
    }

    /**
     * Company-scoped OR system (null) tours. Matches how the tour resolver
     * merges: tenant overrides win, otherwise fall back to system defaults.
     */
    public function scopeCompanywise($query)
    {
        return $query->where(function ($q) {
            $q->where('company_id', settings()->id)
              ->orWhereNull('company_id');
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  int|null $userType — UserType enum value
     */
    public function scopeForRole($query, ?int $userType)
    {
        if ($userType === null) return $query;
        return $query->where(function ($q) use ($userType) {
            $q->whereNull('role_scope')
              ->orWhereRaw('JSON_CONTAINS(role_scope, ?)', [json_encode($userType)]);
        });
    }
}
