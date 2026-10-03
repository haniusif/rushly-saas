<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * One bulk shipment import, identified by the content of the uploaded file.
 *
 * See the create migration for why this exists: a slow import that outlives
 * the browser used to be indistinguishable from a failed one, and retrying
 * doubled every shipment in the sheet.
 */
class ParcelImportRun extends Model
{
    protected $table = 'parcel_import_runs';

    protected $fillable = [
        'company_id',
        'merchant_id',
        'file_hash',
        'file_name',
        'row_count',
        'imported_count',
        'status',
        'error',
    ];

    public const RUNNING   = 'running';
    public const COMPLETED = 'completed';
    public const FAILED    = 'failed';

    /** A previous run of this exact file that should block a repeat. */
    public function scopeBlocking(Builder $query): Builder
    {
        // 'failed' is deliberately NOT blocking: if the import genuinely blew
        // up, the merchant must be able to fix nothing and simply try again.
        return $query->whereIn('status', [self::RUNNING, self::COMPLETED]);
    }

    public function scopeCompanywise(Builder $query): Builder
    {
        return $query->where('company_id', settings()->id ?? null);
    }
}
