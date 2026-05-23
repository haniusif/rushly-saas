<?php

namespace App\Models\Backend\Wms;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WmsOutboundItem extends Model
{
    use HasFactory;

    protected $table = 'wms_outbound_items';

    protected $fillable = [
        'outbound_id', 'product_id', 'location_id', 'quantity', 'batch_number',
    ];

    protected $casts = ['quantity' => 'integer'];

    public function outbound() { return $this->belongsTo(WmsOutbound::class, 'outbound_id'); }
    public function product()  { return $this->belongsTo(WmsProduct::class, 'product_id'); }
    public function location() { return $this->belongsTo(WmsLocation::class, 'location_id'); }
}
