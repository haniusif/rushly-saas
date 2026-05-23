<?php

namespace App\Models\Backend\Wms;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WmsGrnItem extends Model
{
    use HasFactory;

    protected $table = 'wms_grn_items';

    protected $fillable = [
        'grn_id', 'product_id', 'location_id',
        'expected_qty', 'received_qty', 'batch_number', 'expiry_date',
        'condition', 'notes',
    ];

    protected $casts = [
        'expected_qty' => 'integer',
        'received_qty' => 'integer',
        'expiry_date'  => 'date',
    ];

    public function grn()      { return $this->belongsTo(WmsGrn::class, 'grn_id'); }
    public function product()  { return $this->belongsTo(WmsProduct::class, 'product_id'); }
    public function location() { return $this->belongsTo(WmsLocation::class, 'location_id'); }
}
