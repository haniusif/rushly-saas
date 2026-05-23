<?php

namespace App\Models\Backend\Wms;

use App\Models\Backend\Wms\Concerns\Companywise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WmsStock extends Model
{
    use HasFactory, Companywise;

    protected $table = 'wms_stock';

    protected $fillable = [
        'company_id', 'product_id', 'location_id',
        'quantity', 'reserved_qty',
        'batch_number', 'lot_number', 'expiry_date',
    ];

    protected $casts = [
        'quantity'     => 'integer',
        'reserved_qty' => 'integer',
        'expiry_date'  => 'date',
    ];

    public function product()  { return $this->belongsTo(WmsProduct::class, 'product_id'); }
    public function location() { return $this->belongsTo(WmsLocation::class, 'location_id'); }

    public function getAvailableAttribute(): int
    {
        return max(0, $this->quantity - $this->reserved_qty);
    }
}
