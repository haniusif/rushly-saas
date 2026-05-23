<?php

namespace App\Models\Backend\Wms;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WmsFulfillmentItem extends Model
{
    use HasFactory;

    protected $table = 'wms_fulfillment_items';

    protected $fillable = [
        'fulfillment_id', 'product_id', 'location_id',
        'quantity_required', 'quantity_picked', 'status',
    ];

    protected $casts = [
        'quantity_required' => 'integer',
        'quantity_picked'   => 'integer',
    ];

    public function fulfillment() { return $this->belongsTo(WmsFulfillment::class, 'fulfillment_id'); }
    public function product()     { return $this->belongsTo(WmsProduct::class, 'product_id'); }
    public function location()    { return $this->belongsTo(WmsLocation::class, 'location_id'); }
}
