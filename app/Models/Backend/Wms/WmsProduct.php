<?php

namespace App\Models\Backend\Wms;

use App\Models\Backend\Hub;
use App\Models\Backend\Merchant;
use App\Models\Backend\Wms\Concerns\Companywise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class WmsProduct extends Model
{
    use HasFactory, LogsActivity, SoftDeletes, Companywise;

    protected $table = 'wms_products';

    protected $fillable = [
        'company_id', 'merchant_id', 'hub_id', 'name', 'sku', 'barcode',
        'description', 'category', 'weight', 'dimensions', 'unit',
        'reorder_point', 'track_expiry', 'is_active',
    ];

    protected $casts = [
        'dimensions'    => 'array',
        'weight'        => 'decimal:3',
        'reorder_point' => 'integer',
        'track_expiry'  => 'boolean',
        'is_active'     => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['sku', 'name', 'reorder_point', 'is_active', 'hub_id'])
            ->logOnlyDirty();
    }

    public function merchant() { return $this->belongsTo(Merchant::class, 'merchant_id'); }
    public function hub()      { return $this->belongsTo(Hub::class, 'hub_id'); }
    public function stocks()   { return $this->hasMany(WmsStock::class, 'product_id'); }

    /** Total on-hand across all locations + batches. */
    public function getTotalQtyAttribute(): int
    {
        return (int) $this->stocks()->sum('quantity');
    }

    /** Total reserved (held for active fulfillments). */
    public function getReservedQtyAttribute(): int
    {
        return (int) $this->stocks()->sum('reserved_qty');
    }

    public function getAvailableQtyAttribute(): int
    {
        return max(0, $this->total_qty - $this->reserved_qty);
    }

    public function isLowStock(): bool
    {
        return $this->total_qty <= (int) $this->reorder_point;
    }
}
