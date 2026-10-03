<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Parcels_3pl extends Model
{
    // Table & PK
    protected $table = 'parcels_3pl';   // matches your table
    protected $primaryKey = 'id';
    public $timestamps = true;

    // Mass-assignable fields
    protected $fillable = [
        'company_id',           // Phase 9 — tenant scope
        'parcel_id',
        'parcel_3pl_name',
        'target_company_id',   // Logestechs (and similar) — caller-picked routing id
        'awb_number',
        'awb_pdf',
        'response',
        'current_status',      // written by cron / webhook syncs
        'status_datetime',
    ];

    // Cast JSON response to array automatically
    protected $casts = [
        'response'        => 'array',
        'status_datetime' => 'datetime',
    ];

    // Relationships
    public function parcel()
    {
        return $this->belongsTo(Parcel::class, 'parcel_id');
    }

    /**
     * Tenant scope — matches the codebase-wide `companywise` convention.
     * Uses settings()->id so it works in both HTTP and queue/scheduler
     * contexts. Sync jobs (Aramex/Jet/Panda/Zajel) currently run
     * unscoped — those callers should adopt this scope in a follow-up
     * pass (the column + scope are in place as of Phase 9).
     */
    public function scopeCompanywise(Builder $query): Builder
    {
        $id = settings()->id ?? null;
        return $query->where('company_id', $id);
    }

    /**
     * Phase 9 — auto-populate `company_id` from the linked parcel on
     * save. Rather than patch every one of the 15+ `Parcels_3pl::create`
     * call sites across the codebase (in ParcelController + the god-
     * method in ParcelBulkActionController), we resolve the tenant
     * once here.
     *
     * The saving() hook runs on both create + update. It respects an
     * explicit company_id if the caller already set one (bulk-import,
     * cross-tenant admin tools). Only fills the gap when it's missing.
     * Falls through silently when parcel_id is null or the parcel
     * doesn't resolve — the leak-fix goal is best-effort backfill, not
     * to block writes.
     */
    protected static function booted(): void
    {
        static::saving(function (Parcels_3pl $row) {
            if ($row->company_id !== null) {
                return;
            }
            if (! $row->parcel_id) {
                return;
            }
            try {
                // withoutGlobalScopes on Parcel so we resolve the tenant
                // even from CLI / cross-tenant contexts. We only read
                // company_id — no side effects.
                $companyId = Parcel::withoutGlobalScopes()
                    ->where('id', $row->parcel_id)
                    ->value('company_id');
                if ($companyId !== null) {
                    $row->company_id = (int) $companyId;
                }
            } catch (\Throwable) {
                // Best-effort; don't block the save.
            }
        });
    }
}
