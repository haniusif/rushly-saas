<?php

namespace App\Models\Backend;

use App\Models\Backend\Wms\WmsProduct;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParcelItem extends Model
{
    use HasFactory;

    protected $table = 'parcel_items';

    protected $fillable = [
        'parcel_id', 'wms_product_id',
        'sku', 'name', 'quantity', 'unit_price', 'line_total', 'note',
    ];

    protected $casts = [
        'quantity'   => 'integer',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function parcel()
    {
        return $this->belongsTo(Parcel::class, 'parcel_id');
    }

    public function product()
    {
        return $this->belongsTo(WmsProduct::class, 'wms_product_id');
    }
}
