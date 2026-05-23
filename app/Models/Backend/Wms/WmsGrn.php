<?php

namespace App\Models\Backend\Wms;

use App\Models\Backend\Hub;
use App\Models\Backend\Merchant;
use App\Models\Backend\Wms\Concerns\Companywise;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class WmsGrn extends Model
{
    use HasFactory, LogsActivity, SoftDeletes, Companywise;

    protected $table = 'wms_grn';

    protected $fillable = [
        'company_id', 'grn_number', 'hub_id', 'merchant_id', 'received_by',
        'reference_number', 'status', 'notes', 'received_at',
    ];

    protected $casts = [
        'received_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['grn_number', 'status', 'received_at'])
            ->logOnlyDirty();
    }

    public function hub()        { return $this->belongsTo(Hub::class, 'hub_id'); }
    public function merchant()   { return $this->belongsTo(Merchant::class, 'merchant_id'); }
    public function receivedBy() { return $this->belongsTo(User::class, 'received_by'); }
    public function items()      { return $this->hasMany(WmsGrnItem::class, 'grn_id'); }

    public function hasDiscrepancy(): bool
    {
        return $this->items->contains(fn ($i) => (int) $i->expected_qty !== (int) $i->received_qty);
    }
}
